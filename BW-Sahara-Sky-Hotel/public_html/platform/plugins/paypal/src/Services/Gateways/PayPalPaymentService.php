<?php

namespace Botble\PayPal\Services\Gateways;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentHelper;
use Botble\PayPal\Services\Abstracts\PayPalPaymentAbstract;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PayPalPaymentService extends PayPalPaymentAbstract
{
    public function makePayment(array $data)
    {
        $currency = strtoupper($data['currency']);
        $this->setCurrency($currency);
        $amount = round((float) $data['amount'], in_array($currency, ['HUF', 'JPY', 'TWD'], true) ? 0 : 2);
        $orderIds = array_values((array) $data['order_id']);
        if (count($orderIds) !== 1 || ! is_numeric($orderIds[0]) || $amount <= 0) {
            return false;
        }

        if ($cancelUrl = $data['return_url'] ?: PaymentHelper::getCancelURL()) {
            $this->setCancelUrl($cancelUrl);
        }

        $description = Str::limit($data['description'], 50);

        $checkoutUrl = $this
            ->setReturnUrl($data['callback_url'])
            ->setCurrency($currency)
            ->setCustomer(Arr::get($data, 'address.email') ?: '')
            ->setItem([
                'name' => $description,
                'quantity' => 1,
                'price' => $amount,
                'sku' => null,
                'type' => PAYPAL_PAYMENT_METHOD_NAME,
            ])
            ->createPayment($description);

        if ($checkoutUrl && ($chargeId = session('paypal_payment_id'))) {
            // Persist the server-selected booking and expected amount before the browser leaves.
            Payment::query()->create([
                'amount' => $amount,
                'currency' => $currency,
                'charge_id' => $chargeId,
                'order_id' => $orderIds[0],
                'customer_id' => Arr::get($data, 'customer_id'),
                'customer_type' => Arr::get($data, 'customer_type'),
                'payment_channel' => PAYPAL_PAYMENT_METHOD_NAME,
                'payment_fee' => Arr::get($data, 'payment_fee', 0),
                'status' => PaymentStatusEnum::PENDING,
            ]);
        }

        return $checkoutUrl;
    }

    public function afterMakePayment(array $data): ?string
    {
        if ($this->verifiedPaymentId === null) {
            return null;
        }
        $chargeId = DB::transaction(function (): ?string {
            $payment = Payment::query()->whereKey($this->verifiedPaymentId)->lockForUpdate()->first();
            if (! $payment || $payment->charge_id !== session('paypal_payment_id')
                || $payment->payment_channel != PAYPAL_PAYMENT_METHOD_NAME
                || $this->paymentFingerprint($payment) !== $this->verifiedPaymentFingerprint) {
                return null;
            }
            if ($payment->status == PaymentStatusEnum::COMPLETED) {
                return $payment->charge_id;
            }
            if ($payment->status != PaymentStatusEnum::PENDING) {
                return null;
            }
            $payment->status = PaymentStatusEnum::COMPLETED;
            $payment->save();
            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'charge_id' => $payment->charge_id,
                'order_id' => [(int) $payment->order_id],
                'customer_id' => $payment->customer_id,
                'customer_type' => $payment->customer_type,
                'payment_channel' => PAYPAL_PAYMENT_METHOD_NAME,
                'payment_fee' => $payment->payment_fee,
                'status' => PaymentStatusEnum::COMPLETED,
            ]);

            return $payment->charge_id;
        });
        if ($chargeId !== null) {
            session()->forget('paypal_payment_id');
            $this->verifiedPaymentId = null;
        }

        return $chargeId;
    }
}
