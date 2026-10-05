<?php

namespace Botble\PayPal\Http\Requests;

use Botble\Support\Http\Requests\Request;

class PayPalPaymentCallbackRequest extends Request
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'PayerID' => ['required', 'string', 'max:255'],
        ];
    }
}
