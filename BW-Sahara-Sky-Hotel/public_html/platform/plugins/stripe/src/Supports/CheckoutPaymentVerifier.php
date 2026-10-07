<?php

namespace Botble\Stripe\Supports;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentAmount;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use UnexpectedValueException;

final class CheckoutPaymentVerifier
{
    // Inputs must come from a server-side provider retrieval or signed webhook, never browser JSON.
    public function complete(Session $session, PaymentIntent $intent): string
    {
        return DB::transaction(function () use ($session, $intent): string {
            $binding = DB::table('stripe_checkout_bindings')->where('session_id', $session->id)->first();
            $payment = $binding ? Payment::query()->whereKey($binding->payment_id)
                ->where('payment_channel', STRIPE_PAYMENT_METHOD_NAME)->lockForUpdate()->first() : null;
            if (! $payment) {
                throw new UnexpectedValueException('Missing local Stripe checkout binding.');
            }
            $currency = strtoupper((string) $payment->currency);
            $expected = PaymentAmount::minorUnits($payment->amount,
                StripeHelper::getStripeCurrencyMultiplier($currency) === 1 ? 0 : 2);
            if (! $payment->order_id || ! $expected || $expected <= 0
                || $session->object !== 'checkout.session' || $session->mode !== 'payment'
                || $session->status !== 'complete' || $session->payment_status !== 'paid'
                || $session->payment_intent !== $intent->id
                || ! is_string($intent->id) || ! str_starts_with($intent->id, 'pi_')
                || $intent->object !== 'payment_intent' || $intent->status !== 'succeeded'
                || ! is_string($intent->latest_charge) || ! str_starts_with($intent->latest_charge, 'ch_')
                || strtoupper((string) $session->currency) !== $currency
                || strtoupper((string) $intent->currency) !== $currency
                || $session->amount_total !== $expected || $intent->amount !== $expected
                || $intent->amount_received !== $expected
                || ! in_array($payment->charge_id, [$session->id, $intent->id, $intent->latest_charge], true)
                || ($payment->status != PaymentStatusEnum::PENDING && $payment->status != PaymentStatusEnum::COMPLETED)) {
                throw new UnexpectedValueException('Stripe checkout identity, total or state mismatch.');
            }
            if ($payment->status == PaymentStatusEnum::COMPLETED) {
                return $payment->charge_id;
            }
            // Use the charge identity so existing booking and refund lookups remain compatible.
            $payment->charge_id = $intent->latest_charge;
            $payment->status = PaymentStatusEnum::COMPLETED;
            $payment->save();
            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => $payment->amount, 'currency' => $payment->currency,
                'charge_id' => $payment->charge_id, 'order_id' => [$payment->order_id],
                'customer_id' => $payment->customer_id, 'customer_type' => $payment->customer_type,
                'payment_channel' => STRIPE_PAYMENT_METHOD_NAME, 'status' => PaymentStatusEnum::COMPLETED,
                'payment_fee' => $payment->payment_fee,
            ]);

            return $payment->charge_id;
        });
    }
}
