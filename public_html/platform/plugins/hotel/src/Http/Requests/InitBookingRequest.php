<?php

namespace Botble\Hotel\Http\Requests;

use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Rules\ValidDeparture;
use Botble\Hotel\Models\Room;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class InitBookingRequest extends Request
{
    public function rules(): array
    {
        $dateFormat = HotelHelper::getDateFormat();

        $rules = [
            'room_id' => ['required', 'integer', Rule::exists('ht_rooms', 'id')->where('status', 'published')],
            'start_date' => ['bail', 'required', 'string', 'date_format:' . $dateFormat, 'after_or_equal:today'],
            'end_date' => ['bail', 'required', 'string', 'date_format:' . $dateFormat, new ValidDeparture()],
            'adults' => [
                'required',
                'integer',
                'min:' . HotelHelper::getMinimumNumberOfGuests(),
                'max:' . HotelHelper::getMaximumNumberOfGuests(),
            ],
            'children' => ['nullable', 'integer', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:1'],
        ];

        $roomId = $this->input('room_id');

        if (is_int($roomId) || (is_string($roomId) && ctype_digit($roomId))) {
            $room = Room::query()
                ->select('number_of_rooms')
                ->find($roomId);

            if ($room) {
                $rules['rooms'][] = 'max:' . $room->number_of_rooms;
            }
        }

        return $rules;
    }
}
