<?php

use Botble\Stripe\Supports\CheckoutPaymentVerifier;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;

$checkoutMigration = require $source . '/platform/plugins/payment/database/migrations/2026_10_07_150000_create_stripe_checkout_bindings.php';
$checkoutMigration->up();

function checkoutObjects(array $sessionChanges = [], array $intentChanges = []): array
{
    return [CheckoutSession::constructFrom(array_replace([
        'id' => 'cs_synthetic', 'object' => 'checkout.session', 'mode' => 'payment',
        'status' => 'complete', 'payment_status' => 'paid', 'amount_total' => 20000,
        'currency' => 'usd', 'payment_intent' => 'pi_synthetic',
        // Deliberately false metadata must never override the database binding.
        'metadata' => ['amount' => '0.01', 'order_id' => '[999]', 'customer_id' => '999'],
    ], $sessionChanges)), PaymentIntent::constructFrom(array_replace([
        'id' => 'pi_synthetic', 'object' => 'payment_intent', 'status' => 'succeeded',
        'amount' => 20000, 'amount_received' => 20000, 'currency' => 'usd', 'latest_charge' => 'ch_synthetic',
    ], $intentChanges))];
}

function checkoutPending(): void
{
    global $db;
    stripePending();
    $db->table('payments')->where('id', 800)->update(['charge_id' => 'cs_synthetic']);
    $db->table('stripe_checkout_bindings')->insert(['session_id' => 'cs_synthetic', 'payment_id' => 800, 'created_at' => now()]);
}

transactionTest('Checkout: persisted total and booking override provider metadata', function () use ($db): void {
    checkoutPending(); $GLOBALS['integrationActions'] = [];
    check((new CheckoutPaymentVerifier())->complete(...checkoutObjects()) === 'ch_synthetic', 'Charge not bound.');
    $actions = array_values(array_filter($GLOBALS['integrationActions'], fn ($a) => $a[0] === PAYMENT_ACTION_PAYMENT_PROCESSED));
    check(count($actions) === 1 && (float) $actions[0][1]['amount'] === 200.0 && $actions[0][1]['order_id'] === [1], 'Metadata changed credit.');
    check($db->table('payments')->where('id', 800)->value('status') === 'completed', 'Verified checkout not completed.');
});

foreach ([['id', 'cs_unknown'], ['amount_total', 1], ['currency', 'eur'], ['payment_status', 'unpaid'],
    ['status', 'open'], ['mode', 'subscription'], ['payment_intent', 'pi_other']] as [$field, $value]) {
    transactionTest('Checkout: rejects session mismatch ' . $field, function () use ($db, $field, $value): void {
        checkoutPending(); $GLOBALS['integrationActions'] = [];
        try { (new CheckoutPaymentVerifier())->complete(...checkoutObjects([$field => $value])); throw new LogicException('Mismatch accepted.'); }
        catch (UnexpectedValueException) {}
        check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Mismatch completed.');
        check(! $GLOBALS['integrationActions'], 'Mismatch fulfilled.');
    });
}
foreach ([['id', 'pi_other'], ['amount', 1], ['amount_received', 1], ['currency', 'eur'],
    ['status', 'processing'], ['latest_charge', null]] as [$field, $value]) {
    transactionTest('Checkout: rejects intent mismatch ' . $field, function () use ($db, $field, $value): void {
        checkoutPending();
        try { (new CheckoutPaymentVerifier())->complete(...checkoutObjects([], [$field => $value])); throw new LogicException('Mismatch accepted.'); }
        catch (UnexpectedValueException) {}
        check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Mismatch completed.');
    });
}

transactionTest('Checkout: return replay and subsequent intent webhook fulfill once', function (): void {
    checkoutPending(); $GLOBALS['integrationActions'] = [];
    $verifier = new CheckoutPaymentVerifier();
    $verifier->complete(...checkoutObjects()); $verifier->complete(...checkoutObjects());
    (new Botble\Stripe\Http\Controllers\StripeController())->webhook(stripeFixture(overrides: ['latest_charge' => 'ch_synthetic']));
    check(count(array_filter($GLOBALS['integrationActions'], fn ($a) => $a[0] === PAYMENT_ACTION_PAYMENT_PROCESSED)) === 1, 'Repeated fulfillment.');
});
transactionTest('Checkout: refunded local state is preserved', function () use ($db): void {
    checkoutPending(); $db->table('payments')->where('id', 800)->update(['status' => 'refunded']);
    try { (new CheckoutPaymentVerifier())->complete(...checkoutObjects()); throw new LogicException('Refund restored.'); }
    catch (UnexpectedValueException) {}
    check($db->table('payments')->where('id', 800)->value('status') === 'refunded', 'Refund lost.');
});
transactionTest('Checkout: failed fulfillment rolls back status and charge for retry', function () use ($db): void {
    checkoutPending();
    $GLOBALS['integrationActionHandler'] = function ($name): void { if ($name === PAYMENT_ACTION_PAYMENT_PROCESSED) throw new RuntimeException('synthetic failure'); };
    try {
        try { (new CheckoutPaymentVerifier())->complete(...checkoutObjects()); throw new LogicException('Failure expected.'); }
        catch (RuntimeException $e) { check($e->getMessage() === 'synthetic failure', 'Unexpected error.'); }
    } finally { unset($GLOBALS['integrationActionHandler']); }
    check($db->table('payments')->where('id', 800)->value('status') === 'pending'
        && $db->table('payments')->where('id', 800)->value('charge_id') === 'cs_synthetic', 'Rollback failed.');
    (new CheckoutPaymentVerifier())->complete(...checkoutObjects());
});

class FixtureCheckoutService extends Botble\Stripe\Services\Gateways\StripePaymentService
{
    public int $calls = 0;
    public function setClient(): bool { return true; }
    public function isStripeApiCharge(): bool { return false; }
    public function units(float $amount, string $currency): int { $this->currency = $currency; return $this->convertAmount($amount); }
    protected function createCheckoutSession(array $data): CheckoutSession
    {
        $this->calls++;
        return CheckoutSession::constructFrom(['id' => 'cs_created', 'url' => 'https://checkout.example.invalid/synthetic']);
    }
}
$router->get('stripe/success', fn () => null)->name('payments.stripe.success');
$router->get('stripe/error', fn () => null)->name('payments.stripe.error');
$router->getRoutes()->refreshNameLookups();

transactionTest('Checkout: creation persists binding before returning provider URL', function () use ($db): void {
    $service = new FixtureCheckoutService();
    $result = $service->makePayment(['amount' => 200, 'currency' => 'USD', 'order_id' => [1],
        'products' => [['id' => 1, 'name' => 'Synthetic stay', 'price_per_order' => 200, 'qty' => 1]]]);
    $binding = $db->table('stripe_checkout_bindings')->where('session_id', 'cs_created')->first();
    check($result === 'https://checkout.example.invalid/synthetic' && $binding, 'No local binding.');
    check($db->table('payments')->where('id', $binding->payment_id)->value('status') === 'pending', 'Creation fulfilled prematurely.');
});
transactionTest('Checkout: mismatched line items stop before provider request', function (): void {
    $service = new FixtureCheckoutService();
    try { $service->makePayment(['amount' => 200, 'currency' => 'USD', 'order_id' => [1],
        'products' => [['id' => 1, 'name' => 'Synthetic', 'price_per_order' => 100, 'qty' => 1]]]); throw new LogicException('Mismatch accepted.'); }
    catch (UnexpectedValueException) {}
    check($service->calls === 0, 'Provider called with wrong total.');
});
test('Checkout: decimal amounts are independent of display settings', function (): void {
    $service = new FixtureCheckoutService();
    check($service->units(112.2, 'USD') === 11220 && $service->units(200, 'JPY') === 200, 'Bad minor units.');
    try { $service->units(1.001, 'USD'); throw new LogicException('Precision accepted.'); } catch (UnexpectedValueException) {}
});
test('Checkout: binding migration rolls back and reapplies', function () use ($checkoutMigration, $db): void {
    $checkoutMigration->down(); check(! $db->getSchemaBuilder()->hasTable('stripe_checkout_bindings'), 'Rollback failed.');
    $checkoutMigration->up(); check($db->getSchemaBuilder()->hasTable('stripe_checkout_bindings'), 'Reapply failed.');
});

class FixtureChargeService extends Botble\Stripe\Services\Gateways\StripePaymentService
{
    public array $changes = [];
    public function getPaymentDetails(string $chargeId): ?Stripe\Charge
    {
        return Stripe\Charge::constructFrom(array_replace(['id' => $chargeId, 'object' => 'charge',
            'amount' => 20000, 'currency' => 'usd', 'paid' => true, 'status' => 'succeeded'], $this->changes));
    }
}
transactionTest('Charge: local expectations override completion arguments and replay once', function () use ($db): void {
    stripePending(); $db->table('payments')->where('id', 800)->update(['charge_id' => 'ch_synthetic']);
    $GLOBALS['integrationActions'] = []; $service = new FixtureChargeService();
    check($service->afterMakePayment('ch_synthetic', ['amount' => 0.01, 'order_id' => [999]]) === 'ch_synthetic', 'Charge rejected.');
    $service->afterMakePayment('ch_synthetic', []);
    $actions = array_values(array_filter($GLOBALS['integrationActions'], fn ($a) => $a[0] === PAYMENT_ACTION_PAYMENT_PROCESSED));
    check(count($actions) === 1 && (float) $actions[0][1]['amount'] === 200.0 && $actions[0][1]['order_id'] === [1], 'Charge args or replay changed fulfillment.');
});
foreach ([['amount', 1], ['currency', 'eur'], ['paid', false], ['status', 'pending'], ['id', 'ch_other']] as [$field, $value]) {
    transactionTest('Charge: rejects mismatch ' . $field, function () use ($db, $field, $value): void {
        stripePending(); $db->table('payments')->where('id', 800)->update(['charge_id' => 'ch_synthetic']);
        $service = new FixtureChargeService(); $service->changes = [$field => $value];
        check($service->afterMakePayment('ch_synthetic', []) === null, 'Mismatch completed.');
        check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'State changed.');
    });
}
transactionTest('Charge: missing local binding cannot fulfill', function (): void {
    check((new FixtureChargeService())->afterMakePayment('ch_unknown', ['amount' => 200, 'order_id' => [1]]) === null, 'Unbound charge fulfilled.');
});

class FixtureCheckoutController extends Botble\Stripe\Http\Controllers\StripeController
{
    protected function configureWebhookClient(): bool { return true; }
    protected function retrieveIntent(string $id): PaymentIntent { return checkoutObjects()[1]; }
}
function checkoutEvent(string $type, array $changes = [], bool $validSignature = true): Illuminate\Http\Request
{
    $body = json_encode(['id' => 'evt_checkout', 'object' => 'event', 'type' => $type,
        'data' => ['object' => checkoutObjects($changes)[0]->toArray()]]);
    $time = time(); $signature = hash_hmac('sha256', $time . '.' . $body,
        $validSignature ? 'whsec_synthetic_local_fixture' : 'wrong-secret');
    return Illuminate\Http\Request::create('/stripe/webhook', 'POST', [], [], [],
        ['HTTP_STRIPE_SIGNATURE' => "t=$time,v1=$signature"], $body);
}
foreach (['checkout.session.completed', 'checkout.session.async_payment_succeeded'] as $type) {
    transactionTest('Checkout webhook: signed ' . $type . ' fulfills once without browser return', function () use ($db, $type): void {
        checkoutPending(); $GLOBALS['integrationActions'] = []; $controller = new FixtureCheckoutController();
        check($controller->webhook(checkoutEvent($type))->getStatusCode() === 204, 'Webhook failed.');
        $controller->webhook(checkoutEvent($type));
        check($db->table('payments')->where('id', 800)->value('status') === 'completed', 'No completion.');
        check(count(array_filter($GLOBALS['integrationActions'], fn ($a) => $a[0] === PAYMENT_ACTION_PAYMENT_PROCESSED)) === 1, 'Repeated fulfillment.');
    });
}
transactionTest('Checkout webhook: unpaid session stays pending', function () use ($db): void {
    checkoutPending();
    (new FixtureCheckoutController())->webhook(checkoutEvent('checkout.session.completed', ['payment_status' => 'unpaid']));
    check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Unpaid completed.');
});
transactionTest('Checkout webhook: forged signature stays pending', function () use ($db): void {
    checkoutPending();
    check((new FixtureCheckoutController())->webhook(checkoutEvent('checkout.session.completed', [], false))->getStatusCode() === 400, 'Forged signature accepted.');
    check($db->table('payments')->where('id', 800)->value('status') === 'pending', 'Forged completion.');
});
