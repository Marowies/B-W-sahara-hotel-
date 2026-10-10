<?php

namespace App\Http\Controllers;

use Botble\Contact\Enums\ContactStatusEnum;
use Botble\Contact\Models\Contact;
use Botble\Hotel\DataTransferObjects\RoomSearchParams;
use Botble\Hotel\Facades\Currency;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Services\GetRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class HotelFrontendController extends Controller
{
    public function rooms(Request $request): JsonResponse
    {
        $input = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $page = (int) ($input['page'] ?? 1);
        $perPage = (int) ($input['per_page'] ?? 100);
        // Cache only a public presentation DTO. Never cache availability or a payable quote.
        $generation = Cache::get('hotel.catalogue.generation', 'initial');
        $key = 'hotel.catalogue.' . hash('sha256', json_encode([app()->getLocale(), $generation, $page, $perPage]));
        $data = Cache::remember($key, 30, function () use ($page, $perPage): array {
            $result = Room::query()->wherePublished()->with(['slugable', 'currency'])
                ->orderBy('order')->orderBy('id')->paginate($perPage,
                    ['id', 'name', 'description', 'price', 'currency_id', 'max_adults', 'max_children', 'number_of_beds', 'size'], 'page', $page);
            return ['rooms' => $result->getCollection()->map(fn (Room $room) => $this->room($room))->values()->all(),
                'pagination' => ['page' => $result->currentPage(), 'per_page' => $result->perPage(),
                    'total' => $result->total(), 'last_page' => $result->lastPage()]];
        });
        // HTTP remains private to avoid caching Laravel session cookies at a CDN.
        return $this->reply($data);
    }

    public function availability(Request $request, GetRoomService $rooms): JsonResponse
    {
        $input = $request->validate([
            'arrival' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'departure' => ['required', 'date_format:Y-m-d', 'after:arrival'],
            'adults' => ['required', 'integer', 'min:1', 'max:30'],
            'children' => ['required', 'integer', 'min:0', 'max:30'],
            'rooms' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $format = HotelHelper::getDateFormat();
        $params = RoomSearchParams::fromRequest([
            'start_date' => Carbon::parse($input['arrival'])->format($format),
            'end_date' => Carbon::parse($input['departure'])->format($format),
            'adults' => $input['adults'], 'children' => $input['children'],
            'rooms' => $input['rooms'] ?? 1, 'per_page' => $input['per_page'] ?? 50,
            'page' => $input['page'] ?? 1,
        ]);
        $params->with[] = 'currency';
        $result = $rooms->getAvailableRooms($params);
        return $this->reply(['rooms' => $result->getCollection()->map(fn (Room $room) => $this->room($room))->values(), 'total' => $result->total(),
            'pagination' => ['page' => $result->currentPage(), 'per_page' => $result->perPage(), 'total' => $result->total(), 'last_page' => $result->lastPage()]]);
    }

    public function session(): JsonResponse
    {
        $customer = auth('customer')->user();

        return $this->reply(['csrf_token' => csrf_token(), 'ui_locale' => $customer?->ui_locale, 'authenticated' => (bool) $customer, 'email' => $customer?->email]);
    }

    public function locale(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        if (! $customer) {
            return $this->reply(['message' => 'Customer authentication required.'], 401);
        }
        $data = $request->validate(['ui_locale' => ['required', 'string', 'in:en,ar,zh']]);
        $customer->forceFill(['ui_locale' => $data['ui_locale']])->save();
        return $this->reply(['ui_locale' => $data['ui_locale']]);
    }

    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:3000'],
        ]);
        Contact::query()->create([
            'name' => $data['name'], 'email' => $data['email'], 'subject' => $data['subject'],
            'content' => $data['message'], 'status' => ContactStatusEnum::UNREAD,
        ]);
        return $this->reply(['saved' => true], 201);
    }

    private function room(Room $room): array
    {
        return [
            'id' => $room->getKey(), 'slug' => $room->slugable?->key,
            'name' => strip_tags($room->name), 'description' => strip_tags($room->description ?? ''),
            'price' => (float) $room->price, 'currency' => $room->currency?->title ?? Currency::getDefaultCurrency()?->title,
            'max_adults' => (int) $room->max_adults, 'max_children' => (int) $room->max_children,
            'number_of_beds' => (int) $room->number_of_beds, 'size' => (float) $room->size,
        ];
    }

    private function reply(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store');
    }
}
