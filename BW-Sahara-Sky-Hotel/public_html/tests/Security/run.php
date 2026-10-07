<?php

// Standalone regressions: production classes, an in-memory SQLite database and no external services.
// php tests/Security/run.php [optional read-only dependency autoload path]
function apply_filters($name, $value, ...$arguments) {
    // Gateway providers extend this enum in production; emulate that registration in the isolated harness.
    if ($name === 'base_filter_enum_array' && ($arguments[0] ?? null) === Botble\Payment\Enums\PaymentMethodEnum::class) {
        return $value + ['PAYPAL' => 'paypal', 'STRIPE' => 'stripe'];
    }
    return $value;
}
function do_action(...$arguments): void {
    if (defined('HOTEL_INTEGRATION_TESTS')) { $GLOBALS['integrationActions'][] = $arguments; }
    if (isset($GLOBALS['integrationActionHandler'])) { ($GLOBALS['integrationActionHandler'])(...$arguments); }
}
function add_filter(...$arguments): void {}
function add_action(...$arguments): void {}
function is_plugin_active(string $name): bool { return false; }
function get_application_currency() { return Botble\Hotel\Models\Currency::query()->findOrFail(1); }

$source = dirname(__DIR__, 2);
$autoload = $argv[1] ?? $source . '/vendor/autoload.php';
if (! is_file($autoload)) {
    fwrite(STDERR, "Install dependencies or supply an existing read-only vendor/autoload.php path.\n");
    exit(2);
}
$loader = require $autoload;
$loader->addPsr4('Botble\\Hotel\\', $source . '/platform/plugins/hotel/src', true);
$loader->addPsr4('Botble\\Payment\\', $source . '/platform/plugins/payment/src', true);
$loader->addPsr4('Botble\\Base\\Providers\\', $source . '/platform/core/base/src/Providers', true);
$classMap = ['Botble\\Base\\Providers\\EventServiceProvider' => $source . '/platform/core/base/src/Providers/EventServiceProvider.php'];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . '/platform/plugins/hotel/src')) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $relative = substr($file->getPathname(), strlen($source . '/platform/plugins/hotel/src/'), -4);
        $classMap['Botble\\Hotel\\' . str_replace(['/', '\\'], '\\', $relative)] = $file->getPathname();
    }
}
$loader->addClassMap($classMap);
require_once $source . '/platform/core/base/helpers/constants.php';
require __DIR__ . '/TransportDouble.php';

$app = new Illuminate\Foundation\Application($source);
$app->instance('env', 'production');
$app->instance('config', new Illuminate\Config\Repository([
    'app' => ['key' => 'synthetic-test-key', 'locale' => 'en'],
    'session' => ['lifetime' => 120, 'path' => '/', 'domain' => null, 'secure' => true, 'http_only' => true, 'same_site' => 'lax'],
    'core' => ['base' => ['general' => ['disable_verify_csrf_token' => true]]],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$events = new Illuminate\Events\Dispatcher($app);
$app->instance('events', $events);
$router = new Illuminate\Routing\Router($events, $app);
$app->instance('router', $router);
$url = new Illuminate\Routing\UrlGenerator($router->getRoutes(), Illuminate\Http\Request::create('https://hotel.example'));
$app->instance('url', $url);
$router->get('booking/{transactionId}', fn () => null)->name('public.booking.information');
$router->getRoutes()->refreshNameLookups();
$app->instance('cookie', new Illuminate\Cookie\CookieJar());
$app->instance('encrypter', new Illuminate\Encryption\Encrypter(str_repeat('a', 32), 'AES-256-CBC'));
$session = new Illuminate\Session\Store('security-test', new Illuminate\Session\ArraySessionHandler(120));
$session->start();
$app->instance('session', $session);
$app->instance(Botble\Base\Supports\MacroableModels::class, new class {
    public function modelHasMacro(...$arguments) { return false; }
});
Botble\Base\Facades\BaseHelper::swap(new class {
    public function getPhoneValidationRule() { return 'string'; }
    public function clean($value) { return $value; }
    public function hasDemoModeEnabled() { return true; }
    public function logError($exception): void {}
});
$hotel = new class {
    public string $dateFormat = 'd-m-Y';
    public array $checkoutData = [];
    public function getDateFormat() { return $this->dateFormat; }
    public function getMinimumNumberOfGuests() { return 1; }
    public function getMaximumNumberOfGuests() { return 10; }
    public function isBookingEnabled() { return true; }
    public function isEnableFoodOrder() { return false; }
    public function dateFromRequest($date) { return Carbon\Carbon::createFromFormat($this->dateFormat, $date)->startOfDay(); }
    public function getCheckoutData() { return $this->checkoutData; }
    public function saveCheckoutData($data): void { $this->checkoutData = $data; }
    public function getBookingNumber($id) { return 'TEST-' . $id; }
};
Botble\Hotel\Facades\HotelHelper::swap($hotel);
$guard = new class {
    public ?int $customerId = null;
    public function check() { return $this->customerId !== null; }
    public function id() { return $this->customerId; }
};
Illuminate\Support\Facades\Auth::swap(new class($guard) {
    public function __construct(public $customerGuard) {}
    public function guard($name = null) { return $this->customerGuard; }
});
$translator = new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader(), 'en');
$app->instance('translator', $translator);
$validator = new Illuminate\Validation\Factory($translator, $app);
$capsule = new Illuminate\Database\Capsule\Manager($app);
$testConnection = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
if (defined('HOTEL_INTEGRATION_TESTS') && isset($argv[2])) {
    $testConnection = require $argv[2];
    if (($testConnection['host'] ?? null) !== '127.0.0.1'
        || ! str_starts_with($testConnection['database'] ?? '', 'hotel_test_')) {
        throw new RuntimeException('Integration tests require a loopback-only hotel_test_ database.');
    }
}
$capsule->addConnection($testConnection);
$capsule->setEventDispatcher($events);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$app->instance('db', $capsule->getDatabaseManager());
$validator->setPresenceVerifier(new Illuminate\Validation\DatabasePresenceVerifier($capsule->getDatabaseManager()));
$app->instance('validator', $validator);

if (defined('HOTEL_INTEGRATION_WORKER')) {
    $db = $capsule->getConnection();
    require dirname(__DIR__) . '/Integration/worker.php';
    exit;
}

$sql = [
    'CREATE TABLE ht_rooms (id INTEGER PRIMARY KEY, name TEXT, images TEXT, status TEXT, price REAL, number_of_rooms INTEGER, max_adults INTEGER, max_children INTEGER, currency_id INTEGER, tax_id INTEGER)',
    'CREATE TABLE ht_room_dates (id INTEGER PRIMARY KEY, room_id INTEGER, start_date TEXT, end_date TEXT, active INTEGER, number_of_rooms INTEGER, value REAL, value_type TEXT)',
    'CREATE TABLE ht_bookings (id INTEGER PRIMARY KEY AUTOINCREMENT, status TEXT, customer_id INTEGER, payment_id INTEGER, currency_id INTEGER, requests TEXT, arrival_time TEXT, number_of_guests INTEGER, number_of_children INTEGER, amount REAL, sub_total REAL, coupon_amount REAL, coupon_code TEXT, tax_amount REAL, transaction_id TEXT, booking_number TEXT, created_at TEXT, updated_at TEXT)',
    'CREATE TABLE ht_booking_rooms (id INTEGER PRIMARY KEY AUTOINCREMENT, room_id INTEGER, room_name TEXT, room_image TEXT, booking_id INTEGER, price REAL, currency_id INTEGER, number_of_rooms INTEGER, start_date TEXT, end_date TEXT, created_at TEXT, updated_at TEXT)',
    'CREATE TABLE ht_booking_addresses (id INTEGER PRIMARY KEY AUTOINCREMENT, first_name TEXT, last_name TEXT, email TEXT, phone TEXT, country TEXT, state TEXT, city TEXT, address TEXT, zip TEXT, booking_id INTEGER, created_at TEXT, updated_at TEXT)',
    'CREATE TABLE ht_taxes (id INTEGER PRIMARY KEY, percentage REAL)',
    'CREATE TABLE ht_currencies (id INTEGER PRIMARY KEY, title TEXT)',
    'CREATE TABLE ht_services (id INTEGER PRIMARY KEY, status TEXT)',
    'CREATE TABLE ht_foods (id INTEGER PRIMARY KEY, status TEXT)',
    'CREATE TABLE ht_customers (id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT, email TEXT, phone TEXT)',
    'CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT)',
];
foreach ($sql as $statement) {
    if ($testConnection['driver'] === 'mysql') {
        $statement = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INTEGER PRIMARY KEY AUTO_INCREMENT', $statement);
        $statement .= ' ENGINE=InnoDB';
    }
    $capsule->getConnection()->statement($statement);
}
$db = $capsule->getConnection();
$db->table('ht_rooms')->insert(['id' => 1, 'name' => 'Synthetic room', 'images' => '[]', 'status' => 'published', 'price' => 100, 'number_of_rooms' => 5, 'max_adults' => 2, 'max_children' => 1, 'currency_id' => 1, 'tax_id' => 1]);
$db->table('ht_rooms')->insert(['id' => 2, 'name' => 'Draft room', 'status' => 'draft']);
$db->table('ht_taxes')->insert(['id' => 1, 'percentage' => 0]);
$db->table('ht_currencies')->insert(['id' => 1, 'title' => 'USD']);
$db->table('ht_services')->insert([['id' => 1, 'status' => 'published'], ['id' => 2, 'status' => 'draft']]);
$db->table('ht_customers')->insert(['id' => 42, 'first_name' => 'Private', 'last_name' => 'Guest', 'email' => 'private@example.invalid', 'phone' => '123456789']);
Carbon\Carbon::setTestNow('2026-10-04 12:00:00');

$results = [];
function check(bool $condition, string $message): void {
    if (! $condition) { throw new RuntimeException($message); }
}
function test(string $name, Closure $callback): void {
    global $results;
    try { $callback(); $results[$name] = 'PASS'; }
    catch (Throwable $exception) { $results[$name] = 'FAIL: ' . $exception->getMessage(); }
}
function rejects(Closure $callback): void {
    try { $callback(); } catch (InvalidArgumentException|RuntimeException $exception) { return; }
    throw new LogicException('Unsafe input was accepted.');
}
$fetcher = new class extends Botble\Hotel\Services\SafeCalendarFetcher {
    public array $addresses = ['93.184.216.34'];
    protected function resolveHost(string $host): array { return $this->addresses; }
};
foreach (['file:///etc/passwd', 'http://calendar.example/x', 'https://user:pass@calendar.example/x', 'https://calendar.example:8443/x', "https://calendar.example/\r\nx", 'https://127.0.0.1/x', 'https://10.0.0.1/x', 'https://169.254.169.254/x', 'https://[::1]/x', 'https://[fc00::1]/x', 'https://[::ffff:127.0.0.1]/x', 'https://[2002:7f00:1::]/x', 'https://2130706433/x', 'https://0177.0.0.1/x'] as $unsafe) {
    test('URL rejected: ' . trim($unsafe), fn () => rejects(fn () => $fetcher->validateUrl($unsafe)));
}
test('DNS private or mixed addresses rejected', function () use ($fetcher) {
    foreach ([['127.0.0.1'], ['93.184.216.34', '10.0.0.1'], []] as $addresses) {
        $fetcher->addresses = $addresses;
        rejects(fn () => $fetcher->validateUrl('https://calendar.example/x'));
    }
    $fetcher->addresses = ['93.184.216.34'];
});
test('Safe fetch pins DNS, verifies TLS and disables proxy/redirects', function () use ($fetcher) {
    check(str_contains($fetcher->fetch('https://calendar.example/feed.ics'), 'VCALENDAR'), 'Valid calendar failed.');
    $options = Botble\Hotel\Services\CalendarTransportDouble::$options;
    check($options[CURLOPT_RESOLVE] === ['calendar.example:443:93.184.216.34'], 'Destination not pinned.');
    check($options[CURLOPT_FOLLOWLOCATION] === false && $options[CURLOPT_PROXY] === '', 'Redirect/proxy bypass.');
    check($options[CURLOPT_SSL_VERIFYPEER] && $options[CURLOPT_SSL_VERIFYHOST] === 2, 'TLS disabled.');
});
test('Redirect, oversized body and non-calendar content rejected', function () use ($fetcher) {
    Botble\Hotel\Services\CalendarTransportDouble::$status = 302;
    rejects(fn () => $fetcher->fetch('https://calendar.example/x'));
    Botble\Hotel\Services\CalendarTransportDouble::$status = 200;
    Botble\Hotel\Services\CalendarTransportDouble::$body = str_repeat('X', $fetcher::MAX_BYTES + 1);
    rejects(fn () => $fetcher->fetch('https://calendar.example/x'));
    Botble\Hotel\Services\CalendarTransportDouble::$body = '<html>not a calendar</html>';
    rejects(fn () => $fetcher->fetch('https://calendar.example/x'));
});

test('Production admin RouteMatched keeps real CSRF middleware even with demo/legacy bypass config', function () use ($app, $events, $router) {
    $provider = new Botble\Base\Providers\EventServiceProvider($app);
    $provider->boot();
    $route = new Illuminate\Routing\Route(['POST'], 'admin/login', fn () => null);
    $request = Illuminate\Http\Request::create('https://hotel.example/admin/login', 'POST');
    $events->dispatch(new Illuminate\Routing\Events\RouteMatched($route, $request));
    check($app->make(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class) instanceof Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, 'CSRF replaced.');
});
test('Production POST without CSRF fails; valid CSRF succeeds', function () use ($app, $session) {
    $middleware = new Illuminate\Foundation\Http\Middleware\ValidateCsrfToken($app, new Illuminate\Encryption\Encrypter(str_repeat('a', 32), 'AES-256-CBC'));
    $request = Illuminate\Http\Request::create('/admin/login', 'POST');
    $request->setLaravelSession($session);
    try {
        $middleware->handle($request, fn () => new Symfony\Component\HttpFoundation\Response('ok'));
        throw new RuntimeException('Missing token accepted.');
    } catch (Illuminate\Session\TokenMismatchException) {}
    $request->request->set('_token', $session->token());
    check($middleware->handle($request, fn () => new Symfony\Component\HttpFoundation\Response('ok'))->getContent() === 'ok', 'Valid token rejected.');
});
test('Unauthenticated synthetic license API routes are absent', function () use ($source, $router) {
    Botble\Base\Facades\AdminHelper::swap(new class {
        public function registerRoutes(Closure $callback): void {
            Illuminate\Support\Facades\Route::group(['prefix' => 'admin', 'middleware' => ['auth']], $callback);
        }
    });
    require $source . '/platform/plugins/botble_nulled/routes/web.php';
    foreach ($router->getRoutes() as $route) {
        check(! str_starts_with($route->uri(), 'api/'), 'Public license route remains exposed.');
    }
});

$payload = ['token' => str_repeat('a', 32), 'room_id' => 1, 'start_date' => '20-10-2026', 'end_date' => '22-10-2026', 'rooms' => 1, 'number_of_guests' => 1, 'number_of_children' => 0, 'first_name' => 'Synthetic', 'last_name' => 'Guest', 'email' => 'synthetic@example.invalid', 'phone' => '00000000', 'terms_conditions' => 1];
test('Checkout validation rejects draft rooms, invalid dates, negative rooms and hidden services', function () use ($validator, $payload) {
    $rules = (new Botble\Hotel\Http\Requests\CheckoutRequest())->rules();
    foreach ([['room_id' => 2], ['end_date' => '20-10-2026'], ['end_date' => '19-10-2026'], ['rooms' => -1], ['services' => [2]], ['services' => [1, 1]]] as $bad) {
        check($validator->make(array_replace($payload, $bad), $rules)->fails(), 'Invalid checkout passed: ' . json_encode($bad));
    }
    check($validator->make($payload, $rules)->passes(), 'Valid checkout rejected.');
});
test('AJAX price validation rejects malformed dates and negative room count', function () use ($validator, $payload) {
    $rules = (new Botble\Hotel\Http\Requests\CalculateBookingAmountRequest())->rules();
    foreach ([['rooms' => -2], ['start_date' => 'garbage'], ['end_date' => $payload['start_date']]] as $bad) {
        check($validator->make(array_replace($payload, $bad), $rules)->fails(), 'Invalid price request passed.');
    }
});
test('Calendar form validates unsafe URLs before saving', function () use ($validator, $app, $fetcher) {
    $app->instance(Botble\Hotel\Services\SafeCalendarFetcher::class, $fetcher);
    $rules = (new Botble\Hotel\Http\Requests\RoomCalendarRequest())->rules();
    check($validator->make(['room_id' => 1, 'name' => 'Calendar', 'url' => 'file:///etc/passwd'], $rules)->fails(), 'Unsafe calendar form passed.');
    check($validator->make(['room_id' => 1, 'name' => 'Calendar', 'url' => 'https://calendar.example/feed.ics'], $rules)->passes(), 'Valid calendar form failed.');
});
test('Production cookies remain Secure and HttpOnly despite false environment overrides', function () use ($source) {
    $repository = Illuminate\Support\Env::getRepository();
    $repository->set('APP_ENV', 'production');
    $repository->set('SESSION_SECURE_COOKIE', 'false');
    $repository->set('SESSION_HTTP_ONLY', 'false');
    $config = require $source . '/config/session.php';
    check($config['secure'] === true && $config['http_only'] === true, 'Production cookies are insecure.');
});

function checkout(array $data, ?array $selection = null) {
    global $validator, $session, $lastCheckoutRequest;
    $session->put($data['token'], $selection ?? [
        'room_id' => $data['room_id'], 'start_date' => $data['start_date'], 'end_date' => $data['end_date'],
        'rooms' => $data['rooms'] ?? 1, 'adults' => $data['number_of_guests'] ?? 1, 'children' => $data['number_of_children'] ?? 0,
    ]);
    $session->put('checkout_token', $data['token']);
    $request = Botble\Hotel\Http\Requests\CheckoutRequest::create('/checkout', 'POST', $data);
    $validation = $validator->make($data, $request->rules());
    $validation->validate();
    $request->setValidator($validation);
    $lastCheckoutRequest = $request;
    $controller = new Botble\Hotel\Http\Controllers\PublicController(new Botble\Hotel\Services\GetRoomService());

    return $controller->postCheckout($request, new Botble\Base\Http\Responses\BaseHttpResponse());
}
test('Guest checkout cannot spoof status, customer, payment, currency or amount', function () use ($payload, $db) {
    checkout(array_merge($payload, ['status' => 'completed', 'customer_id' => 42, 'payment_id' => 123, 'currency_id' => 999, 'amount' => -1, 'booking_number' => 'INJECTED']));
    $booking = $db->table('ht_bookings')->orderByDesc('id')->first();
    check($booking->status === 'pending' && $booking->customer_id === null && $booking->payment_id === null, 'Protected fields spoofed.');
    check($booking->currency_id === 1 && (float) $booking->amount === 200.0 && $booking->booking_number !== 'INJECTED', 'Server totals/identity spoofed.');
});
test('Authenticated checkout always uses authenticated customer', function () use ($payload, $guard, $db) {
    $guard->customerId = 42;
    try { checkout(array_merge($payload, ['customer_id' => 999])); }
    finally { $guard->customerId = null; }
    check($db->table('ht_bookings')->orderByDesc('id')->value('customer_id') === 42, 'Wrong customer assigned.');
});
test('Checkout discards client-controlled payment callback URLs', function () use ($payload) {
    global $lastCheckoutRequest;
    checkout(array_replace($payload, ['return_url' => 'https://attacker.example/return', 'callback_url' => 'https://attacker.example/callback']));
    check($lastCheckoutRequest->input('return_url') === null && $lastCheckoutRequest->input('callback_url') === null, 'Untrusted payment return URLs remain.');
});
test('Checkout rechecks available inventory before creating a booking', function () use ($payload, $db) {
    $count = $db->table('ht_bookings')->count();
    try { checkout(array_replace($payload, ['rooms' => 9999])); throw new RuntimeException('Overbooking accepted.'); }
    catch (Illuminate\Validation\ValidationException) {}
    check($db->table('ht_bookings')->count() === $count, 'Rejected checkout wrote a booking.');
});
test('Failed address write rolls back booking and room records', function () use ($payload, $db) {
    global $session;
    $transaction = $session->get('booking_transaction_id');
    $bookings = $db->table('ht_bookings')->count();
    $rooms = $db->table('ht_booking_rooms')->count();
    $db->statement($db->getDriverName() === 'mysql'
        ? "CREATE TRIGGER fail_address BEFORE INSERT ON ht_booking_addresses FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'synthetic failure'"
        : "CREATE TRIGGER fail_address BEFORE INSERT ON ht_booking_addresses BEGIN SELECT RAISE(ABORT, 'synthetic failure'); END");
    try { checkout($payload); throw new RuntimeException('Expected database error.'); }
    catch (Illuminate\Database\QueryException) {}
    finally { $db->statement('DROP TRIGGER fail_address'); }
    check($db->table('ht_bookings')->count() === $bookings && $db->table('ht_booking_rooms')->count() === $rooms, 'Partial booking persisted.');
    check($session->get('booking_transaction_id') === $transaction, 'Failed transaction polluted the session.');
});
test('iCalendar export contains availability without guest PII or booking identifiers', function () {
    $content = (new Botble\Hotel\Services\ICalService())->generateICalContent(Botble\Hotel\Models\Room::query()->findOrFail(1));
    check(str_contains($content, 'BEGIN:VEVENT') && str_contains($content, 'SUMMARY:Reserved'), 'Availability events missing.');
    foreach (['Private', 'private@example.invalid', '123456789', 'Synthetic', 'TEST-', 'Customer:', 'Email:', 'Phone:', 'Booking ID:'] as $private) {
        check(! str_contains($content, $private), 'Private data leaked: ' . $private);
    }
});
test('Special nightly rates multiply by room quantity', function () {
    $room = Botble\Hotel\Models\Room::query()->findOrFail(1);
    $room->setRelation('activeRoomDates', new Illuminate\Database\Eloquent\Collection([(object) ['active' => true, 'number_of_rooms' => 5, 'value' => 150, 'value_type' => 'fixed', 'start_date' => '2026-10-20']]));
    check((float) $room->getRoomTotalPrice('2026-10-20', '2026-10-22', 2) === 500.0, 'Incorrect multi-room special price.');
});
test('JWT signed token verifies and altered payload is rejected', function () {
    $key = str_repeat('k', 64);
    $token = Firebase\JWT\JWT::encode(['sub' => 'synthetic', 'iat' => time(), 'exp' => time() + 300], $key, 'HS256');
    check(Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($key, 'HS256'))->sub === 'synthetic', 'JWT verification failed.');
    $parts = explode('.', $token);
    $parts[1] = rtrim(strtr(base64_encode(json_encode(['sub' => 'altered', 'exp' => time() + 300])), '+/', '-_'), '=');
    try {
        Firebase\JWT\JWT::decode(implode('.', $parts), new Firebase\JWT\Key($key, 'HS256'));
        throw new RuntimeException('Altered token verified.');
    } catch (Firebase\JWT\SignatureInvalidException) {}
    check(method_exists(Firebase\JWT\JWT::class, 'urlsafeB64Decode'), 'Apple JWT header decoding API missing.');
});

if (! getenv('HOTEL_SECURITY_ONLY')) {
    require __DIR__ . '/BackendCases.php';
}

if (defined('HOTEL_INTEGRATION_TESTS')) {
    require dirname(__DIR__) . '/Integration/Cases.php';
}

Carbon\Carbon::setTestNow();
if (defined('HOTEL_INTEGRATION_TESTS')) {
    $resultFile = defined('HOTEL_CALENDAR_FIX_TESTS') ? 'CALENDAR_FIX_RESULTS_2026-10-05.json' : 'INTEGRATION_RESULTS_2026-10-05.json';
    file_put_contents(getenv('HOTEL_TEST_RESULT_PATH') ?: dirname($source) . '/docs/' . $resultFile, json_encode([
        'tests' => $results,
        'evidence' => $GLOBALS['integrationEvidence'] ?? [],
        'scope' => 'Isolated minimal schema; real InnoDB and local provider doubles; no live payment APIs',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit(count(array_filter($results, fn ($result) => str_starts_with($result, 'FAIL'))) ? 1 : 0);
