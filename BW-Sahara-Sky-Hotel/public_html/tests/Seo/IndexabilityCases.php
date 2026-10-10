<?php

use Botble\Base\Supports\Action;
use Botble\Hotel\Http\Controllers\PublicController;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Supports\SeoIndexability;
use Botble\SeoHelper\Entities\MiscTags;

test('Canonical drops query strings and renders a single link tag', function (): void {
    $tags = new MiscTags();
    $tags->setUrl('https://hotel.example/ar/rooms/deluxe-room?start_date=01-11-2026&adults=2');
    $html = $tags->render();
    check(str_contains($html, '<link rel="canonical" href="https://hotel.example/ar/rooms/deluxe-room">'), 'Unexpected canonical: ' . $html);
    check(substr_count($html, 'rel="canonical"') === 1, 'Canonical rendered more than once.');
});

test('A later robots directive replaces the earlier one', function (): void {
    $tags = new MiscTags();
    $tags->add('robots', 'index, follow');
    $tags->add('robots', SeoIndexability::NOINDEX);
    $html = $tags->render();
    check(substr_count($html, 'name="robots"') === 1, 'Duplicate robots meta: ' . $html);
    check(str_contains($html, 'content="noindex, nofollow"'), 'Noindex was not kept: ' . $html);
});

test('Unpublished-content hook (priority 40) runs after the SEO helper hook (priority 56)', function (): void {
    $action = new Action();
    $order = [];
    $action->addListener('render_single', function () use (&$order): void { $order[] = 'hotel-indexability'; }, 40);
    $action->addListener('render_single', function () use (&$order): void { $order[] = 'seo-helper'; }, 56);
    $action->fire('render_single', []);
    check($order === ['seo-helper', 'hotel-indexability'], 'Hook order: ' . implode(', ', $order));
});

test('Booking, checkout, account and search pages are noindex; public content is not', function (): void {
    foreach (['public.booking.form', 'public.booking.information', 'public.search', 'customer.login', 'customer.register',
        'customer.password.request', 'customer.password.reset', 'customer.overview', 'customer.bookings.show'] as $route) {
        check(SeoIndexability::isPrivateRoute($route), "$route should be noindex.");
    }
    foreach (['public.index', 'public.single', 'public.rooms', 'public.sitemap', null] as $route) {
        check(! SeoIndexability::isPrivateRoute($route), ($route ?? 'unnamed route') . ' should stay indexable.');
    }
});

test('Only unpublished content records are flagged as noindex', function (): void {
    $room = fn (array $attributes) => (new Room())->setRawAttributes($attributes);
    check(SeoIndexability::isUnpublished($room(['status' => 'draft'])), 'Draft room was indexable.');
    check(SeoIndexability::isUnpublished($room(['status' => 'pending'])), 'Pending room was indexable.');
    check(! SeoIndexability::isUnpublished($room(['status' => 'published'])), 'Published room was noindex.');
    check(! SeoIndexability::isUnpublished($room(['name' => 'No status column loaded'])), 'Missing status treated as unpublished.');
    check(! SeoIndexability::isUnpublished(null), 'Null object treated as unpublished.');
});

test('Every public hotel page sets a self-referencing canonical', function (): void {
    $expected = [
        'getRooms' => "route('public.rooms')",
        'getRoom' => '$room->url',
        'getRoomCategory' => '$category->url',
        'getPlace' => '$place->url',
        'getService' => '$service->url',
        'getFood' => '$food->url',
    ];
    foreach ($expected as $method => $url) {
        check(str_contains(method_source(PublicController::class, $method), "SeoHelper::meta()->setUrl($url)"), "$method has no canonical from $url.");
    }
});

test('Hotel detail pages apply admin SEO meta through the single-render hook', function (): void {
    foreach (['getRoom', 'getRoomCategory', 'getPlace', 'getService', 'getFood'] as $method) {
        check(str_contains(method_source(PublicController::class, $method), 'do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE'), "$method skips SEO meta.");
    }
    $provider = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/hotel/src/Providers/HotelServiceProvider.php');
    check((bool) preg_match('/SeoHelper::registerModule\(\[[^\]]*Food::class/s', $provider), 'Food has no SEO meta box.');
    check(str_contains($provider, 'SeoIndexability::isPrivateRoute($event->route->getName())'), 'Private route noindex listener missing.');
});
