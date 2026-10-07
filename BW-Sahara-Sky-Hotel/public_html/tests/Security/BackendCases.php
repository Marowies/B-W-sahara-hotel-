<?php

// Additional regressions share the isolated database and assertions from run.php.
use Botble\Hotel\DataTransferObjects\RoomSearchParams;
use Botble\Hotel\DataTransferObjects\StayDates;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Services\BookingPricingService;
use Botble\Hotel\Services\GetRoomService;
use Illuminate\Validation\ValidationException;

$db->statement('ALTER TABLE ht_rooms ADD COLUMN created_at TEXT');
$db->statement('ALTER TABLE ht_rooms ADD COLUMN room_category_id INTEGER');
$db->statement('ALTER TABLE ht_rooms ADD COLUMN number_of_beds INTEGER');
$db->statement('ALTER TABLE ht_rooms ADD COLUMN size REAL');
$db->statement('ALTER TABLE ht_rooms ADD COLUMN is_featured INTEGER');
$db->statement('ALTER TABLE ht_services ADD COLUMN price REAL');
$db->statement('ALTER TABLE ht_services ADD COLUMN price_type TEXT');
$db->statement('ALTER TABLE ht_foods ADD COLUMN price REAL');
$db->statement('CREATE TABLE ht_room_categories (id INTEGER PRIMARY KEY, name TEXT)');
$db->statement('CREATE TABLE ht_amenities (id INTEGER PRIMARY KEY, name TEXT)');
$db->statement('CREATE TABLE ht_rooms_amenities (room_id INTEGER, amenity_id INTEGER)');
$db->statement('CREATE TABLE ht_coupons (id INTEGER PRIMARY KEY, code TEXT, type TEXT, value REAL, expires_date TEXT, quantity INTEGER, total_used INTEGER)');
$db->table('ht_room_categories')->insert(['id' => 1, 'name' => 'Synthetic']);
$app->instance('request', Illuminate\Http\Request::create('https://hotel.example/rooms', 'GET', ['rooms' => 2]));

function transactionTest(string $name, Closure $callback): void
{
    global $db, $hotel;
    test($name, function () use ($callback, $db, $hotel): void {
        $state = $hotel->checkoutData;
        $db->beginTransaction();
        try { $callback(); }
        finally { $db->rollBack(); $hotel->checkoutData = $state; }
    });
}

test('Search defaults provide positive nights and canonical selection', function (): void {
    $params = RoomSearchParams::fromRequest([]);
    check($params->startDate->toDateString() === '2026-10-04' && $params->endDate->toDateString() === '2026-10-05', 'Default dates changed.');
    check($params->adults === 1 && $params->rooms === 1 && $params->paginate === true, 'Invalid search defaults.');
});
test('Search rejects invalid dates, arrays, negative quantities and oversized pages', function (): void {
    foreach ([['start_date' => 'garbage'], ['start_date' => ['unexpected']], ['adults' => -5], ['rooms' => -3], ['per_page' => 100000], ['end_date' => '04-10-2026'], ['end_date' => '05-10-2028'], ['sort_direction' => 'invalid'], ['min_price' => 20, 'max_price' => 10]] as $bad) {
        try { RoomSearchParams::fromRequest($bad); throw new RuntimeException('Bad search accepted: ' . json_encode($bad)); }
        catch (ValidationException) {}
    }
});
test('Date parsing uses configured format and accepts stored ISO dates without ambiguity', function () use ($hotel): void {
    $hotel->dateFormat = 'd/m/Y';
    try {
        $params = RoomSearchParams::fromRequest(['start_date' => '20/10/2026', 'end_date' => '22/10/2026']);
        check($params->startDate->toDateString() === '2026-10-20', 'Configured date format ignored.');
        check(StayDates::from('20/10/2026', '22/10/2026')->nights() === 2, 'Configured dates not priced correctly.');
        check(StayDates::from('2026-10-20', '2026-10-22')->nights() === 2, 'Stored dates rejected.');
    } finally { $hotel->dateFormat = 'd-m-Y'; }
    rejects(fn () => StayDates::from('31-02-2027', '03-03-2027'));
});
test('Search ignores public attempts to load arbitrary relationships', function (): void {
    $params = RoomSearchParams::fromRequest(['with' => ['not_a_relation'], 'paginate' => false]);
    check(! in_array('not_a_relation', $params->with, true) && $params->paginate, 'Public relationship injection accepted.');
});
test('Booking selection helper preserves guests, rooms and positive nights', function () use ($app): void {
    $app->instance('request', Illuminate\Http\Request::create('https://hotel.example/rooms', 'GET', [
        'start_date' => '20-10-2026', 'end_date' => '22-10-2026', 'adults' => 2, 'children' => 1, 'rooms' => 3,
    ]));
    $selection = (new Botble\Hotel\Supports\HotelSupport())->getRoomBookingParams();
    check(array_slice($selection, 2) === [2, 2, 1, 3], 'Guest/room selection or nights lost.');
});

transactionTest('Available-room pagination counts all matches before slicing pages', function () use ($db): void {
    foreach (range(101, 107) as $id) {
        $db->table('ht_rooms')->insert(['id' => $id, 'name' => 'Catalog ' . $id, 'images' => '[]', 'status' => 'published', 'price' => 100, 'number_of_rooms' => 2, 'max_adults' => 2, 'max_children' => 1, 'room_category_id' => 1, 'created_at' => '2026-10-04']);
    }
    $db->table('ht_rooms')->where('id', 102)->update(['status' => 'draft']);
    $db->table('ht_room_dates')->insert(['id' => 101, 'room_id' => 101, 'start_date' => '2026-10-20', 'active' => 0, 'number_of_rooms' => 2, 'value' => 100]);
    $params = RoomSearchParams::fromRequest(['room_category_id' => 1, 'start_date' => '20-10-2026', 'end_date' => '22-10-2026', 'per_page' => 2, 'rooms' => 2]);
    $params->with = $params->availabilityRelations(); // Slug/metadata module registration is outside this isolated test.
    $service = new GetRoomService();
    $first = $service->getAvailableRooms($params);
    check($first->total() === 5 && $first->lastPage() === 3 && $first->count() === 2, 'Incorrect available count or page size.');
    check($first->getCollection()->pluck('id')->all() === [103, 104], 'Unavailable rooms consumed the first page.');
    check((float) $first->getCollection()->first()->total_price === 400.0, 'Room quantity omitted from quote.');
    $params->page = 2;
    check($service->getAvailableRooms($params)->getCollection()->pluck('id')->all() === [105, 106], 'Second page duplicated/missed results.');
    $params->page = 3;
    check($service->getAvailableRooms($params)->getCollection()->pluck('id')->all() === [107], 'Last page lost.');
    $params->page = 4;
    $empty = $service->getAvailableRooms($params);
    check($empty->count() === 0 && $empty->total() === 5, 'Out-of-range page lost the total.');
});

test('Per-night inventory uses overrides and excludes the checkout day', function (): void {
    $room = new Room();
    $room->setRawAttributes(['price' => 100, 'number_of_rooms' => 2, 'max_adults' => 2, 'max_children' => 1]);
    $room->setRelation('activeRoomDates', new Illuminate\Database\Eloquent\Collection());
    $room->setRelation('activeBookingRooms', new Illuminate\Database\Eloquent\Collection([
        (object) ['start_date' => '2026-10-19', 'end_date' => '2026-10-20', 'number_of_rooms' => 2],
        (object) ['start_date' => '2026-10-22', 'end_date' => '2026-10-24', 'number_of_rooms' => 2],
    ]));
    check($room->isAvailableAt(['start_date' => '2026-10-20', 'end_date' => '2026-10-22', 'rooms' => 2]), 'Adjacent bookings incorrectly overlap.');
    $room->setRelation('activeRoomDates', new Illuminate\Database\Eloquent\Collection([
        (object) ['start_date' => '2026-10-20', 'active' => true, 'number_of_rooms' => 1, 'value' => 0],
    ]));
    check(! $room->isAvailableAt(['start_date' => '2026-10-20', 'end_date' => '2026-10-22', 'rooms' => 2]), 'Per-night quantity ignored.');
    check(! $room->isAvailableAt(['rooms' => 9999]), 'Undated request bypasses capacity.');
});
test('Nightly pricing handles fixed, amount and percentage overrides for every room', function (): void {
    $room = new Room();
    $room->setRawAttributes(['price' => 100]);
    $room->setRelation('activeRoomDates', new Illuminate\Database\Eloquent\Collection([
        (object) ['start_date' => '2026-10-20', 'active' => true, 'value' => 150, 'value_type' => 'fixed'],
        (object) ['start_date' => '2026-10-21', 'active' => true, 'value' => -20, 'value_type' => 'amount_adjust'],
        (object) ['start_date' => '2026-10-22', 'active' => true, 'value' => 10, 'value_type' => 'percentage_adjust'],
    ]));
    check((float) $room->getRoomTotalPrice('2026-10-20', '2026-10-23', 2) === 680.0, 'Incorrect mixed nightly quote.');
    rejects(fn () => $room->getRoomTotalPrice('2026-10-20', '2026-10-20', 1));
    rejects(fn () => $room->getRoomTotalPrice('2026-10-22', '2026-10-20', 1));
    rejects(fn () => $room->getRoomTotalPrice('2026-10-20', '2026-10-22', -1));
});

transactionTest('Pricing service includes published extras, food and bounded coupon', function () use ($db, $hotel): void {
    $db->table('ht_services')->where('id', 1)->update(['price' => 10, 'price_type' => 'per_day']);
    $db->table('ht_services')->where('id', 2)->update(['price' => 999, 'price_type' => 'per_day']);
    $db->table('ht_foods')->insert(['id' => 1, 'status' => 'published', 'price' => 30]);
    $db->table('ht_coupons')->insert(['id' => 1, 'code' => 'SYNTHETIC', 'type' => 'fixed', 'value' => 999, 'expires_date' => '2026-12-31', 'quantity' => 10, 'total_used' => 0]);
    $hotel->checkoutData = ['coupon_code' => 'SYNTHETIC'];
    $room = Room::query()->findOrFail(1);
    $room->total_price = 400;
    [$amount, $discount] = (new BookingPricingService())->calculate($room, [1, 2], 2, 2, [1]);
    check((float) $amount === 470.0 && (float) $discount === 470.0, 'Extra/food/coupon quote mismatch.');
    check((float) $hotel->checkoutData['coupon_amount'] === 470.0 && $hotel->checkoutData['selected_services'] === [1], 'Session quote differs from checkout.');
});
transactionTest('Expired coupons are cleared from displayed checkout state', function () use ($db, $hotel): void {
    $db->table('ht_coupons')->insert(['id' => 1, 'code' => 'EXPIRED', 'type' => 'fixed', 'value' => 50, 'expires_date' => '2026-01-01', 'quantity' => 10, 'total_used' => 0]);
    $hotel->checkoutData = ['coupon_code' => 'EXPIRED', 'coupon_amount' => 50];
    $room = Room::query()->findOrFail(1);
    $room->total_price = 200;
    [$amount, $discount] = (new BookingPricingService())->calculate($room);
    check((float) $discount === 0.0 && $hotel->checkoutData['coupon_code'] === null, 'Expired coupon remains displayed.');
});
transactionTest('Checkout refuses changes to initial room/date/guest selection', function () use ($payload, $db): void {
    $selection = ['room_id' => 1, 'start_date' => $payload['start_date'], 'end_date' => $payload['end_date'], 'adults' => 1, 'children' => 0, 'rooms' => 1];
    $before = $db->table('ht_bookings')->count();
    foreach ([['start_date' => '23-10-2026', 'end_date' => '25-10-2026'], ['rooms' => 2], ['number_of_guests' => 2], ['number_of_children' => 1]] as $changed) {
        try { checkout(array_replace($payload, $changed), $selection); throw new RuntimeException('Changed selection was accepted.'); }
        catch (ValidationException) {}
    }
    check($db->table('ht_bookings')->count() === $before, 'Modified selection persisted.');
});
test('iCalendar export preserves exclusive departure without adding an extra night', function (): void {
    $service = new Botble\Hotel\Services\ICalService();
    $events = $service->parseICalContent($service->generateICalContent(Room::query()->findOrFail(1)));
    check($events->first()['DTSTART'] === '20261020' && $events->first()['DTEND'] === '20261022', 'iCal stay extended by one night.');
});

test('All booking entry points reject array dates and stays beyond the configured limit', function () use ($validator, $payload): void {
    foreach ([Botble\Hotel\Http\Requests\InitBookingRequest::class, Botble\Hotel\Http\Requests\CheckoutRequest::class, Botble\Hotel\Http\Requests\CalculateBookingAmountRequest::class] as $class) {
        foreach ([['room_id' => ['unexpected']], ['start_date' => ['unexpected']], ['end_date' => ['unexpected']], ['end_date' => '22-10-2028']] as $invalid) {
            $data = array_replace($payload, ['adults' => 1], $invalid);
            $request = $class::create('/booking', 'POST', $data);
            $errors = $validator->make($data, $request->rules())->errors();
            check($errors->has(array_key_first($invalid)), 'Invalid date accepted by ' . $class);
        }
    }
});

transactionTest('Stays beyond 42 nights load and price every calendar override', function () use ($db): void {
    $start = Carbon\Carbon::parse('2026-11-01');
    for ($i = 0; $i < 50; $i++) {
        $db->table('ht_room_dates')->insert(['id' => 1000 + $i, 'room_id' => 1, 'start_date' => $start->copy()->addDays($i)->toDateString(), 'active' => 1, 'number_of_rooms' => 5, 'value' => 150, 'value_type' => 'fixed']);
    }
    $params = RoomSearchParams::fromRequest(['start_date' => '01-11-2026', 'end_date' => '21-12-2026']);
    $room = Room::query()->with($params->availabilityRelations())->findOrFail(1);
    check($room->activeRoomDates->count() === 50, 'Calendar overrides truncated.');
    check((float) $room->getRoomTotalPrice($params->startDate, $params->endDate, 2) === 15000.0, 'Long-stay quote truncated.');
});

transactionTest('Nullable optional extras do not crash checkout', function () use ($payload, $db): void {
    $before = $db->table('ht_bookings')->count();
    checkout(array_replace($payload, ['services' => null, 'foods' => null]));
    check($db->table('ht_bookings')->count() === $before + 1, 'Checkout with no extras failed.');
});

transactionTest('Displayed checkout recalculates extras and tax after discount', function () use ($db, $hotel, $session, $router): void {
    $router->post('booking', fn () => null)->name('public.booking');
    $router->getRoutes()->refreshNameLookups();
    Botble\SeoHelper\Facades\SeoHelper::swap(new class { public function setTitle($title): void {} });
    Botble\Optimize\Facades\OptimizerHelper::swap(new class { public function disable(): void {} });
    Botble\Theme\Facades\Theme::swap(new class {
        public function breadcrumb() { return $this; }
        public function add(...$args) { return $this; }
        public function scope($view, $data) {
            return new class($data) {
                public function __construct(public array $data) {}
                public function render() { return $this->data; }
            };
        }
    });
    $db->table('ht_services')->where('id', 1)->update(['price' => 10, 'price_type' => 'per_day']);
    $db->table('ht_taxes')->where('id', 1)->update(['percentage' => 10]);
    $db->table('ht_coupons')->insert(['id' => 1, 'code' => 'QUOTE', 'type' => 'fixed', 'value' => 50, 'expires_date' => '2026-12-31', 'quantity' => 10, 'total_used' => 0]);
    $token = str_repeat('b', 32);
    $selection = ['room_id' => 1, 'start_date' => '01-11-2026', 'end_date' => '03-11-2026', 'adults' => 1, 'children' => 0, 'rooms' => 1, 'selected_services' => [1], 'coupon_code' => 'QUOTE', 'service_amount' => 99999];
    $session->put($token, $selection);
    $hotel->checkoutData = $selection;
    $controller = new Botble\Hotel\Http\Controllers\PublicController(new GetRoomService());
    $quote = $controller->getBooking($token, new Botble\Base\Http\Responses\BaseHttpResponse());
    check((float) $quote['amount'] === 220.0 && (float) $quote['taxAmount'] === 17.0 && (float) $quote['total'] === 187.0, 'Displayed checkout uses stale extras or inconsistent tax.');
    check($session->get('checkout_token') === $token, 'Checkout state is bound to another booking.');
});

test('Room-list Blade renders every result and forwards the complete selection', function () use ($source, $app, $events): void {
    $renderer = new class {
        public array $cards = [];
        public function getThemeNamespace($name) { return $name; }
        public function partial($name, $data) {
            check($name === 'rooms.item', 'Unexpected room partial.');
            check($data['children'] === 1 && $data['numberOfRooms'] === 2, 'Room selection lost while rendering.');
            $this->cards[] = $data['room']->id;

            return '<article>room-' . $data['room']->id . '</article>';
        }
    };
    Botble\Theme\Facades\Theme::swap($renderer);
    if (! class_exists('Theme', false)) {
        class_alias(Botble\Theme\Facades\Theme::class, 'Theme');
    }
    $files = new Illuminate\Filesystem\Filesystem();
    $compiler = new Illuminate\View\Compilers\BladeCompiler($files, sys_get_temp_dir());
    $__env = new Illuminate\View\Factory(new Illuminate\View\Engines\EngineResolver(), new Illuminate\View\FileViewFinder($files, []), $events);
    $rooms = new Illuminate\Pagination\LengthAwarePaginator([(object) ['id' => 10], (object) ['id' => 11]], 2, 10);
    Illuminate\Pagination\Paginator::viewFactoryResolver(fn () => new class {
        public function make($view, $data) {
            check($data['paginator']->lastPage() === 1, 'Unexpected pagination fixture.');

            return new class {
                public function render() { return ''; }
                public function __toString() { return $this->render(); }
            };
        }
    });
    $startDate = Carbon\Carbon::parse('2026-10-20');
    $endDate = Carbon\Carbon::parse('2026-10-22');
    $adults = 2;
    $children = 1;
    $numberOfRooms = 2;
    $nights = 2;
    $compiled = $compiler->compileString($files->get($source . '/platform/themes/riorelax/partials/shortcodes/all-rooms/index.blade.php'));
    ob_start();
    try { eval('?>' . $compiled); $html = ob_get_contents(); }
    finally { ob_end_clean(); }
    check($renderer->cards === [10, 11] && str_contains($html, 'room-10') && str_contains($html, 'room-11'), 'Only one card rendered.');
});
