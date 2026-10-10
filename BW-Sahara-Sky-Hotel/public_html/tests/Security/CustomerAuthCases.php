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
    return AuthFixtureRequest::create('https://hotel.example/api/hotel/auth/otp/verify', 'POST', $data);
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
    $mail = new class {
        public bool $fail = false;
        public function raw(...$args): void { if ($this->fail) throw new RuntimeException('Synthetic delivery failure'); }
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

test('Auth: concurrency migration rollback and reapply', function () use ($source, $db): void {
    $migration = require $source . '/database/migrations/2026_10_08_110000_harden_customer_auth_concurrency.php';
    $migration->down();
    check(! $db->getSchemaBuilder()->hasTable('hotel_login_locks') && ! $db->getSchemaBuilder()->hasColumn('hotel_auth_tokens', 'refresh_ciphertext'), 'Auth rollback incomplete');
    $migration->up();
    check($db->getSchemaBuilder()->hasTable('hotel_login_locks') && $db->getSchemaBuilder()->hasColumn('hotel_auth_tokens', 'refresh_ciphertext'), 'Auth reapply incomplete');
});
