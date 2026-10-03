<?php

namespace Botble\Hotel\Http\Requests;

use Botble\Support\Http\Requests\Request;

class SyncCalendarRequest extends Request
{
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:ht_rooms,id'],
        ];
    }
}
