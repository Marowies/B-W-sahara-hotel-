<?php

namespace Botble\Hotel\Http\Requests;

use Botble\Hotel\Facades\HotelHelper;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Support\Http\Requests\Request;

class CreateBookingRequest extends Request
{
    public function rules(): array
    {
        $dateFormat = HotelHelper::getDateFormat();

        return [
            'room_id' => ['required', 'exists:ht_rooms,id'],
            'start_date' => ['required', 'string', 'date_format:' . $dateFormat],
            'end_date' => ['required', 'string', 'date_format:' . $dateFormat, 'after_or_equal:start_date'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
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
            'arrival_time' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:60'],
            'state' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'max:60'],
            'requests' => ['nullable', 'string', 'max:10000'],
            'services' => ['nullable', 'array'],
            'foods' => ['nullable', 'array'],
            'status' => ['required', 'string'],
            'customer_id' => ['nullable', 'exists:ht_customers,id'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', PaymentMethodEnum::values())],
            'payment_status' => ['required', 'string', 'in:' . implode(',', PaymentStatusEnum::values())],
            'transaction_id' => ['nullable', 'string', 'max:60'],
        ];
    }
}
