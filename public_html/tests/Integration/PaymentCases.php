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

class FixturePayPalClient extends Botble\PayPal\Services\Core\PayPalHttpClient
{
    public string $status = 'COMPLETED';
    public function __construct() {}
    public function execute(PayPalHttp\HttpRequest $request) {
        return (object) ['statusCode' => 201, 'result' => (object) [
            'id' => 'SYNTHETIC-PAYPAL-ORDER', 'status' => $this->status,
            'purchase_units' => [(object) ['amount' => (object) ['value' => '200.00', 'currency_code' => 'USD']]],
        ]];
    }
}

class FixturePayPalService extends Botble\PayPal\Services\Gateways\PayPalPaymentService
{
    public FixturePayPalClient $fixtureClient;
    public function __construct() { $this->client = $this->fixtureClient = new FixturePayPalClient(); }
}

test('Payment mock: incomplete PayPal capture is rejected', function () use ($session): void {
    $session->put('paypal_payment_id', 'SYNTHETIC-PAYPAL-ORDER');
    $service = new FixturePayPalService();
    $service->fixtureClient->status = 'PENDING';
    check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER'])) === false, 'Incomplete capture accepted.');
});

test('Payment mock: PayPal callback token must match the session order', function () use ($session): void {
    $session->put('paypal_payment_id', 'SYNTHETIC-PAYPAL-ORDER');
    $service = new FixturePayPalService();
    check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', ['PayerID' => 'synthetic', 'token' => 'DIFFERENT-ORDER'])) === false, 'Mismatched callback token accepted.');
});

test('Payment mock: PayPal completion cannot credit a client-supplied amount/currency/order', function () use ($session): void {
    $session->put('paypal_payment_id', 'SYNTHETIC-PAYPAL-ORDER');
    $service = new FixturePayPalService();
    $data = ['PayerID' => 'synthetic', 'token' => 'SYNTHETIC-PAYPAL-ORDER', 'amount' => 0.01, 'currency' => 'EUR', 'order_id' => 99999];
    $GLOBALS['integrationActions'] = [];
    check($service->getPaymentStatus(Illuminate\Http\Request::create('/callback', 'GET', $data)) !== false, 'Fixture completed capture failed.');
    $service->afterMakePayment($data);
    $credited = array_values(array_filter($GLOBALS['integrationActions'], fn ($action) => $action[0] === PAYMENT_ACTION_PAYMENT_PROCESSED));
    check(! $credited || ((float) $credited[0][1]['amount'] === 200.0 && $credited[0][1]['currency'] === 'USD' && $credited[0][1]['order_id'] !== [99999]), 'Untrusted callback fields are forwarded as completed payment data.');
});

function get_payment_setting($key, $method = null) { return $key === 'webhook_secret' && $method === 'stripe' ? 'whsec_synthetic_local_fixture' : null; }
$responseViews = new Illuminate\View\Factory(new Illuminate\View\Engines\EngineResolver(), new Illuminate\View\FileViewFinder(new Illuminate\Filesystem\Filesystem(), []), $events);
$app->instance(Illuminate\Contracts\Routing\ResponseFactory::class, new Illuminate\Routing\ResponseFactory($responseViews, new Illuminate\Routing\Redirector($url)));
foreach (['charge_id TEXT', 'status TEXT', 'order_id INTEGER', 'amount REAL', 'currency TEXT', 'created_at TEXT', 'updated_at TEXT'] as $column) {
    $db->statement('ALTER TABLE payments ADD COLUMN ' . $column);
}

function stripeFixture(string $signatureMode = 'valid', int $amount = 20000): Illuminate\Http\Request
{
    $body = json_encode(['id' => 'evt_synthetic', 'object' => 'event', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_synthetic', 'object' => 'payment_intent', 'amount' => $amount, 'amount_received' => $amount, 'currency' => 'usd', 'status' => 'succeeded']]]);
    $time = time();
    $signature = hash_hmac('sha256', $time . '.' . $body, $signatureMode === 'valid' ? 'whsec_synthetic_local_fixture' : 'wrong-secret');

    return Illuminate\Http\Request::create('/stripe/webhook', 'POST', [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t=$time,v1=$signature"], $body);
}

function stripePending(): void
{
    global $db;
    $db->table('payments')->insert(['id' => 800, 'charge_id' => 'pi_synthetic', 'status' => 'pending', 'order_id' => 1, 'amount' => 200, 'currency' => 'USD']);
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
