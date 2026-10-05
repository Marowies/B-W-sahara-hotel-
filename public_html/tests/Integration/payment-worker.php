<?php

// A separate process and connection exercise InnoDB locks, not sequential mock calls.
$loader->addPsr4('Botble\\Stripe\\', $source . '/platform/plugins/stripe/src', true);
require $source . '/platform/plugins/stripe/helpers/constants.php';
require $source . '/platform/plugins/payment/helpers/constants.php';
function get_payment_setting($key, $method = null) { return $key === 'webhook_secret' && $method === 'stripe' ? 'whsec_synthetic_local_fixture' : null; }
$views = new Illuminate\View\Factory(new Illuminate\View\Engines\EngineResolver(), new Illuminate\View\FileViewFinder(new Illuminate\Filesystem\Filesystem(), []), $events);
$app->instance(Illuminate\Contracts\Routing\ResponseFactory::class, new Illuminate\Routing\ResponseFactory($views, new Illuminate\Routing\Redirector($url)));
$GLOBALS['integrationActionHandler'] = function ($name) use ($db): void {
    if ($name === PAYMENT_ACTION_PAYMENT_PROCESSED) {
        $db->table('payment_test_completions')->insert(['payment_identity' => 'pi_concurrent']);
        usleep(100000);
    }
};
while (microtime(true) < $startAt) { usleep(10000); }
$body = json_encode(['id' => 'evt_concurrent', 'object' => 'event', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_concurrent', 'object' => 'payment_intent', 'amount' => 20000, 'amount_received' => 20000, 'currency' => 'usd', 'status' => 'succeeded']]]);
$time = time();$signature = hash_hmac('sha256', $time . '.' . $body, 'whsec_synthetic_local_fixture');
$request = Illuminate\Http\Request::create('/stripe/webhook', 'POST', [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t=$time,v1=$signature"], $body);
$response = (new Botble\Stripe\Http\Controllers\StripeController())->webhook($request);
echo json_encode(['status' => $response->getStatusCode()]);
