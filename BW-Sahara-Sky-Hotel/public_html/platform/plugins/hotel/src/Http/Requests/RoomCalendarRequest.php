<?php

namespace Botble\Hotel\Http\Requests;

use Botble\Hotel\Services\SafeCalendarFetcher;
use Botble\Support\Http\Requests\Request;
use Closure;
use InvalidArgumentException;

class RoomCalendarRequest extends Request
{
    public function rules(): array
    {
        return [
            'room_id' => ['sometimes', 'required', 'integer', 'exists:ht_rooms,id'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                try {
                    app(SafeCalendarFetcher::class)->validateUrl($value);
                } catch (InvalidArgumentException $exception) {
                    $fail($exception->getMessage());
                }
            }],
        ];
    }
}
