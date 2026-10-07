<?php

namespace Botble\Hotel\Models;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Hotel\DataTransferObjects\StayDates;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class Room extends BaseModel
{
    protected $table = 'ht_rooms';

    protected $fillable = [
        'name',
        'description',
        'content',
        'is_featured',
        'images',
        'price',
        'currency_id',
        'number_of_rooms',
        'number_of_beds',
        'size',
        'max_adults',
        'max_children',
        'room_category_id',
        'tax_id',
        'order',
        'status',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
    ];

    public function getImagesAttribute($value)
    {
        if ($value === '[null]') {
            return [];
        }

        $images = json_decode((string) $value, true);

        if (is_array($images)) {
            $images = array_filter($images);
        }

        return $images ?: [];
    }

    public function getImageAttribute(): ?string
    {
        return Arr::first($this->images) ?? null;
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'ht_rooms_amenities', 'room_id', 'amenity_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id')->withDefault();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id')->withDefault();
    }

    public function isAvailableAt(array $filters = []): bool
    {
        $rooms = (int) ($filters['rooms'] ?? 1);
        $adults = (int) ($filters['adults'] ?? 1);
        $children = (int) ($filters['children'] ?? 0);
        if ($rooms < 1 || $adults < 1 || $children < 0
            || $this->max_adults < $adults || $this->max_children < $children) {
            return false;
        }

        if (empty($filters['start_date']) || empty($filters['end_date'])) {
            return $this->number_of_rooms >= $rooms;
        }

        try {
            $stay = StayDates::from($filters['start_date'], $filters['end_date']);
        } catch (InvalidArgumentException) {
            return false;
        }

        $inventory = array_fill_keys(iterator_to_array($stay->dates()), (int) $this->number_of_rooms);
        foreach ($this->activeRoomDates as $row) {
            $day = substr((string) $row->start_date, 0, 10);
            if (! array_key_exists($day, $inventory)) {
                continue;
            }
            if (! $row->active || $row->number_of_rooms < 1) {
                return false;
            }
            $inventory[$day] = (int) $row->number_of_rooms;
        }

        foreach ($this->activeBookingRooms as $bookingRoom) {
            foreach ($inventory as $day => $remaining) {
                if ($day >= substr((string) $bookingRoom->start_date, 0, 10)
                    && $day < substr((string) $bookingRoom->end_date, 0, 10)) {
                    $inventory[$day] -= (int) $bookingRoom->number_of_rooms;
                }
            }
        }

        return min($inventory) >= $rooms;
    }

    public function getRoomTotalPrice(CarbonInterface|string $startDate, CarbonInterface|string $endDate, ?int $rooms = 1): float|int
    {
        if ($rooms === null) {
            $rooms = 1;
        }
        if ($rooms < 1) {
            throw new InvalidArgumentException('Room quantity must be positive.');
        }

        $stay = StayDates::from($startDate, $endDate);
        $prices = array_fill_keys(iterator_to_array($stay->dates()), max(0, (float) $this->price));
        foreach ($this->activeRoomDates as $row) {
            $day = substr((string) $row->start_date, 0, 10);
            if (! array_key_exists($day, $prices) || ! $row->active) {
                continue;
            }
            $prices[$day] = max(0, match ($row->value_type) {
                'fixed' => (float) $row->value,
                'amount_adjust' => $this->price + $row->value,
                'percentage_adjust' => $this->price + $this->price * $row->value / 100,
                default => $prices[$day],
            });
        }

        return array_sum($prices) * $rooms;
    }

    public function activeRoomDates(): HasMany
    {
        return $this->hasMany(RoomDate::class, 'room_id');
    }

    public function activeBookingRooms(): HasMany
    {
        return $this
            ->hasMany(BookingRoom::class, 'room_id')
            ->active();
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id')->withDefault();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'room_id');
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(RoomCalendar::class, 'room_id');
    }

    protected static function booted(): void
    {
        static::deleted(function (Room $room): void {
            $room->amenities()->detach();
            $room->activeRoomDates()->delete();
            $room->calendars()->delete();
        });
    }
}
