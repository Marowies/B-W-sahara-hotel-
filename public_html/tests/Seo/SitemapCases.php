<?php

use Botble\Base\Supports\MacroableModels;
use Botble\Hotel\Listeners\AddSitemapListener;
use Botble\Hotel\Models\Food;
use Botble\Hotel\Models\Place;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCategory;
use Botble\Hotel\Models\Service;
use Botble\Slug\Models\Slug;
use Botble\Page\Models\Page;
use Botble\Page\Listeners\RenderingSiteMapListener;
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
    'meta_boxes (id INTEGER PRIMARY KEY, meta_key TEXT, meta_value TEXT, reference_type TEXT, reference_id INTEGER)',
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
Botble\Base\Facades\MetaBox::swap(new Botble\Base\Supports\MetaBox());

$sitemapFor = function (?string $key) use ($app): object {
    $recorder = new class {
        public array $urls = [];
        public array $sitemaps = [];
        public function add(string $url, $date = null, string $priority = '1.0', string $freq = 'daily'): self { $this->urls[$url] = $priority; return $this; }
        public function addSitemap(string $loc, $date = null): self { $this->sitemaps[] = $loc; return $this; }
        public function route(?string $key = null): string { return "https://hotel.example/$key.xml"; }
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
        check(in_array("https://hotel.example/$key.xml", $sitemaps, true), "$key sitemap missing.");
    }
    check(! in_array('https://hotel.example/foods.xml', $sitemaps, true), 'Foods sitemap should stay excluded.');
});

test('Page sitemap retains index/default pages and excludes noindex, draft and pending pages', function () use ($capsule, $macros): void {
    $db = $capsule->getConnection();
    $db->statement('CREATE TABLE pages (id INTEGER PRIMARY KEY, name TEXT, status TEXT, created_at TEXT, updated_at TEXT)');
    Page::resolveRelationUsing('slugable', fn ($item) => $item->morphOne(Slug::class, 'reference'));
    $macros->addMacro(Page::class, 'getUrlAttribute', function () {
        return 'https://hotel.example/' . $this->slugable->key;
    });
    foreach (['published', 'published', 'published', 'draft', 'pending'] as $i => $status) {
        $id = $i + 1;
        $db->table('pages')->insert(['id' => $id, 'name' => "Page $id", 'status' => $status, 'created_at' => '2026-10-01', 'updated_at' => '2026-10-01']);
        $db->table('slugs')->insert(['key' => "page-$id", 'reference_type' => Page::class, 'reference_id' => $id, 'prefix' => '']);
    }
    foreach ([1 => 'index', 2 => 'noindex', 4 => 'index', 5 => 'index'] as $id => $index) {
        $db->table('meta_boxes')->insert(['meta_key' => 'seo_meta', 'meta_value' => json_encode([['index' => $index]]), 'reference_type' => Page::class, 'reference_id' => $id]);
    }
    $recorder = new class {
        public array $urls = [];
        public function add(string $url, $date, string $priority): void { $this->urls[] = $url; }
    };
    SiteMapManager::swap($recorder);
    try {
        Botble\Base\Supports\MetadataCache::flush();
        (new RenderingSiteMapListener())->handle(new RenderingSiteMapEvent('pages'));
        sort($recorder->urls);
        check($recorder->urls === ['https://hotel.example/page-1', 'https://hotel.example/page-3'], 'Wrong Page sitemap: ' . implode(', ', $recorder->urls));
        $recorder->urls = [];
        (new RenderingSiteMapListener())->handle(new RenderingSiteMapEvent('rooms'));
        check($recorder->urls === [], 'Page listener changed a different sitemap key.');
    } finally {
        $db->table('meta_boxes')->where('reference_type', Page::class)->delete();
        $db->table('slugs')->where('reference_type', Page::class)->delete();
        $db->statement('DROP TABLE pages');
        Botble\Base\Supports\MetadataCache::flush();
    }
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

test('Published hotel content marked noindex is excluded from discovery sitemaps', function () use ($capsule, $sitemapFor): void {
    $db = $capsule->getConnection();
    try {
        foreach ([Room::class, RoomCategory::class, Service::class, Place::class] as $model) {
            $db->table('meta_boxes')->insert(['meta_key' => 'seo_meta', 'meta_value' => json_encode([['index' => 'noindex']]), 'reference_type' => $model, 'reference_id' => 1]);
        }
        Botble\Base\Supports\MetadataCache::flush();
        check(array_keys($sitemapFor('rooms')->urls) === ['https://hotel.example/rooms'], 'Noindex room leaked into sitemap.');
        foreach (['room-categories', 'services', 'places'] as $key) {
            check($sitemapFor($key)->urls === [], "Noindex content leaked into $key sitemap.");
        }
    } finally {
        $db->table('meta_boxes')->delete();
        Botble\Base\Supports\MetadataCache::flush();
    }
    check(count($sitemapFor('rooms')->urls) === 2, 'Missing SEO metadata incorrectly excluded published content.');
});
