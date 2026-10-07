<?php

namespace Botble\Hotel\Http\Requests;

use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Rules\ValidDeparture;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CalculateBookingAmountRequest extends Request
{
    public function rules(): array
    {
        $dateFormat = HotelHelper::getDateFormat();

        return [
            'room_id' => ['required', 'integer', Rule::exists('ht_rooms', 'id')->where('status', 'published')],
            'start_date' => ['bail', 'required', 'string', 'date_format:' . $dateFormat, 'after_or_equal:today'],
            'end_date' => ['bail', 'required', 'string', 'date_format:' . $dateFormat, new ValidDeparture()],
            'rooms' => ['nullable', 'integer', 'min:1'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'distinct', Rule::exists('ht_services', 'id')->where('status', 'published')],
            'foods' => ['nullable', 'array'],
            'foods.*' => ['integer', 'distinct', Rule::exists('ht_foods', 'id')->where('status', 'published')],
        ];
    }
}
