<?php

use Botble\Hotel\DataTransferObjects\RoomSearchParams;
use Botble\Hotel\Http\Controllers\PublicController;
use Botble\Hotel\Http\Requests\CheckoutRequest;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Services\BookingPricingService;
use Botble\Hotel\Services\GetRoomService;
use Illuminate\Http\Request;

$started = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);$queries = 0;$queryMs = 0.0;
$db->listen(function ($query) use (&$queries, &$queryMs): void { $queries++;$queryMs += $query->time; });
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$app->instance('request', Request::create('http://127.0.0.1' . $_SERVER['REQUEST_URI'], $method, $_GET));
header('Content-Type: application/json');
try {
    if ($method === 'GET' && $path === '/diagnostics') {
        $opcache = function_exists('opcache_get_status') ? opcache_get_status(false) : false;
        echo json_encode(['ok' => true, 'opcache_enabled' => $opcache['opcache_enabled'] ?? false, 'statistics' => $opcache['opcache_statistics'] ?? null]);
        return;
    }
    $params = RoomSearchParams::fromRequest(['start_date' => '05-11-2026', 'end_date' => '07-11-2026', 'adults' => 1, 'rooms' => 1, 'per_page' => 10]);
    // This lab does not render CMS templates, slugs or amenities.
    $params->with = $params->availabilityRelations();
    if ($method === 'GET' && $path === '/rooms') {
        $rooms = (new GetRoomService())->getRooms($params);
        $result = ['ok' => true, 'total' => $rooms->total(), 'ids' => $rooms->getCollection()->pluck('id')->all()];
    } elseif ($method === 'GET' && $path === '/availability') {
        $rooms = (new GetRoomService())->getAvailableRooms($params);
        $result = ['ok' => true, 'total' => $rooms->total(), 'ids' => $rooms->getCollection()->pluck('id')->all()];
    } elseif ($method === 'GET' && in_array($path, ['/rooms/1001', '/quote'], true)) {
        $room = Room::query()->with($params->availabilityRelations())->findOrFail(1001);
        $room->total_price = $room->getRoomTotalPrice($params->startDate, $params->endDate, 1);
        [$amount, $discount] = (new BookingPricingService())->calculate($room, [], 2, 1);
        $result = ['ok' => true, 'room_id' => $room->id, 'amount' => $amount, 'discount' => $discount];
    } elseif ($method === 'POST' && $path === '/checkout') {
        $token = bin2hex(random_bytes(16));
        $data = ['token' => $token, 'room_id' => 1001, 'start_date' => '05-11-2026', 'end_date' => '07-11-2026', 'rooms' => 1,
            'number_of_guests' => 1, 'number_of_children' => 0, 'first_name' => 'Synthetic', 'last_name' => 'Load',
            'email' => 'load@example.invalid', 'phone' => '00000000', 'terms_conditions' => 1];
        $session->put($token, ['room_id' => 1001, 'start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'rooms' => 1, 'adults' => 1, 'children' => 0]);
        $session->put('checkout_token', $token);
        $request = CheckoutRequest::create('/checkout', 'POST', $data);
        $validation = $validator->make($data, $request->rules());$validation->validate();$request->setValidator($validation);
        (new PublicController(new GetRoomService()))->postCheckout($request, new Botble\Base\Http\Responses\BaseHttpResponse());
        $result = ['ok' => true, 'booking_id' => $db->table('ht_bookings')->where('transaction_id', $session->get('booking_transaction_id'))->value('id')];
    } else {
        http_response_code(404);$result = ['ok' => false, 'error' => 'Unknown lab route'];
    }
    header('Server-Timing: app;dur=' . round((microtime(true) - $started) * 1000, 2) . ', db;dur=' . round($queryMs, 2));
    header('X-Lab-Queries: ' . $queries);
    echo json_encode($result, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);echo json_encode(['ok' => false, 'error' => $exception->getMessage()]);
}
