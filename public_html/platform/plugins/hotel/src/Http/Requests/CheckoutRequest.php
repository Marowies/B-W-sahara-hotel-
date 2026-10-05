<?php

namespace Botble\Hotel\Http\Requests;

use Botble\Base\Facades\BaseHelper;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Rules\ValidDeparture;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CheckoutRequest extends Request
{
    public function rules(): array
    {
        $dateFormat = HotelHelper::getDateFormat();

        return [
            'token' => ['required', 'string', 'size:32'],
            'room_id' => ['required', 'integer', Rule::exists('ht_rooms', 'id')->where('status', 'published')],
            'start_date' => ['bail', 'required', 'string', 'date_format:' . $dateFormat, 'after_or_equal:today'],
            'end_date' => ['bail', 'required', 'string', 'date_format:' . $dateFormat, new ValidDeparture()],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', ...explode('|', BaseHelper::getPhoneValidationRule())],
            'number_of_guests' => [
                'nullable',
                'integer',
                'min:' . HotelHelper::getMinimumNumberOfGuests(),
                'max:' . HotelHelper::getMaximumNumberOfGuests(),
            ],
            'number_of_children' => ['nullable', 'integer', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:1'],
            'zip' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:400'],
            'arrival_time' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:60'],
            'state' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'max:60'],
            'requests' => ['nullable', 'string', 'max:10000'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'distinct', Rule::exists('ht_services', 'id')->where('status', 'published')],
            'foods' => ['nullable', 'array'],
            'foods.*' => ['integer', 'distinct', Rule::exists('ht_foods', 'id')->where('status', 'published')],
            'terms_conditions' => ['accepted:1'],
            'register_customer' => ['nullable'],
            'password' => ['nullable', 'required_if:register_customer,1', 'min:6'],
            'password_confirmation' => ['nullable', 'required_if:register_customer,1', 'same:password'],
        ];
    }
}
