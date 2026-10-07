<?php

namespace Botble\Stripe\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentAmount;
use Botble\Payment\Supports\PaymentHelper;
use Botble\Stripe\Http\Requests\StripePaymentCallbackRequest;
use Botble\Stripe\Services\Gateways\StripePaymentService;
use Botble\Stripe\Supports\StripeHelper;
use Botble\Stripe\Supports\CheckoutPaymentVerifier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class StripeController extends BaseController
{
    public function webhook(Request $request)
    {
        $webhookSecret = get_payment_setting('webhook_secret', 'stripe');
        $signature = $request->server('HTTP_STRIPE_SIGNATURE');
        $content = $request->getContent();

        if (! $webhookSecret || ! $signature || ! $content) {
            return response()->noContent(400);
        }

        try {
            do_action('payment_before_making_api_request', STRIPE_PAYMENT_METHOD_NAME, (array) $content);

            $event = Webhook::constructEvent(
                $content,
                $signature,
                $webhookSecret
            );

            do_action('payment_after_api_response', STRIPE_PAYMENT_METHOD_NAME, (array) $content, $event->toArray());

            if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
                $session = $event->data->object;
                if ($session->payment_status !== 'paid') {
                    return response()->noContent();
                }
                if (! DB::table('stripe_checkout_bindings')->where('session_id', $session->id)->exists()) {
                    return response()->noContent(503);
                }
                if (! $this->configureWebhookClient()) {
                    return response()->noContent(503);
                }
                (new CheckoutPaymentVerifier())->complete($session, $this->retrieveIntent($session->payment_intent));

                return response()->noContent();
            }

            if ($event->type == 'payment_intent.succeeded') {
                /**
                 * @var PaymentIntent $paymentIntent
                 */
                $paymentIntent = $event->data->object; // @phpstan-ignore-line

                $status = DB::transaction(function () use ($event, $paymentIntent): int {
                    if (! is_string($event->id) || $event->id === ''
                        || ! is_string($paymentIntent->id) || ! str_starts_with($paymentIntent->id, 'pi_')) {
                        return 400;
                    }
                    $identities = [$paymentIntent->id];
                    $latestCharge = $paymentIntent->latest_charge ?? null;
                    if (is_string($latestCharge) && str_starts_with($latestCharge, 'ch_')) {
                        $identities[] = $latestCharge;
                    }
                    $payments = Payment::query()->whereIn('charge_id', $identities)
                        ->where('payment_channel', STRIPE_PAYMENT_METHOD_NAME)->lockForUpdate()->get();
                    if ($payments->count() !== 1) {
                        // A provider event can precede local persistence; allow provider retry.
                        return 503;
                    }
                    $payment = $payments->first();
                    $currency = strtoupper((string) $payment->currency);
                    $multiplier = StripeHelper::getStripeCurrencyMultiplier($currency);
                    $expected = PaymentAmount::minorUnits($payment->amount, $multiplier === 1 ? 0 : 2);
                    if (! in_array($payment->charge_id, $identities, true)
                        || ! $payment->order_id || $expected === null || $expected <= 0
                        || ($paymentIntent->object ?? null) !== 'payment_intent'
                        || ($paymentIntent->status ?? null) !== 'succeeded'
                        || strtoupper((string) $paymentIntent->currency) !== $currency
                        || ! is_int($paymentIntent->amount) || $paymentIntent->amount !== $expected
                        || ! is_int($paymentIntent->amount_received) || $paymentIntent->amount_received !== $expected) {
                        BaseHelper::logError(new UnexpectedValueException('Stripe payment identity, amount or currency mismatch.'));

                        return 422;
                    }
                    $receipt = DB::table('payment_webhook_receipts')->where('provider', 'stripe')->where('event_id', $event->id)->first();
                    if ($receipt) {
                        return $receipt->payment_identity === $paymentIntent->id ? 204 : 422;
                    }
                    if ($payment->status != PaymentStatusEnum::PENDING && $payment->status != PaymentStatusEnum::COMPLETED) {
                        return 422;
                    }
                    DB::table('payment_webhook_receipts')->insert([
                        'provider' => 'stripe', 'event_id' => $event->id,
                        'payment_identity' => $paymentIntent->id, 'payment_id' => $payment->getKey(),
                        'processed_at' => now(),
                    ]);
                    // Different event IDs for the same successful intent must not repeat fulfillment.
                    if ($payment->status == PaymentStatusEnum::COMPLETED) {
                        return 204;
                    }
                    $payment->status = PaymentStatusEnum::COMPLETED;
                    $payment->save();
                    do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                        'amount' => $payment->amount, 'currency' => $payment->currency,
                        'charge_id' => $payment->charge_id, 'order_id' => [$payment->order_id],
                        'customer_id' => $payment->customer_id, 'customer_type' => $payment->customer_type,
                        'payment_channel' => STRIPE_PAYMENT_METHOD_NAME, 'status' => PaymentStatusEnum::COMPLETED,
                        'payment_fee' => $payment->payment_fee,
                    ]);

                    return 204;
                });

                return response()->noContent($status);
            }
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            BaseHelper::logError($e);

            return response()->noContent(400);
        }

        return response()->noContent();
    }

    public function success(
        StripePaymentCallbackRequest $request,
        StripePaymentService $stripePaymentService,
        BaseHttpResponse $response
    ) {
        try {
            if (! $stripePaymentService->setClient()) {
                throw new UnexpectedValueException('Stripe is not configured.');
            }

            $sessionId = $request->input('session_id');

            do_action('payment_before_making_api_request', STRIPE_PAYMENT_METHOD_NAME, ['id' => $sessionId]);

            // A provider session alone is not proof that it belongs to this website's booking.
            if (! DB::table('stripe_checkout_bindings')->where('session_id', $sessionId)->exists()) {
                throw new UnexpectedValueException('Unknown checkout session.');
            }
            $session = $this->retrieveSession($sessionId);

            do_action('payment_after_api_response', STRIPE_PAYMENT_METHOD_NAME, ['id' => $sessionId], $session->toArray());

            if ($session->payment_status == 'paid') {
                $chargeId = (new CheckoutPaymentVerifier())->complete($session, $this->retrieveIntent($session->payment_intent));

                return $response
                    ->setNextUrl(PaymentHelper::getRedirectURL() . '?charge_id=' . $chargeId)
                    ->setMessage(trans('plugins/payment::payment.checkout_success'));
            }

            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage(trans('plugins/stripe::stripe.payment_failed'));
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->withInput()
                ->setMessage(trans('plugins/stripe::stripe.payment_failed'));
        }
    }

    protected function retrieveSession(string $id): Session
    {
        return Session::retrieve($id);
    }

    protected function configureWebhookClient(): bool
    {
        return (new StripePaymentService())->setClient();
    }

    protected function retrieveIntent(string $id): PaymentIntent
    {
        return PaymentIntent::retrieve($id);
    }

    public function error(BaseHttpResponse $response)
    {
        return $response
            ->setError()
            ->setNextUrl(PaymentHelper::getCancelURL())
            ->withInput()
            ->setMessage(trans('plugins/stripe::stripe.payment_failed'));
    }
}
