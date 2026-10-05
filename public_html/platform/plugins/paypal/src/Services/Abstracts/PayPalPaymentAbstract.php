<?php

namespace Botble\PayPal\Services\Abstracts;

use Botble\Payment\Models\Payment;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Supports\PaymentAmount;
use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\PayPal\Services\Core\PayPalHttpClient;
use Botble\Theme\Facades\Theme;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
use PayPalHttp\HttpResponse;

abstract class PayPalPaymentAbstract
{
    use PaymentErrorTrait;

    protected array $itemList;

    protected string $paymentCurrency;

    protected float $totalAmount;

    protected string $returnUrl;

    protected string $cancelUrl;

    protected PayPalHttpClient $client;

    protected string $transactionDescription;

    protected string $customer;

    protected bool $supportRefundOnline;

    protected ?int $verifiedPaymentId = null;

    protected ?string $verifiedPaymentFingerprint = null;

    protected function paymentFingerprint(Payment $payment): string
    {
        return hash('sha256', json_encode([
            $payment->charge_id, (string) $payment->amount, $payment->currency,
            (string) $payment->order_id, (string) $payment->customer_id, $payment->customer_type,
        ], JSON_THROW_ON_ERROR));
    }

    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');

        $this->totalAmount = 0;

        $this->setClient();

        $this->supportRefundOnline = true;
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    /**
     * Returns PayPal HTTP client instance with environment which has access
     * credentials context. This can be used invoke PayPal API's provided the
     * credentials have the access to do so.
     */
    public function setClient(): self
    {
        $this->client = new PayPalHttpClient($this->environment());

        return $this;
    }

    public function getClient(): PayPalHttpClient
    {
        return $this->client;
    }

    /**
     * Setting up and Returns PayPal SDK environment with PayPal Access credentials.
     * For demo purpose, we are using SandboxEnvironment. In production this will be
     * ProductionEnvironment.
     */
    public function environment(): SandboxEnvironment|ProductionEnvironment
    {
        $clientId = setting('payment_paypal_client_id', '<<PAYPAL-CLIENT-ID>>');
        $clientSecret = setting('payment_paypal_client_secret', '<<PAYPAL-CLIENT-SECRET>>');
        $payPalMode = setting('payment_paypal_mode');

        if ($payPalMode) {
            return new ProductionEnvironment($clientId, $clientSecret);
        }

        return new SandboxEnvironment($clientId, $clientSecret);
    }

    public function setCurrency(string $currency): self
    {
        $this->paymentCurrency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->paymentCurrency;
    }

    public function getCustomer(): string
    {
        return $this->customer;
    }

    public function setCustomer(string $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function setItem(array $itemData): self
    {
        if (count($itemData) === count($itemData, COUNT_RECURSIVE)) {
            $itemData = [$itemData];
        }

        foreach ($itemData as $data) {
            $amount = $data['price'] * $data['quantity'];

            $item = [
                'name' => $data['name'],
                'sku' => $data['sku'],
                'unit_amount' => [
                    'currency_code' => $this->paymentCurrency,
                    'value' => $amount,
                ],
                'quantity' => $data['quantity'],
            ];

            if ($description = Arr::get($data, 'description')) {
                $item['description'] = $description;
            }

            if ($tax = Arr::get($data, 'tax')) {
                $item['tax'] = [
                    'currency_code' => $this->paymentCurrency,
                    'value' => $tax,
                ];
            }

            if ($category = Arr::get($data, 'category')) {
                $item['category'] = $category;
            }

            $this->itemList[] = $item;
            $this->totalAmount += $amount;
        }

        // issue https://developer.paypal.com/docs/api/orders/v2/#error-DECIMAL_PRECISION
        $this->totalAmount = round((float) $this->totalAmount, $this->isSupportedDecimals() ? 2 : 0);

        return $this;
    }

    public function setReturnUrl(string $url): self
    {
        $this->returnUrl = $url;

        return $this;
    }

    public function setCancelUrl(string $url): self
    {
        $this->cancelUrl = $url;

        return $this;
    }

    /**
     * Setting up the JSON request body for creating the Order. The Intent in the
     * request body should be set as "CAPTURE" for capture intent flow.
     */
    protected function buildRequestBody(): array
    {
        return [
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => $this->returnUrl,
                'cancel_url' => $this->cancelUrl ?: $this->returnUrl,
                'brand_name' => Theme::getSiteTitle(),
            ],
            'purchase_units' => [
                0 => [
                    'description' => $this->transactionDescription,
                    'custom_id' => $this->customer,
                    'amount' => [
                        'currency_code' => $this->paymentCurrency,
                        'value' => (string) $this->totalAmount,
                    ],
                ],
            ],
        ];
    }

    public function createPayment(string $transactionDescription): string|null|bool
    {
        $this->transactionDescription = $transactionDescription;

        $orderRequest = new OrdersCreateRequest();
        $orderRequest->prefer('return=representation');
        $orderRequest->body = $this->buildRequestBody();
        $checkoutUrl = '';
        $paymentId = null;

        try {
            do_action('payment_before_making_api_request', PAYPAL_PAYMENT_METHOD_NAME, $orderRequest);

            // Call API with your client and get a response for your call
            $response = $this->client->execute($orderRequest);

            do_action('payment_after_api_response', PAYPAL_PAYMENT_METHOD_NAME, (array) $orderRequest, (array) $response);

            if ($response && $response->statusCode == 201) {
                // @phpstan-ignore-next-line
                $paymentId = $response->result->id;

                // @phpstan-ignore-next-line
                foreach ($response->result->links as $link) {
                    if ($link->rel == 'approve') {
                        $checkoutUrl = $link->href;
                    }
                }
            }
        } catch (Exception $exception) {
            do_action('payment_after_api_response', PAYPAL_PAYMENT_METHOD_NAME, (array) $exception);

            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        if ($checkoutUrl && $paymentId) {
            session(['paypal_payment_id' => $paymentId]);

            return $checkoutUrl;
        }

        session()->forget('paypal_payment_id');

        return null;
    }

    public function getPaymentStatus(Request $request)
    {
        $this->verifiedPaymentId = null;
        $this->verifiedPaymentFingerprint = null;
        $paymentId = session('paypal_payment_id');
        $token = $request->input('token');
        if (! is_string($paymentId) || $paymentId === '' || ! is_string($token)
            || ! hash_equals($paymentId, $token) || ! is_string($request->input('PayerID'))
            || $request->input('PayerID') === '') {
            return false;
        }

        $payments = Payment::query()->where('charge_id', $paymentId)
            ->where('payment_channel', PAYPAL_PAYMENT_METHOD_NAME)->get();
        if ($payments->count() !== 1 || ! $payments->first()->order_id || $payments->first()->charge_id !== $paymentId) {
            return false;
        }
        $payment = $payments->first();
        if ($payment->status == PaymentStatusEnum::COMPLETED) {
            $this->verifiedPaymentId = $payment->getKey();
            $this->verifiedPaymentFingerprint = $this->paymentFingerprint($payment);

            return 'COMPLETED';
        }
        if ($payment->status != PaymentStatusEnum::PENDING) {
            return false;
        }

        try {
            $orderRequest = new OrdersCaptureRequest($paymentId);
            $orderRequest->prefer('return=representation');
            $orderRequest->headers['PayPal-Request-Id'] = substr(hash('sha256', 'capture:' . $paymentId), 0, 38);

            do_action('payment_before_making_api_request', PAYPAL_PAYMENT_METHOD_NAME, $orderRequest);

            $response = $this->client->execute($orderRequest);

            do_action('payment_after_api_response', PAYPAL_PAYMENT_METHOD_NAME, (array) $orderRequest, (array) $response);

            // @phpstan-ignore-next-line
            if ($response && in_array($response->statusCode, [200, 201], true)
                && ($response->result->id ?? null) === $paymentId
                && ($response->result->status ?? null) === 'COMPLETED') {
                $units = $response->result->purchase_units ?? [];
                $captures = count($units) === 1 ? ($units[0]->payments->captures ?? []) : [];
                $capture = count($captures) === 1 ? $captures[0] : null;
                $currency = strtoupper((string) $payment->currency);
                $decimals = in_array($currency, ['HUF', 'JPY', 'TWD'], true) ? 0 : 2;
                $expected = PaymentAmount::minorUnits($payment->amount, $decimals);
                $received = PaymentAmount::minorUnits($capture->amount->value ?? null, $decimals);
                if ($capture && is_string($capture->id ?? null) && $capture->id !== ''
                    && ($capture->status ?? null) === 'COMPLETED'
                    && ($capture->amount->currency_code ?? null) === $currency
                    && $expected !== null && $expected > 0 && $received === $expected
                    && ($capture->supplementary_data->related_ids->order_id ?? $paymentId) === $paymentId) {
                    $this->verifiedPaymentId = $payment->getKey();
                    $this->verifiedPaymentFingerprint = $this->paymentFingerprint($payment);

                    return 'COMPLETED';
                }
            }
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);
        }

        return false;
    }

    public function getPaymentDetails(string $paymentId): bool|HttpResponse
    {
        try {
            $orderRequest = new OrdersGetRequest($paymentId);

            do_action('payment_before_making_api_request', PAYPAL_PAYMENT_METHOD_NAME, $orderRequest);

            $response = $this->client->execute($orderRequest);

            do_action('payment_after_api_response', PAYPAL_PAYMENT_METHOD_NAME, (array) $orderRequest, (array) $response);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        return $response;
    }

    /**
     * Function to create a refund capture request. Payload can be updated to issue partial refund.
     */
    public function buildRefundRequestBody(float|int|string $totalAmount): array
    {
        $totalAmount = round((float) $totalAmount, 2);

        return [
            'amount' => [
                'value' => (string) $totalAmount,
                'currency_code' => $this->paymentCurrency,
            ],
        ];
    }

    /**
     * This function can be used to preform refund on the capture.
     */
    public function refundOrder(string $paymentId, float|int|string $totalAmount): array
    {
        try {
            $detail = $this->getPaymentDetails($paymentId);

            if ($detail) {
                // @phpstan-ignore-next-line
                $purchaseUnits = $detail->result->purchase_units;
                $purchaseUnit = Arr::get($purchaseUnits, 0);

                $refunds = null;
                $payments = $purchaseUnit->payments;
                if ($payments && ! empty($payments->refunds)) {
                    $refunds = $payments->refunds;
                }

                if ($refunds) {
                    // @phpstan-ignore-next-line
                    $purchase = Arr::first($detail->result->purchase_units);
                    $capture = Arr::first($purchase->payments->captures);

                    if (! $capture) {
                        return [
                            'error' => true,
                            'message' => trans('plugins/payment::payment.cannot_found_capture_id'),
                        ];
                    }

                    $captureId = $capture->id;

                    if ($captureId && $capture->status != 'DECLINED') {
                        $payment = Payment::query()->where('charge_id', $paymentId)->firstOrFail();
                        $paymentCurrency = $purchase->amount->currency_code;

                        if ($payment->currency !== $paymentCurrency) {

                            $currency = cms_currency()->currencies()->where('title', $paymentCurrency)->first();

                            if ($currency) {
                                $totalAmount = $totalAmount * $currency->exchange_rate;
                                $this->paymentCurrency = $paymentCurrency;
                            }
                        }

                        $refundRequest = new CapturesRefundRequest($captureId);
                        $refundRequest->body = $this->buildRefundRequestBody($totalAmount);
                        $refundRequest->prefer('return=representation');
                        $response = $this->client->execute($refundRequest);

                        // @phpstan-ignore-next-line
                        if ($response && $response->statusCode == 201 && $response->result->status == 'COMPLETED') {
                            return [
                                'error' => false, // @phpstan-ignore-next-line
                                'status' => $response->result->status,
                                'data' => (array) $response->result,
                            ];
                        }

                        return [
                            'error' => true,
                            'status' => $response->statusCode,
                            'message' => trans('plugins/payment::payment.status_is_not_completed'),
                        ];
                    }
                }
            }

            return [
                'error' => false,
                'status' => true,
                'data' => [],
            ];
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return [
                'error' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function execute(array $data)
    {
        try {
            return $this->makePayment($data);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    public function isSupportedDecimals(): bool
    {
        return ! in_array($this->getCurrency(), [
            'BIF',
            'CLP',
            'DJF',
            'GNF',
            'HUF',
            'JPY',
            'KMF',
            'KRW',
            'MGA',
            'PYG',
            'RWF',
            'TWD',
            'VND',
            'VUV',
            'XAF',
            'XOF',
            'XPF',
        ]);
    }

    /**
     * List currencies supported https://developer.paypal.com/docs/api/reference/currency-codes/
     */
    public function supportedCurrencyCodes(): array
    {
        return [
            'AUD',
            'BRL',
            'CAD',
            'CNY',
            'CZK',
            'DKK',
            'EUR',
            'HKD',
            'HUF',
            'ILS',
            'JPY',
            'MYR',
            'MXN',
            'TWD',
            'NZD',
            'NOK',
            'PHP',
            'PLN',
            'GBP',
            'RUB',
            'SGD',
            'SEK',
            'CHF',
            'THB',
            'USD',
        ];
    }

    abstract public function makePayment(array $data);

    abstract public function afterMakePayment(array $data);
}
