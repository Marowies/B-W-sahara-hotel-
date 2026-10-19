<?php

use App\Services\HotelTokenService;
use Botble\Hotel\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuthFixtureRequest extends Request
{
    public function validate(array $rules, ...$arguments): array
    {
        global $validator;
        return $validator->make($this->all(), $rules)->validate();
    }
}

function authRequest(array $data = []): AuthFixtureRequest
{
    $request = AuthFixtureRequest::create('https://hotel.example/api/hotel/auth/otp/verify', 'POST', $data);
    $request->setLaravelSession(app('session'));

    return $request;
}

function authCode(string $code = '123456'): void
{
    global $db;
    $db->table('hotel_login_codes')->where('email', 'synthetic@example.invalid')->delete();
    $db->table('hotel_login_codes')->insert([
        'email' => 'synthetic@example.invalid', 'code_hash' => hash_hmac('sha256', 'synthetic@example.invalid|' . $code, config('app.key')),
        'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now(),
    ]);
}

test('Auth: unauthenticated checkout creates no booking', function () use ($payload, $db): void {
    $before = $db->table('ht_bookings')->count();
    try { checkout($payload, null, false); throw new RuntimeException('Guest accepted'); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { check($e->getStatusCode() === 401, 'Wrong guest rejection'); }
    check($db->table('ht_bookings')->count() === $before, 'Guest wrote booking');
});

test('Auth: verified customer cannot switch booking email or register a different account', function () use ($payload, $db, $guard): void {
    $guard->customerId = null;
    $before = $db->table('ht_bookings')->count();
    foreach ([['email' => 'someoneelse@example.invalid'], ['register_customer' => 1]] as $change) {
        try { checkout(array_replace($payload, $change)); throw new RuntimeException('Account switched'); }
        catch (Illuminate\Validation\ValidationException) {}
    }
    check($db->table('ht_bookings')->count() === $before, 'Rejected identity wrote booking');
});

test('Auth: OTP can be consumed only once', function (): void {
    authCode();
    $controller = new App\Http\Controllers\HotelCustomerAuthController(app(HotelTokenService::class));
    $request = authRequest(['email' => 'synthetic@example.invalid', 'code' => '123456']);
    $first = $controller->verifyCode($request);
    check($first->getStatusCode() === 200 && $first->getData(true)['authenticated'], 'Valid OTP failed');
    check(\Illuminate\Support\Facades\Auth::guard('customer')->check(), 'OTP did not authenticate the existing account pages');
    check($controller->verifyCode($request)->getStatusCode() === 422, 'OTP replay accepted');
});

test('Auth: incorrect OTP exhausts attempts without creating sessions', function () use ($db): void {
    authCode();
    $before = $db->table('hotel_auth_tokens')->count();
    $controller = new App\Http\Controllers\HotelCustomerAuthController(app(HotelTokenService::class));
    for ($i = 0; $i < 5; $i++) check($controller->verifyCode(authRequest(['email' => 'synthetic@example.invalid', 'code' => '000000']))->getStatusCode() === 422, 'Wrong guess accepted');
    check($controller->verifyCode(authRequest(['email' => 'synthetic@example.invalid', 'code' => '123456']))->getStatusCode() === 429, 'Locked challenge accepted');
    check($db->table('hotel_auth_tokens')->count() === $before, 'Invalid OTP created session');
});

test('Auth: expired OTP rejected', function () use ($db): void {
    authCode();
    $db->table('hotel_login_codes')->update(['expires_at' => now()->subSecond()]);
    $controller = new App\Http\Controllers\HotelCustomerAuthController(app(HotelTokenService::class));
    check($controller->verifyCode(authRequest(['email' => 'synthetic@example.invalid', 'code' => '123456']))->getStatusCode() === 422, 'Expired code accepted');
});

test('Auth: resend cooldown and mail failure rollback', function () use ($db): void {
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.transport' => 'smtp']);
    $mail = new class {
        public bool $fail = false;
        public function raw(...$args): void { if ($this->fail) throw new RuntimeException('Synthetic delivery failure'); }
        // The sign-in code is now a branded Mailable sent with Mail::to($email)->send(...).
        public function to(...$args): static { return $this; }
        public function send(...$args): void { if ($this->fail) throw new RuntimeException('Synthetic delivery failure'); }
    };
    Illuminate\Support\Facades\Mail::swap($mail);
    $controller = new App\Http\Controllers\HotelCustomerAuthController(app(HotelTokenService::class));
    $request = authRequest(['email' => 'resend-test@example.invalid']);
    check($controller->requestCode($request)->getStatusCode() === 200, 'Code send failed');
    check($controller->requestCode($request)->getStatusCode() === 429, 'Resend cooldown skipped');
    $mail->fail = true;
    // The harness has no exception reporter; bind one to exercise the caught delivery failure.
    app()->instance(Illuminate\Contracts\Debug\ExceptionHandler::class, new class implements Illuminate\Contracts\Debug\ExceptionHandler {
        public function report(Throwable $e) {} public function shouldReport(Throwable $e) { return false; }
        public function render($request, Throwable $e) {} public function renderForConsole($output, Throwable $e) {}
    });
    check($controller->requestCode(authRequest(['email' => 'failed-send@example.invalid']))->getStatusCode() === 503, 'Delivery failure hidden');
    check(! $db->table('hotel_login_codes')->where('email', 'failed-send@example.invalid')->exists(), 'Failed delivery left a usable code');
});

test('Auth: local, staging and production refuse logging mailers without creating OTPs', function () use ($db): void {
    $originalEnv = app('env');
    $originalMail = config('mail');
    $calls = 0;
    Illuminate\Support\Facades\Mail::swap(new class($calls) {
        public function __construct(private int &$calls) {}
        public function raw(...$args): void { $this->calls++; }
    });
    try {
        config(['mail.mailers.log.transport' => 'log', 'mail.mailers.array.transport' => 'array',
            'mail.mailers.failover.transport' => 'failover', 'mail.mailers.failover.mailers' => ['smtp', 'log']]);
        foreach (['local', 'staging', 'production'] as $environment) {
            app()->instance('env', $environment);
            foreach (['log', 'array', 'failover'] as $driver) {
                config(['mail.default' => $driver]);
                $email = "$environment-$driver@example.invalid";
                $response = (new App\Http\Controllers\HotelCustomerAuthController(app(HotelTokenService::class)))->requestCode(authRequest(['email' => $email]));
                check($response->getStatusCode() === 503, 'Non-delivery mailer reported success');
                check($response->getData(true)['code'] === 'mail_not_configured', 'Missing error code');
                check(! $db->table('hotel_login_codes')->where('email', $email)->exists(), 'Non-delivery mailer created an OTP');
            }
        }
        check($calls === 0, 'OTP leaked into testing mail transport');
    } finally {
        app()->instance('env', $originalEnv);
        config(['mail' => $originalMail]);
    }
});

test('Auth: rotation grace returns one successor and hashed storage', function () use ($db): void {
    $service = app(HotelTokenService::class);
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $next = $service->refresh($pair['refresh_token'], authRequest());
    $race = $service->refresh($pair['refresh_token'], authRequest());
    check($next['ok'] && $race['ok'] && $next['refresh_token'] === $race['refresh_token'], 'Grace branched or lost successor');
    $row = $db->table('hotel_auth_tokens')->where('refresh_hash', hash('sha256', $next['refresh_token']))->first();
    check($row && $row->refresh_ciphertext !== $next['refresh_token'], 'Plaintext refresh storage');
});

test('Auth: delayed replay revokes family and not another device', function (): void {
    $service = app(HotelTokenService::class);
    $now = now()->copy();
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $other = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $next = $service->refresh($pair['refresh_token'], authRequest());
    try {
        Carbon::setTestNow($now->copy()->addSeconds(6));
        $result = $service->refresh($pair['refresh_token'], authRequest());
        check(! $result['ok'] && $result['reason'] === 'reuse', 'Replay not detected');
        check(! $service->authenticate($next['access_token']), 'Revoked family access lives');
        check($service->authenticate($other['access_token']) !== null, 'Other device wrongly revoked');
        check(count($GLOBALS['authAlerts'] ?? []) > 0, 'Missing security alert');
    } finally { Carbon::setTestNow($now); }
});

test('Auth: logout invalidates access and grace immediately', function (): void {
    $service = app(HotelTokenService::class);
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $next = $service->refresh($pair['refresh_token'], authRequest());
    $service->logout($pair['refresh_token'], null);
    check(! $service->authenticate($next['access_token']), 'Logout kept access');
    check(! $service->refresh($pair['refresh_token'], authRequest())['ok'], 'Grace revived logout');
    check(! $service->refresh($next['refresh_token'], authRequest())['ok'], 'Successor revived logout');
});

test('Auth: pruning retains old rotated hashes until absolute expiry', function () use ($db): void {
    $service = app(HotelTokenService::class);
    $now = now()->copy();
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $service->refresh($pair['refresh_token'], authRequest());
    try {
        Carbon::setTestNow($now->copy()->addDays(8)); $service->prune();
        check($db->table('hotel_auth_tokens')->where('refresh_hash', hash('sha256', $pair['refresh_token']))->exists(), 'Replay evidence deleted early');
        Carbon::setTestNow($now->copy()->addDays(31)); $service->prune();
        check(! $db->table('hotel_auth_tokens')->where('refresh_hash', hash('sha256', $pair['refresh_token']))->exists(), 'Expired token not pruned');
    } finally { Carbon::setTestNow($now); }
});

test('Auth: access expires exactly at 15 minutes and tampering is rejected', function (): void {
    $service = app(HotelTokenService::class);
    $now = now()->copy();
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    check($service->authenticate($pair['access_token']) !== null, 'New access invalid');
    check(! $service->authenticate($pair['access_token'] . 'x'), 'Tampered token accepted');
    try { Carbon::setTestNow($now->copy()->addSeconds(900)); check(! $service->authenticate($pair['access_token']), 'Boundary accepted'); }
    finally { Carbon::setTestNow($now); }
});

test('Auth: production refresh and delete cookies have secure attributes', function (): void {
    $service = app(HotelTokenService::class);
    foreach ([$service->cookie('synthetic', now()->addDay(), authRequest()), $service->cookie(null, null, authRequest())] as $cookie) {
        check($cookie->isSecure() && $cookie->isHttpOnly() && $cookie->getSameSite() === 'strict' && $cookie->getPath() === '/api/hotel/auth', 'Cookie attributes invalid');
    }
});

test('Auth: refresh cannot extend absolute expiry and advertises remaining access lifetime', function (): void {
    $service = app(HotelTokenService::class);
    $now = now()->copy();
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    try {
        Carbon::setTestNow($now->copy()->addDays(30)->subSeconds(20));
        $next = $service->refresh($pair['refresh_token'], authRequest());
        check($next['ok'] && $next['expires_in'] === 20, 'Access extended past absolute expiry');
        Carbon::setTestNow($now->copy()->addDays(30));
        check(! $service->refresh($next['refresh_token'], authRequest())['ok'], 'Absolute boundary accepted');
    } finally { Carbon::setTestNow($now); }
});

function authWorkers(array $jobs): array
{
    global $argv;
    $processes = [];
    $at = microtime(true) + 2;
    foreach ($jobs as $i => [$kind, $token]) {
        $cmd = [PHP_BINARY, '-c', php_ini_loaded_file(), dirname(__DIR__) . '/Integration/run.php', $argv[1], $argv[2], 'worker', (string) $i, (string) $at, $kind, $token];
        $p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        check(is_resource($p), 'Could not spawn auth worker'); fclose($pipes[0]); $processes[] = [$p, $pipes];
    }
    $results = [];
    foreach ($processes as [$p, $pipes]) {
        $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        check(proc_close($p) === 0, 'Auth worker failed: ' . $error . $output);
        $results[] = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
    }
    return $results;
}

if ($db->getDriverName() === 'mysql' && isset($argv[2])) {
    test('Auth InnoDB: two OTP requests consume one challenge and create one session', function () use ($db): void {
        authCode(); $before = $db->table('hotel_auth_tokens')->count();
        $results = authWorkers([['auth-otp', ''], ['auth-otp', '']]);
        $statuses = array_column($results, 'status'); sort($statuses);
        check($statuses === [200, 422] && $db->table('hotel_auth_tokens')->count() === $before + 1, 'Concurrent OTP consumption duplicated session');
        $GLOBALS['integrationEvidence']['auth_otp_concurrency'] = $statuses;
    });
    test('Auth InnoDB: concurrent rotation creates one successor', function () use ($db): void {
        $pair = app(HotelTokenService::class)->issue(Customer::query()->findOrFail(43), authRequest());
        $row = $db->table('hotel_auth_tokens')->where('refresh_hash', hash('sha256', $pair['refresh_token']))->first();
        $results = authWorkers([['auth-refresh', $pair['refresh_token']], ['auth-refresh', $pair['refresh_token']]]);
        check($results[0]['ok'] && $results[1]['ok'] && $results[0]['hash'] === $results[1]['hash'], 'Refresh branches');
        check($db->table('hotel_auth_tokens')->where('family_id', $row->family_id)->where('status', 'active')->count() === 1, 'Multiple successors');
        $GLOBALS['integrationEvidence']['auth_refresh_concurrency'] = ['same_successor' => true, 'active_tokens' => 1];
    });
    test('Auth InnoDB: concurrent logout and refresh leave no active successor', function () use ($db): void {
        $pair = app(HotelTokenService::class)->issue(Customer::query()->findOrFail(43), authRequest());
        $row = $db->table('hotel_auth_tokens')->where('refresh_hash', hash('sha256', $pair['refresh_token']))->first();
        authWorkers([['auth-refresh', $pair['refresh_token']], ['auth-logout', $pair['refresh_token']]]);
        check($db->table('hotel_auth_tokens')->where('family_id', $row->family_id)->where('status', 'active')->count() === 0, 'Logout race revived family');
        check(! app(HotelTokenService::class)->authenticate($pair['access_token']), 'Logout race kept access');
        $GLOBALS['integrationEvidence']['auth_logout_concurrency'] = ['active_tokens' => 0];
    });
}

test('Google: verified callback creates both sessions without requesting email OTP', function () use ($session, $db, $guard): void {
    Illuminate\Support\Facades\Mail::swap(new class { public function raw(...$args): void { throw new RuntimeException('Google must not send an OTP'); } });
    $customer = Customer::query()->findOrFail(43);
    $before = $db->table('hotel_auth_tokens')->count();
    $request = authRequest();
    $session->put(App\Services\HotelGoogleSession::RETURN_KEY, '/ar/takhteet-eqama/moragaa/');
    $session->put(App\Services\HotelGoogleSession::PENDING_KEY, ['pending' => true]);
    $response = app(App\Services\HotelGoogleSession::class)->complete($customer, $request);
    check($response->headers->get('Location') === '/ar/takhteet-eqama/moragaa/', 'Booking return lost');
    check(! $session->has(App\Services\HotelGoogleSession::RETURN_KEY) && ! $session->has(App\Services\HotelGoogleSession::PENDING_KEY), 'Old callback intent survived');
    check(str_contains($response->headers->get('Cache-Control'), 'no-store'), 'Private callback cacheable');
    $cookie = $response->headers->getCookies()[0];
    check($cookie->getName() === HotelTokenService::COOKIE && $cookie->isHttpOnly() && $cookie->isSecure() && $cookie->getSameSite() === 'strict' && $cookie->getPath() === HotelTokenService::COOKIE_PATH, 'Google cookie policy differs');
    check((bool) $cookie->getValue() && $guard->check() && $db->table('hotel_auth_tokens')->count() === $before + 1, 'Google session not established');
    $pair = app(HotelTokenService::class)->refresh($cookie->getValue(), $request);
    check($pair['ok'] && app(HotelTokenService::class)->authenticate($pair['access_token'])->getKey() === $customer->getKey(), 'Google refresh belongs to wrong customer');
    check(! str_contains($response->headers->get('Location'), $customer->email), 'Identity leaked in redirect');
});

test('Google: ordinary sign-in returns directly to the account and retains safe account destinations', function () use ($session): void {
    $customer = Customer::query()->findOrFail(43);
    foreach (['/login/' => '/customer/overview?lang=en', '/ar/dokhool/' => '/customer/overview?lang=ar', '/zh/denglu/' => '/customer/overview?lang=zh', '/login/?next=%2Fcustomer%2Fbookings%3Flang%3Den' => '/customer/bookings?lang=en'] as $from => $to) {
        $session->put(App\Services\HotelGoogleSession::RETURN_KEY, $from);
        $response = app(App\Services\HotelGoogleSession::class)->complete($customer, authRequest());
        check($response->headers->get('Location') === $to, 'Wrong Google destination: ' . $from);
    }
});

test('Google: pending OTP cooldown survives reload and expires without leaking internal deadlines', function () use ($session): void {
    $clock = Carbon::getTestNow();
    $session->put(App\Services\HotelGoogleSession::PENDING_KEY, ['pending' => true, 'email' => 'synthetic@example.invalid', 'resend_at' => now()->addSeconds(15)->timestamp, 'expires_at' => now()->addMinutes(10)->timestamp]);
    try {
        $first = app(App\Services\HotelGoogleSession::class)->pending(authRequest())->getData(true);
        check($first['resend_in'] === 15 && ! isset($first['expires_at'], $first['resend_at']), 'Internal pending state leaked');
        Carbon::setTestNow(now()->addSeconds(16));
        check(app(App\Services\HotelGoogleSession::class)->pending(authRequest())->getData(true)['resend_in'] === 0, 'Reload reset cooldown');
        Carbon::setTestNow(now()->addMinutes(11));
        check(app(App\Services\HotelGoogleSession::class)->pending(authRequest())->getData(true) === ['pending' => false], 'Expired Google OTP intent survived');
    } finally { Carbon::setTestNow($clock);$session->forget(App\Services\HotelGoogleSession::PENDING_KEY); }
});

test('Google: API logout clears both customer sessions and callback intent', function () use ($session, $guard): void {
    $service = app(HotelTokenService::class);
    $customer = Customer::query()->findOrFail(43);
    $pair = $service->issue($customer, authRequest());
    $request = authRequest();
    $request->cookies->set(HotelTokenService::COOKIE, $pair['refresh_token']);
    $request->setLaravelSession($session);
    $session->put(App\Services\HotelGoogleSession::RETURN_KEY, '/login/');
    $guard->setUser($customer);
    (new App\Http\Controllers\HotelCustomerAuthController($service))->logout($request);
    check(! $guard->check() && ! $session->has(App\Services\HotelGoogleSession::RETURN_KEY), 'CMS session survived logout');
    check(! $service->authenticate($pair['access_token']) && ! $service->refresh($pair['refresh_token'], $request)['ok'], 'API session survived logout');
});

test('Google: return destinations reject external and unrelated application paths', function (): void {
    foreach (['/login/', '/ar/login/', '/zh/register/', '/booking/review/', '/ar/dokhool/', '/ar/hesab-gadeed/', '/ar/takhteet-eqama/moragaa/', '/zh/denglu/', '/zh/zhuce/', '/zh/zhusu-jihua/queren/'] as $path) {
        check(App\Services\HotelGoogleSession::returnPath($path) === $path, 'Valid return rejected');
    }
    foreach (['https://evil.example/', '//evil.example/', '/admin/', '/auth/google', '/login/?token=secret', null, [], "/login/\n"] as $path) {
        check(App\Services\HotelGoogleSession::returnPath($path) === '/login/', 'Unsafe return accepted');
    }
    // A sign-in page keeps one account page to continue to; any other target is dropped.
    foreach (['/login/?next=%2Fcustomer%2Foverview' => '/login/?next=%2Fcustomer%2Foverview',
        '/ar/dokhool/?next=/customer/bookings' => '/ar/dokhool/?next=%2Fcustomer%2Fbookings',
        '/login/?next=https://evil.example/' => '/login/', '/login/?next=//evil.example/' => '/login/',
        '/login/?next=/customer/overview&token=secret' => '/login/', '/login/?next=/admin/' => '/login/',
        '/booking/review/?next=/customer/overview' => '/booking/review/'] as $path => $expected) {
        check(App\Services\HotelGoogleSession::returnPath($path) === $expected, 'Account return mishandled: ' . $path);
    }
});

/** POST /api/hotel/auth/refresh with only the HttpOnly cookie; extra request data must never pick the identity. */
function refreshWith(?string $token, array $data = []): Illuminate\Http\JsonResponse
{
    $request = authRequest($data);
    if ($token !== null) {
        $request->cookies->set(HotelTokenService::COOKIE, $token);
    }

    return (new App\Http\Controllers\HotelCustomerAuthController(app(HotelTokenService::class)))->refresh($request);
}

test('Session: valid refresh restores the account-page session after the Laravel session expired', function () use ($guard, $session): void {
    $service = app(HotelTokenService::class);
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $guard->logout();
    $before = $session->getId();
    $response = refreshWith($pair['refresh_token']);
    check($response->getStatusCode() === 200 && $response->getData(true)['email'] === 'synthetic@example.invalid', 'Valid refresh rejected');
    check($guard->id() === 43, 'Refresh left the account pages signed out (My account -> /login/ loop)');
    check($session->getId() !== $before, 'Session id not regenerated when the page session was restored');
    check($service->authenticate($response->getData(true)['access_token'])?->getKey() === 43, 'Rotated access token invalid');
    $guard->logout();
});

test('Session: refresh replaces a different customer page session and ignores client identity', function () use ($guard): void {
    $service = app(HotelTokenService::class);
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $guard->setUser(Customer::query()->findOrFail(42));
    $response = refreshWith($pair['refresh_token'], ['email' => 'private@example.invalid', 'customer_id' => 42]);
    check($response->getStatusCode() === 200 && $guard->id() === 43, 'Account pages kept another customer after refresh');
    check($response->getData(true)['email'] === 'synthetic@example.invalid', 'Client-supplied identity leaked into the response');
    $guard->logout();
});

test('Session: unchanged page session is kept without a new session id', function () use ($guard, $session): void {
    $service = app(HotelTokenService::class);
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $guard->setUser(Customer::query()->findOrFail(43));
    $before = $session->getId();
    check(refreshWith($pair['refresh_token'])->getStatusCode() === 200 && $guard->id() === 43, 'Signed-in refresh failed');
    check($session->getId() === $before, 'Every refresh regenerated the session (breaks other tabs)');
    $guard->logout();
});

test('Session: missing, invalid, expired and revoked refresh tokens never sign in', function () use ($guard): void {
    $service = app(HotelTokenService::class);
    $guard->logout();
    $missing = refreshWith(null);
    check($missing->getStatusCode() === 200 && $missing->getData(true)['authenticated'] === false && ! isset($missing->getData(true)['access_token']) && ! $guard->check(), 'Missing cookie signed in');
    check(refreshWith('not-a-real-token')->getStatusCode() === 401 && ! $guard->check(), 'Invalid token signed in');
    // A rejected token also ends a page session left on this device.
    $guard->setUser(Customer::query()->findOrFail(43));
    check(refreshWith(str_repeat('x', 64))->getStatusCode() === 401 && ! $guard->check(), 'Rejected token left the account pages open');

    $revoked = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $service->logout($revoked['refresh_token'], null);
    $guard->setUser(Customer::query()->findOrFail(43));
    check(refreshWith($revoked['refresh_token'])->getStatusCode() === 401 && ! $guard->check(), 'Revoked token kept or restored the page session');

    $clock = Carbon::getTestNow();
    try {
        $expired = $service->issue(Customer::query()->findOrFail(43), authRequest());
        Carbon::setTestNow(now()->addDays(HotelTokenService::ABSOLUTE_DAYS)->addMinute());
        check(refreshWith($expired['refresh_token'])->getStatusCode() === 401 && ! $guard->check(), 'Expired token signed in');
    } finally { Carbon::setTestNow($clock); }
    // A missing cookie (for example the CMS password form) does not end a page session.
    $guard->setUser(Customer::query()->findOrFail(43));
    check(refreshWith(null)->getData(true)['authenticated'] === false && $guard->id() === 43, 'Cookie-less refresh signed out a CMS session');
    $guard->logout();
});

test('Session: logout closes both the token family and the restored page session', function () use ($guard): void {
    $service = app(HotelTokenService::class);
    $pair = $service->issue(Customer::query()->findOrFail(43), authRequest());
    $guard->logout();
    $next = refreshWith($pair['refresh_token'])->getData(true);
    check($guard->id() === 43, 'Refresh did not restore the page session');
    $request = authRequest();
    $request->cookies->set(HotelTokenService::COOKIE, $service->refresh($pair['refresh_token'], authRequest())['refresh_token']);
    (new App\Http\Controllers\HotelCustomerAuthController($service))->logout($request);
    check(! $guard->check() && ! $service->authenticate($next['access_token']), 'Logout left a session open');
    check(refreshWith($pair['refresh_token'])->getStatusCode() === 401 && ! $guard->check(), 'Logged-out family restored a session');
});

test('Booking: native form requires an authenticated customer session', function (): void {
    $guard=\Illuminate\Support\Facades\Auth::guard('customer');$guard->logout();
    $middleware=new App\Http\Middleware\RequireHotelBookingSession();
    $request=authRequest();$request->headers->set('Accept','application/json');
    $result=$middleware->handle($request,fn()=>response('reached'));
    check($result->getStatusCode()===401,'Anonymous native booking was allowed');
    $guard->setUser(Customer::query()->firstOrFail());
    $result=$middleware->handle($request,fn()=>response('reached'));
    check($result->getContent()==='reached','Signed-in booking was blocked');$guard->logout();
});

test('Traffic: bursts return 429 with retry guidance and expire', function (): void {
    $limiter=new \Illuminate\Cache\RateLimiter(new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore()));
    \Illuminate\Support\Facades\RateLimiter::swap($limiter);
    $request=\Illuminate\Http\Request::create('https://hotel.example/api/hotel/rooms','GET',[],[],[],['REMOTE_ADDR'=>'192.0.2.9','HTTP_ACCEPT'=>'application/json']);
    $middleware=new App\Http\Middleware\LimitHotelTraffic();
    for($i=0;$i<120;$i++)check($middleware->handle($request,fn()=>response('ok'))->getStatusCode()===200,'Normal request blocked');
    $blocked=$middleware->handle($request,fn()=>response('ok'));
    check($blocked->getStatusCode()===429 && (int)$blocked->headers->get('Retry-After')>0,'Burst not limited');
    $key='hotel-traffic:'.hash('sha256','192.0.2.9');$limiter->clear($key);
    check($middleware->handle($request,fn()=>response('ok'))->getStatusCode()===200,'Client did not recover');
});

test('Auth: concurrency migration rollback and reapply', function () use ($source, $db): void {
    $migration = require $source . '/database/migrations/2026_10_08_110000_harden_customer_auth_concurrency.php';
    $migration->down();
    check(! $db->getSchemaBuilder()->hasTable('hotel_login_locks') && ! $db->getSchemaBuilder()->hasColumn('hotel_auth_tokens', 'refresh_ciphertext'), 'Auth rollback incomplete');
    $migration->up();
    check($db->getSchemaBuilder()->hasTable('hotel_login_locks') && $db->getSchemaBuilder()->hasColumn('hotel_auth_tokens', 'refresh_ciphertext'), 'Auth reapply incomplete');
});
