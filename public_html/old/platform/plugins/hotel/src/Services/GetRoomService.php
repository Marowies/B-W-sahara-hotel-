<?php

namespace Botble\Hotel\Services;

use Botble\Hotel\DataTransferObjects\RoomSearchParams;
use Botble\Hotel\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class GetRoomService
{
    public function getRooms(RoomSearchParams $params): LengthAwarePaginator
    {
        $query = Room::query()
            ->wherePublished();

        // Handle keyword search - search in room name and amenities
        if ($params->keyword) {
            $query->where(function ($query) use ($params): void {
                $query->where('name', 'like', "%{$params->keyword}%")
                    ->orWhereHas('amenities', function ($query) use ($params): void {
                        $query->where('name', 'like', "%{$params->keyword}%");
                    });
            });
        }

        // Filter by room category
        if ($params->roomCategoryId) {
            $query->where('room_category_id', $params->roomCategoryId);
        }

        // Filter by price range
        if ($params->minPrice) {
            $query->where('price', '>=', $params->minPrice);
        }
        if ($params->maxPrice) {
            $query->where('price', '<=', $params->maxPrice);
        }

        // Filter by number of beds
        if ($params->numberOfBeds) {
            $query->where('number_of_beds', '>=', $params->numberOfBeds);
        }

        // Filter by room size
        if ($params->minSize) {
            $query->where('size', '>=', $params->minSize);
        }
        if ($params->maxSize) {
            $query->where('size', '<=', $params->maxSize);
        }

        // Filter by amenities
        if ($params->amenities) {
            foreach ($params->amenities as $amenityId) {
                $query->whereHas('amenities', function (Builder $query) use ($amenityId): void {
                    $query->where('amenity_id', $amenityId);
                });
            }
        }

        // Filter by featured rooms
        if ($params->isFeatured !== null) {
            $query->where('is_featured', $params->isFeatured);
        }

        // Sort results
        if ($params->sortBy) {
            switch ($params->sortBy) {
                case 'price':
                    $query->orderBy('price', $params->sortDirection);

                    break;
                case 'name':
                    $query->orderBy('name', $params->sortDirection);

                    break;
                case 'created_at':
                    $query->orderBy('created_at', $params->sortDirection);

                    break;
                case 'number_of_beds':
                    $query->orderBy('number_of_beds', $params->sortDirection);

                    break;
                case 'size':
                    $query->orderBy('size', $params->sortDirection);

                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        // Eager load relationships
        if (! empty($params->with)) {
            $query->with($params->with);
        }

        return $query->paginate(
            $params->perPage,
            ['*'],
            'page',
            $params->page
        );
    }

    public function getAvailableRooms(RoomSearchParams $params): LengthAwarePaginator
    {
        $dateFormat = config('plugins.hotel.hotel.date_format', 'd-m-Y');

        $rooms = $this->getRooms($params);

        $availableRooms = collect();
        foreach ($rooms->items() as $room) {
            if ($room->isAvailableAt([
                'start_date' => $params->startDate?->format($dateFormat),
                'end_date' => $params->endDate?->format($dateFormat),
                'adults' => $params->adults,
                'children' => $params->children,
                'rooms' => $params->rooms,
            ])) {
                $room->total_price = $room->getRoomTotalPrice(
                    $params->startDate?->format($dateFormat),
                    $params->endDate?->format($dateFormat)
                );
                $availableRooms->push($room);
            }
        }

        return new LengthAwarePaginator(
            $availableRooms,
            $availableRooms->count(),
            $params->perPage,
            $params->page,
            ['path' => request()->url()]
        );
    }

    public function getRelatedRooms(int $roomId, int $limit = 2, array $params = []): Collection
    {
        $query = Room::query()
            ->wherePublished()
            ->where('id', '!=', $roomId);

        // Get rooms from the same category
        $room = Room::query()->find($roomId);
        if ($room && $room->room_category_id) {
            $query->where('room_category_id', $room->room_category_id);
        }

        if (! empty($params['with'])) {
            $query->with($params['with']);
        }

        return $query->limit($limit)->get();
    }
}
