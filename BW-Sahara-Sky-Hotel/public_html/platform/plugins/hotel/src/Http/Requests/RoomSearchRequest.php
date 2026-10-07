<?php

namespace Botble\Hotel\Http\Requests;

use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Rules\ValidDeparture;
use Botble\Support\Http\Requests\Request;

class RoomSearchRequest extends Request
{
    public function rules(): array
    {
        $dateFormat = HotelHelper::getDateFormat();

        return [
            'q' => ['nullable', 'string', 'max:200'],
            'start_date' => ['bail', 'required', 'string', 'date_format:' . $dateFormat, 'after_or_equal:today'],
            'end_date' => ['bail', 'required', 'string', 'date_format:' . $dateFormat, new ValidDeparture()],
            'adults' => ['required', 'integer', 'min:' . HotelHelper::getMinimumNumberOfGuests(), 'max:' . HotelHelper::getMaximumNumberOfGuests()],
            'children' => ['required', 'integer', 'min:0', 'max:100'],
            'rooms' => ['required', 'integer', 'min:1', 'max:100'],
            'page' => ['required', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['required', 'integer', 'min:1', 'max:50'],
            'room_category_id' => ['nullable', 'integer', 'min:1', 'exists:ht_room_categories,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'number_of_beds' => ['nullable', 'integer', 'min:1'],
            'min_size' => ['nullable', 'numeric', 'min:0'],
            'max_size' => ['nullable', 'numeric', 'min:0'],
            'amenities' => ['nullable', 'array', 'max:50'],
            'amenities.*' => ['integer', 'distinct', 'exists:ht_amenities,id'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:price,name,created_at,number_of_beds,size'],
            'sort_direction' => ['required', 'string', 'in:asc,desc'],
        ];
    }
}
