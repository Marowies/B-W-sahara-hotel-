<?php

namespace Botble\Hotel\Http\Requests\Fronts\Auth;

use Botble\Support\Http\Requests\Request;

class RegisterRequest extends Request
{
    public function rules(): array
    {
        return apply_filters('hotel_customer_registration_form_validation_rules', [
            'first_name' => 'required|string|max:60|min:2',
            'last_name' => 'required|string|max:60|min:2',
            'email' => 'required|max:120|min:6|email|unique:ht_customers',
            'password' => 'required|string|min:6|confirmed',
            'agree_terms_and_policy' => ['sometimes', 'accepted:1'],
        ]);
    }

    public function attributes(): array
    {
        return apply_filters('hotel_customer_registration_form_validation_attributes', [
            'first_name' => trans('plugins/hotel::customer.first_name'),
            'last_name' => trans('plugins/hotel::customer.last_name'),
            'email' => trans('plugins/hotel::customer.email'),
            'password' => trans('plugins/hotel::customer.password'),
            'agree_terms_and_policy' => trans('plugins/hotel::customer.term_and_policy'),
        ]);
    }

    public function messages(): array
    {
        return apply_filters('hotel_customer_registration_form_validation_messages', []);
    }
}
