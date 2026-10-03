<?php

namespace Botble\Hotel\Http\Requests;

use Botble\Support\Http\Requests\Request;

class RoomCalendarRequest extends Request
{
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:ht_rooms,id'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2000'],
        ];
    }
}
