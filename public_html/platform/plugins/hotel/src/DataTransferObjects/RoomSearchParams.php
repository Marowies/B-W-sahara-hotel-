<?php

namespace Botble\Hotel\DataTransferObjects;

use Botble\Hotel\Enums\BookingStatusEnum;
use Carbon\Carbon;
use Exception;

class RoomSearchParams
{
    public function __construct(
        public ?string $keyword = null,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null,
        public int $adults = 1,
        public int $children = 0,
        public int $rooms = 1,
        public int $page = 1,
        public int $perPage = 10,
        public ?int $roomCategoryId = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?int $numberOfBeds = null,
        public ?float $minSize = null,
        public ?float $maxSize = null,
        public ?array $amenities = null,
        public ?bool $isFeatured = null,
        public ?string $sortBy = null,
        public string $sortDirection = 'asc',
        public array $with = [],
        public bool $paginate = true,
    ) {
        $this->with = $this->getDefaultRelations();
    }

    public static function fromRequest(array $request): self
    {
        $dateFormat = config('plugins.hotel.hotel.date_format', 'd-m-Y');

        try {
            if (isset($request['start_date']) && isset($request['end_date'])) {
                $startDate = Carbon::createFromFormat($dateFormat, $request['start_date']);
                $endDate = Carbon::createFromFormat($dateFormat, $request['end_date']);
            } else {
                $startDate = Carbon::now();
                $endDate = Carbon::now()->addDay();
            }
        } catch (Exception) {
            $startDate = Carbon::now();
            $endDate = Carbon::now()->addDay();
        }

        return new self(
            keyword: $request['q'] ?? null,
            startDate: $startDate,
            endDate: $endDate,
            adults: (int) ($request['adults'] ?? 1),
            children: (int) ($request['children'] ?? 0),
            rooms: (int) ($request['rooms'] ?? 1),
            page: (int) ($request['page'] ?? 1),
            perPage: (int) ($request['per_page'] ?? 10),
            roomCategoryId: isset($request['room_category_id']) ? (int) $request['room_category_id'] : null,
            minPrice: isset($request['min_price']) ? (float) $request['min_price'] : null,
            maxPrice: isset($request['max_price']) ? (float) $request['max_price'] : null,
            numberOfBeds: isset($request['number_of_beds']) ? (int) $request['number_of_beds'] : null,
            minSize: isset($request['min_size']) ? (float) $request['min_size'] : null,
            maxSize: isset($request['max_size']) ? (float) $request['max_size'] : null,
            amenities: isset($request['amenities']) ? (array) $request['amenities'] : null,
            isFeatured: isset($request['is_featured']) ? (bool) $request['is_featured'] : null,
            sortBy: $request['sort_by'] ?? null,
            sortDirection: $request['sort_direction'] ?? 'asc',
            with: $request['with'] ?? [],
            paginate: isset($request['paginate']) && (bool) $request['paginate'],
        );
    }

    protected function getDefaultRelations(): array
    {
        return [
            'amenities',
            'amenities.metadata',
            'slugable',
            'activeBookingRooms' => function ($query) {
                return $query
                    ->whereNot('status', BookingStatusEnum::CANCELLED)
                    ->where(function ($query) {
                        return $query
                            ->where(function ($query) {
                                return $query
                                    ->whereDate('start_date', '>=', $this->startDate)
                                    ->whereDate('start_date', '<=', $this->endDate);
                            })
                            ->orWhere(function ($query) {
                                return $query
                                    ->whereDate('end_date', '>=', $this->startDate)
                                    ->whereDate('end_date', '<=', $this->endDate);
                            })
                            ->orWhere(function ($query) {
                                return $query
                                    ->whereDate('start_date', '<=', $this->startDate)
                                    ->whereDate('end_date', '>=', $this->endDate);
                            })
                            ->orWhere(function ($query) {
                                return $query
                                    ->whereDate('start_date', '>=', $this->startDate)
                                    ->whereDate('end_date', '<=', $this->endDate);
                            });
                    });
            },
            'activeRoomDates' => function ($query) {
                return $query
                    ->whereDate('start_date', '>=', $this->startDate)
                    ->whereDate('end_date', '<=', $this->endDate)
                    ->take(42);
            },
        ];
    }

    public function toArray(): array
    {
        $dateFormat = config('plugins.hotel.hotel.date_format', 'd-m-Y');

        return [
            'keyword' => $this->keyword,
            'start_date' => $this->startDate?->format($dateFormat),
            'end_date' => $this->endDate?->format($dateFormat),
            'adults' => $this->adults,
            'children' => $this->children,
            'rooms' => $this->rooms,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'room_category_id' => $this->roomCategoryId,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'number_of_beds' => $this->numberOfBeds,
            'min_size' => $this->minSize,
            'max_size' => $this->maxSize,
            'amenities' => $this->amenities,
            'is_featured' => $this->isFeatured,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'with' => $this->with,
            'paginate' => $this->paginate,
        ];
    }
}
