<?php

// Loaded after application/connection bootstrap, without recreating fixtures.
$worker = (int) ($argv[4] ?? 0);
$startAt = (float) ($argv[5] ?? 0);
$kind = $argv[6] ?? 'checkout';
if (str_starts_with($kind, 'auth-')) {
    Carbon\Carbon::setTestNow('2026-10-04 12:00:00');
    while (microtime(true) < $startAt) { usleep(10000); }
    $token = $argv[7] ?? '';
    $request = Illuminate\Http\Request::create('https://hotel.example/api/hotel/auth', 'POST');
    $service = app(App\Services\HotelTokenService::class);
    if ($kind === 'auth-refresh') {
        $result = $service->refresh($token, $request);
        echo json_encode(['ok' => $result['ok'], 'hash' => isset($result['refresh_token']) ? hash('sha256', $result['refresh_token']) : null]);
    } elseif ($kind === 'auth-logout') {
        $service->logout($token, null);
        echo json_encode(['ok' => true]);
    } else {
        $request->merge(['email' => 'synthetic@example.invalid', 'code' => '123456']);
        $response = (new App\Http\Controllers\HotelCustomerAuthController($service))->verifyCode($request);
        echo json_encode(['status' => $response->getStatusCode()]);
    }
    return;
}
if ($kind === 'stripe-webhook') {
    require __DIR__ . '/payment-worker.php';
    return;
}
if ($kind === 'performance') {
    require dirname(__DIR__) . '/Performance/handlers.php';
    return;
}
$roomId = (int) ($argv[7] ?? 900);
$marker = $argv[8] ?? '';
while (microtime(true) < $startAt) { usleep(10000); }
if ($kind === 'ical' || $kind === 'ical-hold') {
    if ($kind === 'ical-hold') {
        $capsule->getConnection()->listen(function ($query) use ($marker): void {
            if (str_contains(strtolower($query->sql), 'for update') && str_contains($query->sql, 'ht_rooms')) {
                file_put_contents($marker, 'calendar-locked');
                usleep(1000000);
            }
        });
    }
    $deadline = microtime(true) + 10;
    if ($kind === 'ical') {
        while (! is_file($marker) && microtime(true) < $deadline) { usleep(10000); }
        if (! is_file($marker)) { throw new RuntimeException('Checkout barrier was not reached.'); }
    }
    $service = new class extends Botble\Hotel\Services\ICalService {
        protected function fetchCalendarContent(string $url): ?string {
            return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:race@example.invalid\r\nDTSTART;VALUE=DATE:20261105\r\nDTEND;VALUE=DATE:20261107\r\nEND:VEVENT\r\nEND:VCALENDAR";
        }
    };
    Illuminate\Support\Facades\Log::swap(new class { public function error(...$args): void {} });
    $result = $service->syncExternalCalendars(Botble\Hotel\Models\Room::query()->with('calendars')->findOrFail($roomId));
    echo json_encode(['worker' => $worker, 'result' => $result['created'] === 1 ? 'imported' : 'not-imported']);
    return;
}
if ($kind === 'checkout-wait') {
    $deadline = microtime(true) + 10;
    while (! is_file($marker) && microtime(true) < $deadline) { usleep(10000); }
    if (! is_file($marker)) { throw new RuntimeException('Calendar barrier was not reached.'); }
}
$data = [
    'token' => str_repeat(dechex($worker), 32), 'room_id' => $roomId,
    'start_date' => '05-11-2026', 'end_date' => '07-11-2026', 'rooms' => 1,
    'number_of_guests' => 1, 'number_of_children' => 0,
    'first_name' => 'Synthetic', 'last_name' => 'Concurrent',
    'email' => 'synthetic@example.invalid', 'phone' => '00000000', 'terms_conditions' => 1,
];
$session->put($data['token'], ['room_id' => $roomId, 'start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'rooms' => 1, 'adults' => 1, 'children' => 0]);
$request = Botble\Hotel\Http\Requests\CheckoutRequest::create('/checkout', 'POST', $data);
$customer = Botble\Hotel\Models\Customer::query()->findOrFail(43);
$pair = app(App\Services\HotelTokenService::class)->issue($customer, $request);
$request->headers->set('Authorization', 'Bearer ' . $pair['access_token']);
$validation = $validator->make($data, $request->rules());
$validation->validate();
$request->setValidator($validation);
$session->put('checkout_token', $data['token']);
// Hold the first lock briefly: the other processes must wait on a real InnoDB lock.
$capsule->getConnection()->listen(function ($query) use ($kind, $marker): void {
    if (str_contains(strtolower($query->sql), 'for update') && str_contains($query->sql, 'ht_rooms')) { usleep(250000); }
    if ($kind === 'checkout-hold' && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'ht_booking_rooms')) {
        file_put_contents($marker, 'snapshot-ready');
        usleep(1000000);
    }
});
try {
    $controller = new Botble\Hotel\Http\Controllers\PublicController(new Botble\Hotel\Services\GetRoomService());
    $controller->postCheckout($request, new Botble\Base\Http\Responses\BaseHttpResponse());
    echo json_encode(['worker' => $worker, 'result' => 'booked']);
} catch (Illuminate\Validation\ValidationException) {
    echo json_encode(['worker' => $worker, 'result' => 'unavailable']);
}
