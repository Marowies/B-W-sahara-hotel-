<?php

use App\Http\Controllers\HotelBookingStartController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Illuminate\Validation\ValidationException;

test('Booking handoff uses configured dates and creates no reservation', function () use ($app, $router, $url, $session, $db): void {
    $router->get('booking/{token}', fn () => 'test')->name('public.booking.form');
    $router->getRoutes()->refreshNameLookups();
    $url->setRoutes($router->getRoutes());
    $app->instance('redirect', new Illuminate\Routing\Redirector($url));
    $before = $db->table('ht_bookings')->count();
    $request = authRequest(['room_id' => 1, 'arrival' => '2026-11-10', 'departure' => '2026-11-12', 'adults' => 2, 'children' => 0,
        'amount' => 0, 'customer_id' => 999, 'payment_method' => 'stripe']);
    $result = (new HotelBookingStartController())->start($request, new BaseHttpResponse());
    $data = $result->getData(true);
    check($result->getStatusCode() === 200 && ! $data['error'], 'Valid selection failed');
    check(preg_match('~/booking/[a-f0-9]{32}$~D', $data['next_url']) === 1, 'Missing existing booking form URL');
    $selection = $session->get($session->get('checkout_token'));
    check($selection['start_date'] === '10-11-2026' && $selection['end_date'] === '12-11-2026', 'Configured dates lost');
    check($selection['room_id'] === 1 && $selection['adults'] === 2 && $selection['rooms'] === 1, 'Selection lost');
    check(! isset($selection['amount'], $selection['customer_id'], $selection['payment_method']), 'Untrusted fields entered the checkout session');
    check($db->table('ht_bookings')->count() === $before, 'Starting a booking reserved inventory');
});

test('Booking handoff rejects invalid dates, draft rooms and excessive inventory', function (): void {
    $valid = ['room_id' => 1, 'arrival' => '2026-11-10', 'departure' => '2026-11-12', 'adults' => 2, 'children' => 0];
    foreach ([['arrival' => '2026-02-31'], ['departure' => '2026-11-09'], ['room_id' => 2], ['rooms' => 6], ['adults' => -1], ['arrival' => []]] as $invalid) {
        try {
            (new HotelBookingStartController())->start(authRequest(array_replace($valid, $invalid)), new BaseHttpResponse());
            throw new RuntimeException('Invalid selection accepted');
        } catch (ValidationException) {}
    }
});

test('Booking start route requires customer access token', function () use ($source, $router): void {
    require $source . '/routes/web.php';
    $route = $router->getRoutes()->match(Illuminate\Http\Request::create('/api/hotel/booking/start', 'POST'));
    check(in_array(App\Http\Middleware\RequireHotelCustomer::class, $route->gatherMiddleware(), true), 'Booking handoff lacks authentication');
});
