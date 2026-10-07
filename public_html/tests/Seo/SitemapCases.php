<?php

use Botble\Base\Supports\MacroableModels;
use Botble\Hotel\Listeners\AddSitemapListener;
use Botble\Hotel\Models\Food;
use Botble\Hotel\Models\Place;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCategory;
use Botble\Hotel\Models\Service;
use Botble\Slug\Models\Slug;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule($app);
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$app->instance('db', $capsule->getDatabaseManager());
foreach (['ht_rooms (id INTEGER PRIMARY KEY, name TEXT, status TEXT, room_category_id INTEGER, created_at TEXT, updated_at TEXT)',
    'ht_room_categories (id INTEGER PRIMARY KEY, name TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
    'ht_services (id INTEGER PRIMARY KEY, name TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
    'ht_places (id INTEGER PRIMARY KEY, name TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
    'ht_foods (id INTEGER PRIMARY KEY, name TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
    'slugs (id INTEGER PRIMARY KEY, "key" TEXT, reference_type TEXT, reference_id INTEGER, prefix TEXT)'] as $table) {
    $capsule->getConnection()->statement('CREATE TABLE ' . $table);
}

// Mirrors the slug package: a morphOne slug relation and a URL macro built from prefix/key.
$macros = new MacroableModels();
$app->instance(MacroableModels::class, $macros);
Botble\Base\Facades\MacroableModels::swap($macros);
foreach ([Room::class, RoomCategory::class, Service::class, Place::class, Food::class] as $model) {
    $model::resolveRelationUsing('slugable', fn ($item) => $item->morphOne(Slug::class, 'reference'));
    $macros->addMacro($model, 'getUrlAttribute', function () {
        return 'https://hotel.example/' . $this->slugable->prefix . '/' . $this->slugable->key;
    });
}

$seed = function () use ($capsule): void {
    $db = $capsule->getConnection();
    $at = '2026-10-01 10:00:00';
    $db->table('ht_room_categories')->insert([
        ['id' => 1, 'name' => 'Suites', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at],
        ['id' => 2, 'name' => 'Empty category', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at],
        ['id' => 3, 'name' => 'Draft category', 'status' => 'draft', 'created_at' => $at, 'updated_at' => $at],
    ]);
    $db->table('ht_rooms')->insert([
        ['id' => 1, 'name' => 'Sky Suite', 'status' => 'published', 'room_category_id' => 1, 'created_at' => $at, 'updated_at' => $at],
        ['id' => 2, 'name' => 'Draft room', 'status' => 'draft', 'room_category_id' => 2, 'created_at' => $at, 'updated_at' => $at],
        ['id' => 3, 'name' => 'Room without slug', 'status' => 'published', 'room_category_id' => 3, 'created_at' => $at, 'updated_at' => $at],
    ]);
    $db->table('ht_services')->insert([
        ['id' => 1, 'name' => 'Airport transfer', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at],
        ['id' => 2, 'name' => 'Pending service', 'status' => 'pending', 'created_at' => $at, 'updated_at' => $at],
    ]);
    $db->table('ht_places')->insert(['id' => 1, 'name' => 'Pyramids', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at]);
    $db->table('ht_foods')->insert(['id' => 1, 'name' => 'Breakfast', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at]);
    $db->table('slugs')->insert([
        ['key' => 'sky-suite', 'reference_type' => Room::class, 'reference_id' => 1, 'prefix' => 'rooms'],
        ['key' => 'draft-room', 'reference_type' => Room::class, 'reference_id' => 2, 'prefix' => 'rooms'],
        ['key' => 'suites', 'reference_type' => RoomCategory::class, 'reference_id' => 1, 'prefix' => 'room-categories'],
        ['key' => 'empty-category', 'reference_type' => RoomCategory::class, 'reference_id' => 2, 'prefix' => 'room-categories'],
        ['key' => 'draft-category', 'reference_type' => RoomCategory::class, 'reference_id' => 3, 'prefix' => 'room-categories'],
        ['key' => 'airport-transfer', 'reference_type' => Service::class, 'reference_id' => 1, 'prefix' => 'services'],
        ['key' => 'pending-service', 'reference_type' => Service::class, 'reference_id' => 2, 'prefix' => 'services'],
        ['key' => 'pyramids', 'reference_type' => Place::class, 'reference_id' => 1, 'prefix' => 'places'],
        ['key' => 'breakfast', 'reference_type' => Food::class, 'reference_id' => 1, 'prefix' => 'foods'],
    ]);
};
$seed();

$sitemapFor = function (?string $key) use ($app): object {
    $recorder = new class {
        public array $urls = [];
        public array $sitemaps = [];
        public function add(string $url, $date = null, string $priority = '1.0', string $freq = 'daily'): self { $this->urls[$url] = $priority; return $this; }
        public function addSitemap(string $loc, $date = null): self { $this->sitemaps[] = $loc; return $this; }
        public function route(?string $key = null): string { return "https://hotel.example/sitemap/$key.xml"; }
    };
    SiteMapManager::swap($recorder);
    $app['router']->get('rooms', fn () => null)->name('public.rooms');
    $app['router']->getRoutes()->refreshNameLookups();
    (new AddSitemapListener())->handle(new RenderingSiteMapEvent($key));
    return $recorder;
};
$app->instance('router', new Illuminate\Routing\Router($app['events'], $app));
$app->instance('url', new Illuminate\Routing\UrlGenerator($app['router']->getRoutes(), Illuminate\Http\Request::create('https://hotel.example')));

test('Sitemap index lists rooms, room categories, services and places sitemaps', function () use ($sitemapFor): void {
    $sitemaps = $sitemapFor(null)->sitemaps;
    foreach (['rooms', 'room-categories', 'services', 'places'] as $key) {
        check(in_array("https://hotel.example/sitemap/$key.xml", $sitemaps, true), "$key sitemap missing.");
    }
    check(! in_array('https://hotel.example/sitemap/foods.xml', $sitemaps, true), 'Foods sitemap should stay excluded.');
});

test('Rooms sitemap lists the listing and only published, slugged rooms', function () use ($sitemapFor): void {
    $urls = array_keys($sitemapFor('rooms')->urls);
    check($urls === ['https://hotel.example/rooms', 'https://hotel.example/rooms/sky-suite'], 'Rooms sitemap: ' . implode(', ', $urls));
});

test('Content sitemaps list only published pages with content', function () use ($sitemapFor): void {
    $expected = [
        'room-categories' => ['https://hotel.example/room-categories/suites'],
        'services' => ['https://hotel.example/services/airport-transfer'],
        'places' => ['https://hotel.example/places/pyramids'],
    ];
    foreach ($expected as $key => $urls) {
        $actual = array_keys($sitemapFor($key)->urls);
        check($actual === $urls, "$key sitemap: " . implode(', ', $actual));
    }
});

test('Sitemap keys are registered for every hotel content sitemap', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/hotel/src/Providers/HotelServiceProvider.php');
    check(str_contains($provider, "SiteMapManager::registerKey(['rooms', ...array_keys(AddSitemapListener::CONTENT)])"), 'Sitemap keys not registered.');
    check(array_keys(AddSitemapListener::CONTENT) === ['room-categories', 'services', 'places'], 'Unexpected sitemap keys.');
});
