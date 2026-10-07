<?php

namespace Botble\Stripe\Services\Gateways;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Supports\PaymentHelper;
use Botble\Payment\Supports\PaymentAmount;
use Botble\Payment\Models\Payment;
use Botble\Stripe\Services\Abstracts\StripePaymentAbstract;
use Botble\Stripe\Supports\StripeHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;
use Stripe\Charge;
use Stripe\Checkout\Session as StripeCheckoutSession;

class StripePaymentService extends StripePaymentAbstract
{
    public function makePayment(array $data): ?string
    {
        $request = request();
        $this->amount = $data['amount'];
        $this->currency = strtoupper($data['currency']);

        if (! $this->setClient()) {
            throw new UnexpectedValueException('Stripe is not configured.');
        }
        if (count((array) $data['order_id']) !== 1 || $this->convertAmount($this->amount) <= 0) {
            throw new UnexpectedValueException('A payment must bind to one booking and a positive total.');
        }

        if ($this->isStripeApiCharge()) {
            if (! $this->token) {
                $this->setErrorMessage(trans('plugins/payment::payment.could_not_get_stripe_token'));

                Log::error(
                    trans('plugins/payment::payment.could_not_get_stripe_token'),
                    PaymentHelper::formatLog(
                        [
                            'error' => 'missing Stripe token',
                            'last_4_digits' => $request->input('last4Digits'),
                            'name' => $request->input('name'),
                            'client_IP' => $request->input('clientIP'),
                            'time_created' => $request->input('timeCreated'),
                            'live_mode' => $request->input('liveMode'),
                        ],
                        __LINE__,
                        __FUNCTION__,
                        __CLASS__
                    )
                );

                return null;
            }

            // The amount already includes the payment fee from the checkout controller

            $charge = Charge::create([
                'amount' => $this->convertAmount($this->amount),
                'currency' => $this->currency,
                'source' => $this->token,
                'description' => trans('plugins/payment::payment.payment_description', [
                    'order_id' => implode(', #', $data['order_id']),
                    'site_url' => $request->getHost(),
                ]),
                'metadata' => [
                    'order_id' => json_encode($data['order_id']),
                    'payment_fee' => Arr::get($data, 'payment_fee', 0),
                ],
            ]);

            $this->chargeId = $charge['id'];

            if ($this->chargeId) {
                $this->persistPendingPayment($this->chargeId, $data);

                return $this->afterMakePayment($this->chargeId, $data);
            }

            return $this->chargeId;
        }

        $lineItems = [];

        foreach ($data['products'] as $product) {
            $lineItems[] = [
                'price_data' => [
                    'product_data' => [
                        'name' => $product['name'],
                        'metadata' => [
                            'pro_id' => $product['id'],
                        ],
                        'description' => $product['name'],
                        'images' => array_filter([Arr::get($product, 'image')]),
                    ],
                    'unit_amount' => $this->convertAmount($product['price_per_order'] / $product['qty']),
                    'currency' => $this->currency,
                ],
                'quantity' => $product['qty'],
            ];
        }

        // Add payment fee as a separate line item if it exists
        $paymentFee = Arr::get($data, 'payment_fee', 0);

        if ($paymentFee > 0) {
            $lineItems[] = [
                'price_data' => [
                    'product_data' => [
                        'name' => trans('plugins/payment::payment.payment_fee'),
                        'description' => trans('plugins/payment::payment.payment_fee'),
                    ],
                    'unit_amount' => $this->convertAmount($paymentFee),
                    'currency' => $this->currency,
                ],
                'quantity' => 1,
            ];
        }

        // The amount already includes the payment fee from the checkout controller
        // We also add the payment fee as a separate line item for transparency
        $requestData = [
            'line_items' => $lineItems,
            'mode' => 'payment',
            'locale' => $this->getCurrentLocale(),
            'success_url' => route('payments.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payments.stripe.error'),
            'metadata' => [
                'order_id' => json_encode($data['order_id']),
                'amount' => $this->amount,
                'currency' => $this->currency,
                'customer_id' => Arr::get($data, 'customer_id'),
                'customer_type' => Arr::get($data, 'customer_type'),
                'return_url' => Arr::get($data, 'return_url'),
                'callback_url' => Arr::get($data, 'callback_url'),
                'payment_fee' => Arr::get($data, 'payment_fee', 0),
            ],
        ];

        if (! empty($data['shipping_method'])) {
            $requestData['shipping_options'] = [
                [
                    'shipping_rate_data' => [
                        'type' => 'fixed_amount',
                        'fixed_amount' => [
                            'amount' => $this->convertAmount($data['shipping_amount']),
                            'currency' => $this->currency,
                        ],
                        'display_name' => $data['shipping_method'],
                    ],
                ],
            ];
        }

        $total = array_sum(array_map(fn ($item) => $item['price_data']['unit_amount'] * $item['quantity'], $lineItems));
        if (! empty($requestData['shipping_options'])) {
            $total += $requestData['shipping_options'][0]['shipping_rate_data']['fixed_amount']['amount'];
        }
        if ($total !== $this->convertAmount($this->amount)) {
            throw new UnexpectedValueException('Stripe checkout line items do not match the stored booking total.');
        }

        do_action('payment_before_making_api_request', STRIPE_PAYMENT_METHOD_NAME, $requestData);

        $checkoutSession = $this->createCheckoutSession($requestData);

        do_action('payment_after_api_response', STRIPE_PAYMENT_METHOD_NAME, $requestData, $checkoutSession->toArray());

        DB::transaction(function () use ($checkoutSession, $data): void {
            $payment = $this->persistPendingPayment($checkoutSession->id, $data);
            DB::table('stripe_checkout_bindings')->insert([
                'session_id' => $checkoutSession->id, 'payment_id' => $payment->getKey(), 'created_at' => now(),
            ]);
        });

        return $checkoutSession->url;
    }

    protected function createCheckoutSession(array $data): StripeCheckoutSession
    {
        return StripeCheckoutSession::create($data);
    }

    protected function convertAmount(float $amount): int
    {
        $multiplier = StripeHelper::getStripeCurrencyMultiplier($this->currency);
        $units = PaymentAmount::minorUnits($amount, $multiplier === 1 ? 0 : 2);
        if ($units === null) {
            throw new UnexpectedValueException('Invalid payment precision.');
        }

        return $units;
    }

    protected function persistPendingPayment(string $chargeId, array $data): Payment
    {
        return Payment::query()->create([
            'amount' => $data['amount'], 'currency' => $this->currency,
            'charge_id' => $chargeId, 'order_id' => Arr::first($data['order_id']),
            'customer_id' => Arr::get($data, 'customer_id'), 'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => STRIPE_PAYMENT_METHOD_NAME, 'status' => PaymentStatusEnum::PENDING,
            'payment_fee' => Arr::get($data, 'payment_fee', 0),
        ]);
    }

    public function afterMakePayment(string $chargeId, array $data): ?string
    {
        $charge = $this->getPaymentDetails($chargeId);

        return DB::transaction(function () use ($chargeId, $charge): ?string {
            $payments = Payment::query()->where('charge_id', $chargeId)
                ->where('payment_channel', STRIPE_PAYMENT_METHOD_NAME)->lockForUpdate()->get();
            $payment = $payments->count() === 1 ? $payments->first() : null;
            $expected = $payment ? PaymentAmount::minorUnits($payment->amount,
                StripeHelper::getStripeCurrencyMultiplier($payment->currency) === 1 ? 0 : 2) : null;
            if (! $payment || ! $payment->order_id || ! $expected || ! $charge
                || $charge->id !== $chargeId || $charge->paid !== true || $charge->status !== 'succeeded'
                || $charge->amount !== $expected
                || strtoupper((string) $charge->currency) !== strtoupper((string) $payment->currency)
                || ($payment->status != PaymentStatusEnum::PENDING && $payment->status != PaymentStatusEnum::COMPLETED)) {
                $this->setErrorMessage(trans('plugins/stripe::stripe.payment_failed'));

                return null;
            }
            if ($payment->status == PaymentStatusEnum::COMPLETED) {
                return $chargeId;
            }
            $payment->status = PaymentStatusEnum::COMPLETED;
            $payment->save();
            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => $payment->amount, 'currency' => $payment->currency,
                'charge_id' => $chargeId, 'order_id' => [$payment->order_id],
                'customer_id' => $payment->customer_id, 'customer_type' => $payment->customer_type,
                'payment_channel' => STRIPE_PAYMENT_METHOD_NAME, 'status' => PaymentStatusEnum::COMPLETED,
                'payment_fee' => $payment->payment_fee,
            ]);

            return $chargeId;
        });
    }

    public function isStripeApiCharge(): bool
    {
        $key = 'stripe_api_charge';

        return get_payment_setting('payment_type', STRIPE_PAYMENT_METHOD_NAME, $key) == $key;
    }

    public function supportedCurrencyCodes(): array
    {
        return [
            'USD',
            'AED',
            'AFN',
            'ALL',
            'AMD',
            'ANG',
            'AOA',
            'ARS',
            'AUD',
            'AWG',
            'AZN',
            'BAM',
            'BBD',
            'BDT',
            'BGN',
            'BHD',
            'BIF',
            'BMD',
            'BND',
            'BOB',
            'BRL',
            'BSD',
            'BWP',
            'BYN',
            'BZD',
            'CAD',
            'CDF',
            'CHF',
            'CLP',
            'CNY',
            'COP',
            'CRC',
            'CVE',
            'CZK',
            'DJF',
            'DKK',
            'DOP',
            'DZD',
            'EGP',
            'ETB',
            'EUR',
            'FJD',
            'FKP',
            'GBP',
            'GEL',
            'GIP',
            'GMD',
            'GNF',
            'GTQ',
            'GYD',
            'HKD',
            'HNL',
            'HRK',
            'HTG',
            'HUF',
            'IDR',
            'ILS',
            'INR',
            'ISK',
            'JMD',
            'JOD',
            'JPY',
            'KES',
            'KGS',
            'KHR',
            'KMF',
            'KRW',
            'KWD',
            'KYD',
            'KZT',
            'LAK',
            'LBP',
            'LKR',
            'LRD',
            'LSL',
            'MAD',
            'MDL',
            'MGA',
            'MKD',
            'MMK',
            'MNT',
            'MOP',
            'MRO',
            'MUR',
            'MVR',
            'MWK',
            'MXN',
            'MYR',
            'MZN',
            'NAD',
            'NGN',
            'NIO',
            'NOK',
            'NPR',
            'NZD',
            'OMR',
            'PAB',
            'PEN',
            'PGK',
            'PHP',
            'PKR',
            'PLN',
            'PYG',
            'QAR',
            'RON',
            'RSD',
            'RUB',
            'RWF',
            'SAR',
            'SBD',
            'SCR',
            'SEK',
            'SGD',
            'SHP',
            'SLE',
            'SOS',
            'SRD',
            'STD',
            'SZL',
            'THB',
            'TJS',
            'TND',
            'TOP',
            'TRY',
            'TTD',
            'TWD',
            'TZS',
            'UAH',
            'UGX',
            'UYU',
            'UZS',
            'VND',
            'VUV',
            'WST',
            'XAF',
            'XCD',
            'XOF',
            'XPF',
            'YER',
            'ZAR',
            'ZMW',
            'USDC',
            'BTN',
            'GHS',
            'EEK',
            'LVL',
            'SVC',
            'VEF',
            'LTL',
            'SLL',
        ];
    }

    public function getCurrentLocale(): string
    {
        $supportedLocales = [
            'bg',
            'cs',
            'da',
            'de',
            'el',
            'en',
            'en-GB',
            'es',
            'es-419',
            'et',
            'fi',
            'fil',
            'fr',
            'fr-CA',
            'hr',
            'hu',
            'id',
            'it',
            'ja',
            'ko',
            'lt',
            'lv',
            'ms',
            'mt',
            'nb',
            'nl',
            'pl',
            'pt',
            'pt-BR',
            'ro',
            'ru',
            'sk',
            'sl',
            'sv',
            'th',
            'tr',
            'vi',
            'zh',
            'zh-HK',
            'zh-TW',
        ];

        $currentLocale = app()->getLocale();

        if (in_array($currentLocale, $supportedLocales)) {
            return $currentLocale;
        }

        return 'auto';
    }
}
