<?php

namespace Botble\Hotel\Rules;

use Botble\Hotel\DataTransferObjects\StayDates;
use Botble\Hotel\Facades\HotelHelper;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class ValidDeparture implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $arrival = $this->data['start_date'] ?? null;
        if (! is_string($arrival) || ! is_string($value)) {
            return; // The individual fields already report their type errors.
        }

        $format = HotelHelper::getDateFormat();
        try {
            $start = CarbonImmutable::createFromFormat('!' . $format, $arrival);
            if (! $start || $start->format($format) !== $arrival) {
                return; // Do not compare against an invalid arrival.
            }
            StayDates::from($start, $value);
        } catch (InvalidArgumentException) {
            $fail(__('Choose a departure after arrival within the maximum stay of :nights nights.', [
                'nights' => config('plugins.hotel.hotel.max_search_nights', 365),
            ]));
        }
    }
}
