<?php

namespace Botble\Hotel\DataTransferObjects;

use Botble\Hotel\Facades\HotelHelper;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

readonly class StayDates
{
    public function __construct(public CarbonImmutable $arrival, public CarbonImmutable $departure)
    {
        if ($departure <= $arrival || $arrival->diffInDays($departure) > config('plugins.hotel.hotel.max_search_nights', 365)) {
            throw new InvalidArgumentException('Invalid stay date range.');
        }
    }

    public static function from(CarbonInterface|string $arrival, CarbonInterface|string $departure): self
    {
        return new self(self::parse($arrival), self::parse($departure));
    }

    private static function parse(CarbonInterface|string $date): CarbonImmutable
    {
        if ($date instanceof CarbonInterface) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        foreach (array_unique([HotelHelper::getDateFormat(), 'Y-m-d']) as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat('!' . $format, $date);
                if ($parsed && $parsed->format($format) === $date) {
                    return $parsed;
                }
            } catch (InvalidArgumentException) {
                // Try the canonical ISO format used by stored booking dates.
            }
        }

        throw new InvalidArgumentException('Invalid stay date.');
    }

    public function nights(): int
    {
        return (int) $this->arrival->diffInDays($this->departure);
    }

    public function dates(): iterable
    {
        for ($date = $this->arrival; $date < $this->departure; $date = $date->addDay()) {
            yield $date->toDateString();
        }
    }
}
