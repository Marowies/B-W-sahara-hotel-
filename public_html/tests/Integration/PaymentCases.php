<?php

// Service/controller contract tests only: SDK transport is replaced; no provider API is contacted.
foreach (['paypal' => 'PayPal', 'stripe' => 'Stripe'] as $directory => $namespace) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . '/platform/plugins/' . $directory . '/src')) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relative = substr($file->getPathname(), strlen($source . '/platform/plugins/' . $directory . '/src/'), -4);
            $loader->addClassMap(['Botble\\' . $namespace . '\\' . str_replace(['/', '\\'], '\\', $relative) => $file->getPathname()]);
        }
    }
    require_once $source . '/platform/plugins/' . $directory . '/helpers/constants.php';
}
require_once $source . '/platform/plugins/payment/helpers/constants.php';

foreach (['charge_id TEXT', 'status TEXT', 'order_id INTEGER', 'amount REAL', 'currency TEXT', 'payment_channel TEXT', 'customer_id INTEGER', 'customer_type TEXT', 'payment_fee REAL', 'created_at TEXT', 'updated_at TEXT'] as $column) {
    $db->statement('ALTER TABLE payments ADD COLUMN ' . $column);
}
$receiptMigration = require $source . '/platform/plugins/payment/database/migrations/2026_10_05_000002_create_payment_webhook_receipts.php';
$receiptMigration->up();

class FixturePayPalClient extends Botble\PayPal\Services\Core\PayPalHttpClient
{
    public string $status = 'COMPLETED';
    public string $orderId = 'SYNTHETIC-PAYPAL-ORDER';
    public string $amount = '200.00';
    public string $currency = 'USD';
    public string $captureStatus = 'COMPLETED';
    public int $requests = 0;
    public ?PayPalHttp\HttpRequest $lastRequest = null;
    public function __construct() {}
    public function execute(PayPalHttp\HttpRequest $request) {
        $this->requests++;
        $this->lastRequest = $request;
        return (object) ['statusCode' => 201, 'result' => (object) [
            'id' => $this->orderId, 'status' => $this->status,
            'links' => [(object) ['rel' => 'approve', 'href' => 'https://provider.example.invalid/approve']],
            'purchase_units' => [(object) ['amount' => (object) ['value' => $this->amount, 'currency_code' => $this->currency],
                'payments' => (object) ['captures' => [(object) ['id' => 'SYNTHETIC-CAPTURE', 'status' => $this->captureStatus,
                    'amount' => (object) ['value' => $this->amount, 'currency_code' => $this->currency]]]]]],
        ]];
    }
}

function paypalPending(): void
{
    global $db, $session;
    $db->table('payments')->where('id', 801)->delete();
    $db->table('payments')->insert(['id' => 801, 'charge_id' => 'SYNTHETIC-PAYPAL-ORDER', 'status' => 'pending', 'order_id' => 1, 'amount' => 200, 'currency' => 'USD', 'payment_channel' => 'paypal']);
    $session->put('paypal_payment_id', 'SYNTHETIC-PAYPAL-ORDER');
}

class FixturePayPalService extends Botble\PayPal\Services\Gateways\PayPalPaymentService
{
    public FixturePayPalClient $fixtureClient;
    public function __construct() { $this->client = $this->fixtureClient = new FixturePayPalClient(); $this->paymentCurrency = 'USD'; $this->totalAmount = 0; $this->itemList = []; }
}

test('Payment mock: incomplete PayPal capture is rejected', function () use ($session): void {
    paypalPending();
    $service = new FixturePayPalService();
    $service->fixtureClient->status = 'PENDING';
    check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER'])) === false, 'Incomplete capture accepted.');
});

test('Payment mock: PayPal callback token must match the session order', function () use ($session): void {
    paypalPending();
    $service = new FixturePayPalService();
    check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'DIFFERENT-ORDER'])) === false, 'Mismatched callback token accepted.');
});

test('Payment mock: PayPal completion cannot credit a client-supplied amount/currency/order', function () use ($session): void {
    paypalPending();
    $service = new FixturePayPalService();
    $data = ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER', 'amount' => 0.01, 'currency' => 'EUR', 'order_id' => 99999];
    $GLOBALS['integrationActions'] = [];
    check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', $data)) !== false, 'Fixture completed capture failed.');
    $service->afterMakePayment($data);
    $credited = array_values(array_filter($GLOBALS['integrationActions'], fn ($action) => $action[0] === PAYMENT_ACTION_PAYMENT_PROCESSED));
    check(count($credited) === 1 && (float) $credited[0][1]['amount'] === 200.0 && $credited[0][1]['currency'] === 'USD' && $credited[0][1]['order_id'] === [1], 'Completion does not use the stored payment binding.');
});

function get_payment_setting($key, $method = null) { return $key === 'webhook_secret' && $method === 'stripe' ? 'whsec_synthetic_local_fixture' : null; }
$responseViews = new Illuminate\View\Factory(new Illuminate\View\Engines\EngineResolver(), new Illuminate\View\FileViewFinder(new Illuminate\Filesystem\Filesystem(), []), $events);
$app->instance(Illuminate\Contracts\Routing\ResponseFactory::class, new Illuminate\Routing\ResponseFactory($responseViews, new Illuminate\Routing\Redirector($url)));

function stripeFixture(string $signatureMode = 'valid', int $amount = 20000, array $overrides = [], string $eventId = 'evt_synthetic', int $timestampOffset = 0): Illuminate\Http\Request
{
    $body = json_encode(['id' => $eventId, 'object' => 'event', 'type' => 'payment_intent.succeeded', 'data' => ['object' => array_replace(['id' => 'pi_synthetic', 'object' => 'payment_intent', 'amount' => $amount, 'amount_received' => $amount, 'currency' => 'usd', 'status' => 'succeeded'], $overrides)]]);
    $time = time() + $timestampOffset;
    $signature = hash_hmac('sha256', $time . '.' . $body, $signatureMode === 'valid' ? 'whsec_synthetic_local_fixture' : 'wrong-secret');

    return Illuminate\Http\Request::create('/stripe/webhook', 'POST', [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t=$time,v1=$signature"], $body);
}

function stripePending(): void
{
    global $db;
    $db->table('payments')->insert(['id' => 800, 'charge_id' => 'pi_synthetic', 'status' => 'pending', 'order_id' => 1, 'amount' => 200, 'currency' => 'USD', 'payment_channel' => 'stripe']);
}

transactionTest('Payment mock: invalid Stripe webhook signature leaves payment pending', function () use ($db): void {
    stripePending();
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture('invalid'));
    check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Forged webhook completes payment.');
});

transactionTest('Payment mock: valid signed Stripe webhook completes the matching payment', function () use ($db): void {
    stripePending();
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture());
    check($db->table('payments')->where('id', 800)->value('status') === 'completed', 'Valid signed webhook ignored.');
});

transactionTest('Payment mock: replayed Stripe event emits completion only once', function (): void {
    stripePending();
    $GLOBALS['integrationActions'] = [];
    $controller = new Botble\Stripe\Http\Controllers\StripeController();
    $controller->webhook(stripeFixture());
    $controller->webhook(stripeFixture());
    $completions = array_filter($GLOBALS['integrationActions'], fn ($action) => $action[0] === PAYMENT_ACTION_PAYMENT_PROCESSED);
    check(count($completions) === 1, 'Webhook replay re-emits completion actions.');
});

transactionTest('Payment mock: Stripe amount mismatch does not complete the stored payment', function () use ($db): void {
    stripePending();
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture(amount: 1));
    check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Amount mismatch accepted without reconciliation.');
});

foreach (['orderId' => 'WRONG-ORDER', 'amount' => '0.01', 'currency' => 'EUR', 'captureStatus' => 'PENDING'] as $field => $value) {
    transactionTest('Payment: PayPal rejects provider mismatch in ' . $field, function () use ($field, $value, $db): void {
        paypalPending();
        $service = new FixturePayPalService();
        $service->fixtureClient->$field = $value;
        check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER'])) === false, 'Provider mismatch accepted.');
        check($service->afterMakePayment(['order_id' => 99999]) === null, 'Rejected capture can still complete.');
        check($db->table('payments')->where('id', 801)->value('status') === 'pending', 'Rejected capture changed status.');
    });
}

transactionTest('Payment: PayPal mismatched token causes no provider request', function (): void {
    paypalPending();
    $service = new FixturePayPalService();
    $service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'wrong']));
    check($service->fixtureClient->requests === 0, 'Provider contacted for a mismatched token.');
});

transactionTest('Payment: PayPal cannot complete without stored binding or verification', function () use ($db): void {
    paypalPending();
    $service = new FixturePayPalService();
    check($service->afterMakePayment(['amount' => 200, 'currency' => 'USD', 'order_id' => 1]) === null, 'Direct completion bypass accepted.');
    $db->table('payments')->where('id', 801)->delete();
    check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER'])) === false, 'Missing stored binding accepted.');
});

transactionTest('Payment: PayPal binding cannot change between verification and completion', function () use ($db): void {
    paypalPending();
    $service = new FixturePayPalService();
    check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER'])) !== false, 'Valid capture failed.');
    $db->table('payments')->where('id', 801)->update(['order_id' => 99999]);
    check($service->afterMakePayment([]) === null, 'Changed binding completed.');
    check($db->table('payments')->where('id', 801)->value('status') === 'pending', 'Changed binding marked paid.');
});

transactionTest('Payment: PayPal replay emits one completion', function () use ($session): void {
    paypalPending();
    $GLOBALS['integrationActions'] = [];
    $request = Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER']);
    $service = new FixturePayPalService();
    $service->getPaymentStatus($request);$service->afterMakePayment([]);
    $session->put('paypal_payment_id', 'SYNTHETIC-PAYPAL-ORDER');
    $other = new FixturePayPalService();$other->getPaymentStatus($request);$other->afterMakePayment([]);
    check(count(array_filter($GLOBALS['integrationActions'], fn ($action) => $action[0] === PAYMENT_ACTION_PAYMENT_PROCESSED)) === 1, 'Replay repeats fulfillment.');
    check($other->fixtureClient->requests === 0, 'Replay recaptures payment.');
});

transactionTest('Payment: PayPal creation persists expected booking and emits no money in callback URL', function () use ($db): void {
    Botble\Theme\Facades\Theme::swap(new class { public function getSiteTitle() { return 'Synthetic hotel'; } });
    $service = new FixturePayPalService();
    $service->fixtureClient->orderId = 'SYNTHETIC-NEW-ORDER';
    $url = $service->makePayment(['amount' => 200, 'currency' => 'USD', 'order_id' => [1], 'description' => 'Synthetic stay', 'return_url' => '/cancel', 'callback_url' => 'https://hotel.example.invalid/callback']);
    check($url === 'https://provider.example.invalid/approve', 'Creation redirect failed.');
    $payment = $db->table('payments')->where('charge_id', 'SYNTHETIC-NEW-ORDER')->first();
    check($payment && $payment->order_id == 1 && (float) $payment->amount === 200.0 && $payment->status === 'pending' && $payment->payment_channel === 'paypal', 'Server binding not persisted.');
    check($service->fixtureClient->lastRequest->body['application_context']['return_url'] === 'https://hotel.example.invalid/callback', 'Money or booking data leaked into callback URL.');
});

foreach (['currency' => 'eur', 'id' => 'pi_unknown', 'status' => 'processing', 'amount_received' => 19999, 'object' => 'charge'] as $field => $value) {
    transactionTest('Payment: Stripe rejects mismatch in ' . $field, function () use ($field, $value, $db): void {
        stripePending();
        $GLOBALS['integrationActions'] = [];
        (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture(overrides: [$field => $value]));
        check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Mismatched intent completed.');
        check($db->table('payment_webhook_receipts')->count() === 0, 'Rejected intent consumed its event ID.');
        check(! array_filter($GLOBALS['integrationActions'], fn ($action) => $action[0] === PAYMENT_ACTION_PAYMENT_PROCESSED), 'Rejected intent emitted fulfillment.');
    });
}

transactionTest('Payment: Stripe event with expired signature is rejected', function () use ($db): void {
    stripePending();
    check((new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture(timestampOffset: -600))->getStatusCode() === 400, 'Expired signature accepted.');
    check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Expired event changed payment.');
});

transactionTest('Payment: Stripe cannot match a different payment channel', function () use ($db): void {
    stripePending();$db->table('payments')->where('id', 800)->update(['payment_channel' => 'paypal']);
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture());
    check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Other provider payment completed.');
});

transactionTest('Payment: distinct Stripe event IDs cannot fulfill the same intent twice', function () use ($db): void {
    stripePending();$GLOBALS['integrationActions'] = [];
    $controller = new Botble\Stripe\Http\Controllers\StripeController();
    $controller->webhook(stripeFixture());$controller->webhook(stripeFixture(eventId: 'evt_second'));
    check($db->table('payment_webhook_receipts')->count() === 2, 'Distinct successful events not recorded.');
    check(count(array_filter($GLOBALS['integrationActions'], fn ($action) => $action[0] === PAYMENT_ACTION_PAYMENT_PROCESSED)) === 1, 'Distinct event repeats fulfillment.');
});

transactionTest('Payment: Stripe missing local binding requests provider retry', function (): void {
    check((new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture())->getStatusCode() === 503, 'Early event silently dropped.');
});

transactionTest('Payment: Stripe never restores a refunded payment to completed', function () use ($db): void {
    stripePending();$db->table('payments')->where('id', 800)->update(['status' => 'refunded']);
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture());
    check($db->table('payments')->where('id', 800)->value('status') === 'refunded', 'Refunded state overwritten.');
});

transactionTest('Payment: Stripe zero-decimal currency compares integer units', function () use ($db): void {
    stripePending();$db->table('payments')->where('id', 800)->update(['currency' => 'JPY']);
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture(amount: 200, overrides: ['currency' => 'jpy']));
    check($db->table('payments')->where('id', 800)->value('status') === 'completed', 'Zero-decimal conversion incorrect.');
});

transactionTest('Payment: Stripe matches the stored legacy charge identity from the signed intent', function () use ($db): void {
    stripePending();$db->table('payments')->where('id', 800)->update(['charge_id' => 'ch_synthetic']);
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture(overrides: ['latest_charge' => 'ch_synthetic']));
    check($db->table('payments')->where('id', 800)->value('status') === 'completed', 'Legacy charge binding ignored.');
});

transactionTest('Payment: Stripe fulfillment failure rolls back both status and event receipt', function () use ($db): void {
    stripePending();
    $GLOBALS['integrationActionHandler'] = function ($name): void { if ($name === PAYMENT_ACTION_PAYMENT_PROCESSED) { throw new RuntimeException('synthetic fulfillment failure'); } };
    try {
        try { (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture());throw new LogicException('Expected fulfillment failure.'); }
        catch (RuntimeException $exception) { check($exception->getMessage() === 'synthetic fulfillment failure', 'Unexpected failure.'); }
    } finally { unset($GLOBALS['integrationActionHandler']); }
    check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Failed fulfillment leaves completed payment.');
    check($db->table('payment_webhook_receipts')->count() === 0, 'Failed fulfillment consumes event ID.');
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture());
    check($db->table('payments')->where('id', 800)->value('status') === 'completed', 'Retry could not complete.');
});

transactionTest('Payment: PayPal fulfillment failure rolls back status and preserves retry binding', function () use ($db, $session): void {
    paypalPending();$service = new FixturePayPalService();
    $service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER']));
    $GLOBALS['integrationActionHandler'] = function ($name): void { if ($name === PAYMENT_ACTION_PAYMENT_PROCESSED) { throw new RuntimeException('synthetic fulfillment failure'); } };
    try {
        try { $service->afterMakePayment([]);throw new LogicException('Expected fulfillment failure.'); }
        catch (RuntimeException $exception) { check($exception->getMessage() === 'synthetic fulfillment failure', 'Unexpected failure.'); }
    } finally { unset($GLOBALS['integrationActionHandler']); }
    check($db->table('payments')->where('id', 801)->value('status') === 'pending' && $session->get('paypal_payment_id') === 'SYNTHETIC-PAYPAL-ORDER', 'Failure destroys retry binding.');
    check($service->afterMakePayment([]) === 'SYNTHETIC-PAYPAL-ORDER', 'Retry could not complete.');
});

test('Payment: receipt migration rolls back and reapplies', function () use ($receiptMigration, $db): void {
    $receiptMigration->down();check(! $db->getSchemaBuilder()->hasTable('payment_webhook_receipts'), 'Receipt rollback failed.');
    $receiptMigration->up();check($db->getSchemaBuilder()->hasTable('payment_webhook_receipts'), 'Receipt reapply failed.');
});

if ($db->getDriverName() === 'mysql') {
    require __DIR__ . '/PaymentConcurrencyCases.php';
}
