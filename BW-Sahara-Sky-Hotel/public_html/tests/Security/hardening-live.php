<?php

/*
 * Live regression for customer sign-in hardening. Needs a running local app (NOT for CI, it sends real HTTP requests):
 *   php -S 127.0.0.1:8781 -t public <router>     (MAIL_MAILER=log or Botble email_driver=log, so the code lands in storage/logs)
 *   php tests/Security/hardening-live.php [http://127.0.0.1:8781] <directory holding the sink's *.eml files>
 * Two cookie jars play two different browsers: A signs in, B only receives what A could send (URLs).
 */
$base = rtrim($argv[1] ?? 'http://127.0.0.1:8781', '/');
$mailDir = $argv[2] ?? getenv('HOTEL_TEST_MAIL_DIR') ?: '';
$results = [];
$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $results, $fail;
    $results[] = ($ok ? 'PASS' : 'FAIL') . ' ' . $name . ($ok || $detail === '' ? '' : "  -> $detail");
    $fail += $ok ? 0 : 1;
}
class Browser
{
    public array $cookies = [];
    public array $urls = [];

    public function __construct(private string $base)
    {
    }

    public function req(string $method, string $path, array|string|null $body = null, array $headers = []): array
    {
        $ch = curl_init(str_starts_with($path, 'http') ? $path : $this->base . $path);
        $cookie = implode('; ', array_map(fn ($k, $v) => "$k=$v", array_keys($this->cookies), $this->cookies));
        $h = isset($headers['Accept']) ? [] : ['Accept: application/json, text/html'];
        if ($cookie) {
            $h[] = 'Cookie: ' . $cookie;
        }
        foreach ($headers as $k => $v) {
            $h[] = "$k: $v";
        }
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $h, CURLOPT_TIMEOUT => 40]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($h, is_array($body) ? ['Content-Type: application/json'] : []));
        }
        $raw = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $head = substr($raw, 0, (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $content = substr($raw, strlen($head));
        curl_close($ch);
        $hdr = [];
        foreach (explode("\r\n", $head) as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $hdr[strtolower(trim($k))][] = trim($v);
            }
        }
        foreach ($hdr['set-cookie'] ?? [] as $c) {
            [$pair] = explode(';', $c, 2);
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            if (stripos($c, 'expires=Thu, 01 Jan 1970') !== false || stripos($c, 'max-age=0') !== false || $v === 'deleted') {
                unset($this->cookies[$k]);
            } else {
                $this->cookies[$k] = $v;
            }
        }
        $this->urls[] = $method . ' ' . $path . ' -> ' . ($hdr['location'][0] ?? $status);

        return ['status' => $status, 'headers' => $hdr, 'body' => $content, 'json' => json_decode($content, true)];
    }

    public function csrf(): string
    {
        return (string) ($this->req('GET', '/api/hotel/session')['json']['csrf_token'] ?? '');
    }

    public function post(string $path, array $body = [], array $headers = []): array
    {
        return $this->req('POST', $path, $body, ['X-CSRF-TOKEN' => $this->csrf(), 'Accept' => 'application/json'] + $headers);
    }
}
function newestCode(string $email): string
{
    // A local SMTP sink (or the log mailer) holds the message; the six digits come from the newest mail for this address.
    $files = glob($GLOBALS['mailDir'] . '/*.eml') ?: [];
    usort($files, fn ($x, $y) => filemtime($y) <=> filemtime($x));
    foreach ($files as $file) {
        $mail = quoted_printable_decode((string) file_get_contents($file));
        if (! str_contains($mail, 'To: ' . $email)) {
            continue;
        }
        $parts = preg_split('/\r?\n\r?\n/', $mail, 2);
        // The code stands alone on its own line in both the text and the HTML part.
        if (preg_match('/^\s*(\d{6})\s*$/m', $parts[1] ?? '', $m)) {
            return $m[1];
        }
    }

    return '';
}
function otpLogin(Browser $b, string $email): array
{
    $b->post('/api/hotel/auth/otp/request', ['email' => $email]);
    usleep(400000);
    $code = newestCode($email);

    return $b->post('/api/hotel/auth/otp/verify', ['email' => $email, 'code' => $code]);
}

$email = 'hardening.' . bin2hex(random_bytes(3)) . '@example.com';
$A = new Browser($base);
$B = new Browser($base);
$C = new Browser($base);

// 1. Legacy sign-in doors are closed.
$closed = [['POST', '/login'], ['POST', '/register'], ['GET', '/register/confirm/1?expires=9999999999&signature=x'], ['GET', '/password/reset/abc'],
    ['GET', '/password/request'], ['POST', '/password/email'], ['GET', '/customer/change-password'], ['POST', '/api/v1/login'],
    ['POST', '/api/v1/register'], ['POST', '/api/v1/email/check'], ['POST', '/api/v1/auth/google']];
foreach ($closed as [$method, $path]) {
    $r = $C->req($method, $path, $method === 'POST' ? [] : null);
    check("closed: $method $path", $r['status'] === 404, 'status ' . $r['status']);
}

// 2. A signs in with the email code.
$login = otpLogin($A, $email);
check('OTP sign-in succeeds', ($login['status'] ?? 0) === 200 && ! empty($login['json']['access_token']), 'status ' . ($login['status'] ?? '?'));
$token = $login['json']['access_token'] ?? '';
$refreshCookie = $A->cookies['hotel_refresh'] ?? '';
check('refresh cookie is set', $refreshCookie !== '');
$sessionCookie = $A->cookies['botble_session'] ?? '';

// 3. Nothing that A can copy from the address bar or a redirect signs anyone in.
$own = $A->req('GET', '/customer/overview');
check('A reaches the account page', in_array($own['status'], [200], true), 'status ' . $own['status']);
$shared = implode("\n", $A->urls);
check('no token, code, email or session id appears in any URL A visited', ! preg_match('/access_token|refresh|code=\d{6}|hardening\.|' . preg_quote($sessionCookie ?: 'zzzz', '/') . '/', $shared), $shared);
foreach (['/customer/overview', '/customer/overview?lang=en', '/customer/bookings', '/customer/edit-account', '/customer/reviews', '/booking'] as $url) {
    $r = $B->req('GET', $url);
    $loc = $r['headers']['location'][0] ?? '';
    check("B opening A's URL $url is not signed in", $r['status'] !== 200 || ! str_contains($r['body'], $email), "status {$r['status']}");
    check("B opening $url does not receive A's data", ! str_contains($r['body'], $email));
}
$bs = $B->req('GET', '/api/hotel/session');
check('B has no signed-in session', ($bs['json']['authenticated'] ?? true) === false);
check('B cannot start checkout', $B->req('GET', '/api/hotel/checkout/eligibility')['status'] === 401);
check('B cannot refresh without A\'s cookie', $B->post('/api/hotel/auth/refresh')['json']['authenticated'] ?? false ? false : true);
// A sending B only the URL of the sign-in page with next= leaves B on the sign-in page.
$next = $B->req('GET', '/login/?next=%2Fcustomer%2Foverview');
check('B opening a sign-in URL with next= is not signed in', ! str_contains($next['body'], $email));

// 4. Responses that depend on the visitor are never cacheable by a shared cache.
foreach (['/api/hotel/session', '/customer/overview', '/customer/bookings'] as $url) {
    $r = $A->req('GET', $url);
    $cc = strtolower($r['headers']['cache-control'][0] ?? '');
    check("Cache-Control private/no-store on $url", str_contains($cc, 'no-store') && str_contains($cc, 'private'), $cc);
    check("Vary: Cookie on $url", str_contains(strtolower(implode(',', $r['headers']['vary'] ?? [])), 'cookie'), implode(',', $r['headers']['vary'] ?? []));
}

// 4b. The sign-in email is the hotel's branded multipart message, in the language the visitor was using.
foreach (['en' => ['Sahara Sky sign-in code', 'ltr'], 'ar' => ['رمز الدخول', 'rtl'], 'zh' => ['登录验证码', 'ltr']] as $loc => [$needle, $dir]) {
    $m = new Browser($base);
    $to = "locale.$loc." . bin2hex(random_bytes(2)) . '@example.com';
    $m->post('/api/hotel/auth/otp/request', ['email' => $to, 'locale' => $loc]);
    usleep(500000);
    $raw = '';
    foreach (glob($mailDir . '/*.eml') ?: [] as $file) {
        $c = (string) file_get_contents($file);
        if (str_contains($c, 'To: ' . $to)) {
            $raw = $c;
        }
    }
    $decoded = quoted_printable_decode($raw);
    check("email ($loc) is multipart text + HTML", str_contains($raw, 'multipart/alternative') && str_contains($raw, 'text/html') && str_contains($raw, 'text/plain'));
    check("email ($loc) is branded and localised", str_contains($decoded, 'B&W SAHARA SKY') && str_contains($decoded, $needle) && str_contains($decoded, 'dir="' . $dir . '"'));
    check("email ($loc) carries a six-digit code", newestCode($to) !== '');
}

// 5. "Sign out" by link (no JavaScript) really signs out: the refresh cookie must not bring the session back.
$jarCopy = $A->cookies;
$out = $A->req('GET', '/customer/logout');
check('GET /customer/logout redirects', in_array($out['status'], [301, 302], true), 'status ' . $out['status']);
check('refresh cookie removed from the browser', ! isset($A->cookies['hotel_refresh']));
$A->cookies = $jarCopy; // an attacker or stale tab that still holds the old cookies
$again = $A->post('/api/hotel/auth/refresh');
check('old refresh cookie no longer signs in after link logout', $again['status'] === 401, 'status ' . $again['status']);
$A->cookies = [];
check('old access token refused after logout', $A->req('GET', '/api/hotel/checkout/eligibility', null, ['Authorization' => 'Bearer ' . $token])['status'] === 401);

echo implode("\n", $results) . "\n";
echo $fail === 0 ? "\nALL PASS\n" : "\n$fail FAILED\n";
exit($fail === 0 ? 0 : 1);
