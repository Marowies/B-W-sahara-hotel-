<?php

// Internal linking batch: language switcher final URLs (L1), room card search links (L2), named social links (L4).
// Real classes and templates with doubles; no languages, rooms or CMS records are created.

use Botble\Hotel\Supports\RoomSearchLink;
use Botble\Language\LanguageManager;
use Botble\Language\Listeners\AddHrefLangListener;
use Carbon\Carbon;

$root = dirname(__DIR__, 2);
$themePath = "$root/platform/themes/riorelax";

$linkFacades = [Botble\Language\Facades\Language::class, Botble\Hotel\Facades\HotelHelper::class, Botble\Theme\Facades\Theme::class];
$linkState = ['url' => $app['url'], 'request' => $app->bound('request') ? $app['request'] : null, 'facades' => []];
foreach ($linkFacades as $facade) {
    try { $linkState['facades'][$facade] = $facade::getFacadeRoot(); } catch (Throwable) { $linkState['facades'][$facade] = null; }
}
function_exists('setting') || eval('function setting($key = null, $default = null) { return $GLOBALS["seoSettings"][$key] ?? $default; }');
$GLOBALS['seoSettings'] = [];

$app->instance('url', new Illuminate\Routing\UrlGenerator(new Illuminate\Routing\RouteCollection(), Illuminate\Http\Request::create('https://hotel.example')));

// Real getSwitcherUrl(); only the URL builder and locale settings are doubled.
$switcher = function (string $requestUrl, array $rows = []): LanguageManager {
    $manager = (new ReflectionClass(new class extends LanguageManager {
        public array $localizedCalls = [];
        public function __construct() {}
        public function getLocalizedURL($locale = null, $url = null, array $attributes = [], $forceDefaultLocation = true): string {
            $this->localizedCalls[] = [$locale, $forceDefaultLocation];
            return "localized:$locale:" . ($forceDefaultLocation ? 'forced' : 'final');
        }
        public function getDefaultLocale(): ?string { return 'en'; }
        public function hideDefaultLocaleInURL(): bool { return true; }
    }))->newInstanceWithoutConstructor();
    (new ReflectionProperty(LanguageManager::class, 'request'))->setValue($manager, Illuminate\Http\Request::create($requestUrl));
    $manager->setSwitcherURLs($rows);
    return $manager;
};
$cluster = fn (array $urls) => [
    ['lang_code' => 'en_US', 'locale' => 'en', 'url' => $urls['en']],
    ['lang_code' => 'ar', 'locale' => 'ar', 'url' => $urls['ar']],
    ['lang_code' => 'zh_CN', 'locale' => 'zh', 'url' => $urls['zh']],
];

// L1: language switcher

test('Switcher links on the homepage go straight to each language homepage, never /en', function () use ($switcher, $cluster): void {
    $manager = $switcher('https://hotel.example/ar', $cluster(['en' => 'https://hotel.example', 'ar' => 'https://hotel.example/ar', 'zh' => 'https://hotel.example/zh']));
    check($manager->getSwitcherUrl('en', 'en_US') === 'https://hotel.example', 'English homepage link: ' . $manager->getSwitcherUrl('en', 'en_US'));
    check($manager->getSwitcherUrl('ar', 'ar') === 'https://hotel.example/ar' && $manager->getSwitcherUrl('zh', 'zh_CN') === 'https://hotel.example/zh', 'AR/ZH homepage links changed.');
    check($manager->localizedCalls === [], 'Cluster URLs were rebuilt instead of reused.');
});

test('Switcher links on inner pages reuse the hreflang targets, including translated slugs and the search query', function () use ($switcher, $cluster): void {
    $urls = ['en' => 'https://hotel.example/rooms/deluxe-room', 'ar' => 'https://hotel.example/ar/rooms/ghurfa-deluxe', 'zh' => 'https://hotel.example/zh/rooms/deluxe-room'];
    $manager = $switcher('https://hotel.example/zh/rooms/deluxe-room', $cluster($urls));
    foreach (['en' => 'en_US', 'ar' => 'ar', 'zh' => 'zh_CN'] as $locale => $code) {
        check($manager->getSwitcherUrl($locale, $code) === $urls[$locale], "$locale link differs from its hreflang target.");
        check(! str_contains($manager->getSwitcherUrl($locale, $code), '/en/'), "$locale link goes through /en.");
    }
    $search = $switcher('https://hotel.example/ar/rooms?adults=2&start_date=20-10-2026', $cluster(['en' => 'https://hotel.example/rooms', 'ar' => 'https://hotel.example/ar/rooms', 'zh' => 'https://hotel.example/zh/rooms']));
    check($search->getSwitcherUrl('en', 'en_US') === 'https://hotel.example/rooms?adults=2&start_date=20-10-2026', 'Search query lost when switching language.');
});

test('Pages without an hreflang cluster (search, 404) build the final localized URL', function () use ($switcher): void {
    $manager = $switcher('https://hotel.example/zh/search?q=desert');
    check($manager->getSwitcherUrl('en', 'en_US') === 'localized:en:final' && $manager->getSwitcherUrl('ar', 'ar') === 'localized:ar:final', 'Fallback still forces the default-language prefix.');
    check($manager->localizedCalls === [['en', false], ['ar', false]], 'Fallback arguments: ' . json_encode($manager->localizedCalls));
    // With "show related page" off, each language links to its own homepage; the hidden default has no prefix.
    $GLOBALS['seoSettings']['language_show_default_item_if_current_version_not_existed'] = false;
    try {
        check($manager->getSwitcherUrl('en', 'en_US') === 'https://hotel.example', 'Default homepage fallback is prefixed: ' . $manager->getSwitcherUrl('en', 'en_US'));
        check($manager->getSwitcherUrl('zh', 'zh_CN') === 'https://hotel.example/zh', 'Chinese homepage fallback changed.');
    } finally {
        $GLOBALS['seoSettings'] = [];
    }
});

test('The hreflang listener hands the switcher one row per language with its hreflang URL', function () use ($app, $languageStub, $hreflangUrls, $switcher): void {
    Botble\Language\Facades\Language::swap($languageStub);
    $app->instance('url', new Illuminate\Routing\UrlGenerator(new Illuminate\Routing\RouteCollection(), Illuminate\Http\Request::create($hreflangUrls['zh'])));
    $listener = new class extends AddHrefLangListener {
        public function cluster(): array { return $this->generateHreflangUrls(null, null); }
        public function rows(array $urls): array { return $this->switcherUrls($urls); }
    };
    $app->setLocale('zh');
    $hreflang = $listener->cluster();
    $rows = $listener->rows($hreflang);
    check(array_column($rows, 'lang_code') === ['en_US', 'ar', 'zh_CN'], 'Rows: ' . json_encode($rows));
    $manager = $switcher($hreflangUrls['zh'], $rows);
    foreach (['en' => ['en_US', 'en-us'], 'ar' => ['ar', 'ar'], 'zh' => ['zh_CN', 'zh-cn']] as $locale => [$code, $hreflangCode]) {
        check($manager->getSwitcherUrl($locale, $code) === $hreflang[$hreflangCode], "$locale switcher link differs from hreflang.");
    }
    $source = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/language/src/Listeners/AddHrefLangListener.php');
    check(str_contains($source, 'Language::setSwitcherURLs($this->switcherUrls($hreflangUrls));'), 'Listener does not pass switcher rows.');
});

test('Theme switchers use the final switcher URL in every display mode', function () use ($themePath): void {
    foreach (['partials/language-switcher.blade.php' => 2, 'partials/language-switcher-mobile.blade.php' => 1] as $file => $links) {
        $source = file_get_contents("$themePath/$file");
        check(substr_count($source, "Language::getSwitcherUrl(\$localeCode, \$properties['lang_code'])") === $links, "$file does not use getSwitcherUrl for every link.");
        check(! str_contains($source, 'Language::getLocalizedURL(') && ! str_contains($source, 'url($localeCode)'), "$file still builds a forced /en link.");
    }
    $manager = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/language/src/LanguageManager.php');
    check(str_contains($manager, "if (\$forceDefaultLocation || \$locale != \$this->getDefaultLocale() || ! \$this->hideDefaultLocaleInURL()) {"), 'Unprefixed default-language rule changed.');
});

// L2: room card links

test('Room links stay clean without a search and keep the visitor\'s validated search parameters', function (): void {
    $values = ['start_date' => '20-10-2026', 'end_date' => '22-10-2026', 'adults' => 2, 'children' => 1, 'rooms' => 2];
    $url = 'https://hotel.example/ar/rooms/deluxe-room';
    check(RoomSearchLink::url($url, [], $values) === $url, 'Default values written into a clean link.');
    check(RoomSearchLink::url($url, ['page' => 2, 'q' => 'x', 'start_date' => '', 'adults' => ['2']], $values) === $url, 'Non-booking, empty or array inputs added parameters.');
    check(RoomSearchLink::url($url, ['adults' => '2'], $values) === "$url?adults=2", 'Guests-only search: ' . RoomSearchLink::url($url, ['adults' => '2'], $values));
    check(RoomSearchLink::url($url, ['start_date' => '20-10-2026'], $values) === "$url?start_date=20-10-2026&end_date=22-10-2026", 'Dates not kept as the validated pair.');
    check(RoomSearchLink::url($url, ['start_date' => '20-10-2026', 'end_date' => '22-10-2026', 'adults' => '2', 'children' => '1', 'rooms' => '2'], $values)
        === "$url?start_date=20-10-2026&end_date=22-10-2026&adults=2&children=1&rooms=2", 'Full search context lost.');
    check(RoomSearchLink::url("$url?ref=x", ['adults' => '2'], $values) === "$url?ref=x&adults=2", 'Existing query not respected.');
});

$hotelHelper = fn (Carbon $start, Carbon $end, int $adults = 1, int $children = 0, int $rooms = 1) => Botble\Hotel\Facades\HotelHelper::swap(new class($start, $end, $adults, $children, $rooms) {
    public function __construct(public Carbon $start, public Carbon $end, public int $adults, public int $children, public int $rooms) {}
    public function getDateFormat(): string { return 'd-m-Y'; }
    public function getRoomBookingParams(): array { return [$this->start, $this->end, $this->adults, (int) $this->start->diffInDays($this->end), $this->children, $this->rooms]; }
});
$renderCardLink = function (string $requestUrl, string $file) use ($app, $compileBlade, $themePath, $hotelHelper): string {
    class_exists('HotelHelper') || class_alias(Botble\Hotel\Facades\HotelHelper::class, 'HotelHelper');
    class_exists('Theme') || class_alias(Botble\Theme\Facades\Theme::class, 'Theme');
    Botble\Theme\Facades\Theme::swap(new class { public array $regions = []; public function set(string $key, $value): self { $this->regions[$key] = $value; return $this; } });
    $app->instance('request', Illuminate\Http\Request::create($requestUrl));
    $search = Illuminate\Http\Request::create($requestUrl)->query();
    $start = Carbon::createFromFormat('d-m-Y', $search['start_date'] ?? Carbon::today()->format('d-m-Y'))->startOfDay();
    $end = Carbon::createFromFormat('d-m-Y', $search['end_date'] ?? Carbon::tomorrow()->format('d-m-Y'))->startOfDay();
    $hotelHelper($start, $end, (int) ($search['adults'] ?? 1), (int) ($search['children'] ?? 0), (int) ($search['rooms'] ?? 1));
    $room = new class { public string $url = 'https://hotel.example/zh/rooms/deluxe-room'; public array $images = ['a.jpg']; public string $name = 'Deluxe'; };
    $source = file_get_contents("$themePath/$file");
    if ($file === 'partials/rooms/item.blade.php') {
        preg_match('/^@php\b.*?@endphp/s', $source, $block);
        return $compileBlade($block[0] . "\n{{ \$roomUrl }}", ['room' => $room, 'startDate' => $start, 'endDate' => $end, 'adults' => (int) ($search['adults'] ?? 1)]);
    }
    preg_match('/^@php\b.*?@endphp/s', $source, $block);
    preg_match_all('/<a href="\{\{ Botble\\\\Hotel\\\\Supports\\\\RoomSearchLink::url[^\n]*/', $source, $link);
    return $compileBlade($block[0] . "\n" . implode("\n", $link[0]), ['room' => $room, 'category' => (object) ['name' => 'Suites']]);
};

test('Room and room-category cards link to the clean room URL when nobody searched', function () use ($renderCardLink, $themePath): void {
    $item = $renderCardLink('https://hotel.example/zh', 'partials/rooms/item.blade.php');
    check(trim($item) === 'https://hotel.example/zh/rooms/deluxe-room', 'Room card link: ' . $item);
    $category = $renderCardLink('https://hotel.example/zh/room-categories/suites', 'views/hotel/room-category.blade.php');
    check(str_contains($category, '<a href="https://hotel.example/zh/rooms/deluxe-room">'), 'Category card link: ' . $category);
    check(! str_contains($item . $category, '00:00:00') && ! str_contains($item . $category, 'start_date'), 'Default dates leaked into a link.');
    // The internal booking form keeps its own hidden search fields.
    $form = file_get_contents("$themePath/partials/rooms/item.blade.php");
    foreach (['name="start_date" value="{{ $startDate = $startDate->format(HotelHelper::getDateFormat()) }}"', 'name="end_date" value="{{ $endDate = $endDate->format(HotelHelper::getDateFormat()) }}"', 'name="adults" value="{{ $adults }}"', 'name="rooms" type="hidden" value="{{ $roomsOfNumber = BaseHelper::stringify(request()->integer(\'rooms\', 1)) }}"'] as $field) {
        check(str_contains($form, $field), "Booking form field changed: $field");
    }
});


test('Room card booking inputs render the original main search values', function () use ($compileBlade, $themePath, $app): void {
    $source = file_get_contents("$themePath/partials/rooms/item.blade.php");
    check(str_contains($source, '<form action="{{ route(\'public.booking\') }}" method="POST">') && str_contains($source, '@csrf'), 'Booking action, method or CSRF changed.');
    $start = strpos($source, '<input type="hidden" name="room_id"');
    $end = strpos($source, '<button', $start);
    check($start !== false && $end !== false, 'Booking inputs missing.');
    $inputs = substr($source, $start, $end - $start);
    $helper = Botble\Base\Facades\BaseHelper::getFacadeRoot();
    $hotel = Botble\Hotel\Facades\HotelHelper::getFacadeRoot();
    $oldRequest = $app->bound('request') ? $app['request'] : null;
    Botble\Base\Facades\BaseHelper::swap(new class { public function stringify($value): string { return (string) $value; } });
    Botble\Hotel\Facades\HotelHelper::swap(new class { public function getDateFormat(): string { return 'd-m-Y'; } });
    try {
        foreach ([['children' => 0, 'rooms' => 1], ['children' => 1, 'rooms' => 2]] as $query) {
            $app->instance('request', Illuminate\Http\Request::create('https://hotel.example/rooms', 'GET', $query));
            $html = $compileBlade($inputs, [
                'room' => (object) ['id' => 17], 'adults' => 3,
                'startDate' => Carbon::createFromFormat('d-m-Y', '20-10-2026'),
                'endDate' => Carbon::createFromFormat('d-m-Y', '22-10-2026'),
            ]);
            $document = new DOMDocument();
            @$document->loadHTML($html);
            $values = [];
            foreach ($document->getElementsByTagName('input') as $input) {
                $name = $input->getAttribute('name');
                check(! array_key_exists($name, $values), "Duplicate booking input: $name");
                check($input->getAttribute('type') === 'hidden', "Non-hidden booking input: $name");
                $values[$name] = $input->getAttribute('value');
            }
            check($values === ['room_id' => '17', 'start_date' => '20-10-2026', 'end_date' => '22-10-2026', 'adults' => '3', 'children' => (string) $query['children'], 'rooms' => (string) $query['rooms']], 'Booking values changed: ' . json_encode($values));
        }
    } finally {
        Botble\Base\Facades\BaseHelper::swap($helper);
        Botble\Hotel\Facades\HotelHelper::swap($hotel);
        if ($oldRequest) { $app->instance('request', $oldRequest); } else { $app->forgetInstance('request'); }
    }
});
test('Room and room-category cards carry a real search to the room page', function () use ($renderCardLink): void {
    $query = 'start_date=20-10-2026&end_date=22-10-2026&adults=2&children=1&rooms=2';
    $item = $renderCardLink("https://hotel.example/ar/rooms?$query", 'partials/rooms/item.blade.php');
    check(html_entity_decode(trim($item)) === "https://hotel.example/zh/rooms/deluxe-room?$query", 'Room card search link: ' . $item);
    $category = html_entity_decode($renderCardLink("https://hotel.example/ar/room-categories/suites?$query", 'views/hotel/room-category.blade.php'));
    check(str_contains($category, "href=\"https://hotel.example/zh/rooms/deluxe-room?$query\""), 'Category search link: ' . $category);
    $guests = html_entity_decode($renderCardLink('https://hotel.example/rooms?adults=3', 'partials/rooms/item.blade.php'));
    check(trim($guests) === 'https://hotel.example/zh/rooms/deluxe-room?adults=3', 'Guest-only search invented dates: ' . $guests);
});

// L4: named social links

test('Author social links have accessible names and unchanged destinations', function () use ($compileBlade, $themePath): void {
    preg_match('/@foreach\(\[\'facebook\'.*?@endforeach/s', file_get_contents("$themePath/views/post.blade.php"), $block);
    check(isset($block[0]), 'Author social block not found.');
    $author = new class { public function getMetaData(string $key, bool $single = false) {
        return ['facebook' => 'https://facebook.example/author', 'twitter' => 'https://x.example/author', 'linkedin' => 'https://linkedin.example/in/author'][$key] ?? null;
    } };
    $html = $compileBlade($block[0], ['author' => $author]);
    foreach (['https://facebook.example/author' => 'Facebook', 'https://x.example/author' => 'X (Twitter)', 'https://linkedin.example/in/author' => 'LinkedIn'] as $href => $label) {
        check(str_contains($html, "<a href=\"$href\" aria-label=\"$label\">"), "$label link unnamed or moved: $html");
    }
    check(substr_count($html, '<a ') === 3 && substr_count($html, 'aria-hidden="true"') === 3, 'Unset profiles rendered or icons announced.');
});

$app->instance('url', $linkState['url']);
$linkState['request'] ? $app->instance('request', $linkState['request']) : $app->forgetInstance('request');
foreach ($linkState['facades'] as $facade => $previous) {
    if ($previous !== null) {
        $facade::swap($previous);
    } else {
        $facade::clearResolvedInstance((new ReflectionMethod($facade, 'getFacadeAccessor'))->invoke(null));
    }
}

test('Category image and title both retain the full room search', function () use ($renderCardLink): void {
    $html = $renderCardLink('https://hotel.example/zh/room-categories/suites?start_date=20-10-2026&end_date=22-10-2026&adults=2&children=1&rooms=2', 'views/hotel/room-category.blade.php');
    preg_match_all('/href="([^"]+)"/', $html, $links);
    $expected = 'https://hotel.example/zh/rooms/deluxe-room?start_date=20-10-2026&end_date=22-10-2026&adults=2&children=1&rooms=2';
    check(count($links[1]) === 2, 'Both image and title anchors must be rendered.');
    foreach ($links[1] as $url) check(html_entity_decode($url) === $expected, 'Category room link lost search: ' . $url);
});