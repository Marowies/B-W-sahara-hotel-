<?php

// Batch 1 template gaps: Rooms listing copy, Galleries listing H1, album filters, gallery photo markup.
// Rendered with the real templates and in-memory doubles; no CMS records, hotel facts or translations are invented.

use Botble\Hotel\Http\Controllers\PublicController;
use Botble\Hotel\Supports\RoomsListingSeo;

$root = dirname(__DIR__, 2);
$themePath = "$root/platform/themes/riorelax";

$listingFacades = [Botble\Theme\Facades\Theme::class, Botble\Media\Facades\RvMedia::class];
$listingState = ['locale' => $app->getLocale(), 'facades' => []];
foreach ($listingFacades as $facade) {
    try { $listingState['facades'][$facade] = $facade::getFacadeRoot(); } catch (Throwable) { $listingState['facades'][$facade] = null; }
}

// Theme double: region storage plus the asset/layout calls the gallery views make.
$listingTheme = function (array $regions = []): object {
    $theme = new class($regions) {
        public ?string $layout = null;
        public function __construct(public array $regions) {}
        public function get(string $key, $default = null) { return $this->regions[$key] ?? $default; }
        public function set(string $key, $value): self { $this->regions[$key] = $value; return $this; }
        public function layout(string $layout): self { $this->layout = $layout; return $this; }
        public function asset(): object {
            return new class { public function __call($method, $arguments) { return $this; } };
        }
    };
    Botble\Theme\Facades\Theme::swap($theme);
    class_exists('Theme') || class_alias(Botble\Theme\Facades\Theme::class, 'Theme');
    class_exists('BaseHelper') || class_alias(Botble\Base\Facades\BaseHelper::class, 'BaseHelper');
    class_exists('RvMedia') || class_alias(Botble\Media\Facades\RvMedia::class, 'RvMedia');
    class_exists('Arr') || class_alias(Illuminate\Support\Arr::class, 'Arr');
    // Stored paths become storage URLs; values that are already URLs (or unsafe schemes) pass through, as in RvMedia.
    Botble\Media\Facades\RvMedia::swap(new class {
        public function getImageUrl($url, $size = null, $relativePath = false, $default = null) {
            if (! $url) { return $default; }
            if (preg_match('#^[a-z]+:#i', $url)) { return $url; }
            return 'https://hotel.example/storage/' . ($size ? "$size/" : '') . $url;
        }
    });
    return $theme;
};
$phpBlock = function (string $file) use ($themePath): string {
    preg_match('/^@php\b.*?@endphp/s', file_get_contents("$themePath/$file"), $match);
    check(isset($match[0]), "No leading @php block in $file.");
    return $match[0];
};

// 1. Rooms listing

test('Rooms listing copy reads only the current language, never the default-language value', function (): void {
    $store = [
        null => ['rooms_page_seo_title' => 'Rooms at B&W Sahara Sky Hotel', 'rooms_page_heading' => '  Rooms at <b>B&W</b>   Sahara Sky  '],
        '-ar' => ['rooms_page_seo_title' => 'غرف فندق B&W Sahara Sky', 'rooms_page_heading' => ''],
        '-zh' => [],
    ];
    $reads = [];
    $read = function (string $key, ?string $suffix) use ($store, &$reads) { $reads[] = $suffix; return $store[$suffix][$key] ?? null; };
    check(RoomsListingSeo::resolve(RoomsListingSeo::TITLE, 'en', 'en', $read) === 'Rooms at B&W Sahara Sky Hotel', 'Default language title not read.');
    check(RoomsListingSeo::resolve(RoomsListingSeo::HEADING, 'en', 'en', $read) === 'Rooms at B&W Sahara Sky', 'Heading not reduced to plain text.');
    check(RoomsListingSeo::resolve(RoomsListingSeo::TITLE, 'ar', 'en', $read) === 'غرف فندق B&W Sahara Sky', 'Arabic title not read from its own key.');
    check(RoomsListingSeo::resolve(RoomsListingSeo::HEADING, 'ar', 'en', $read) === null, 'Empty Arabic heading fell back to English.');
    check(RoomsListingSeo::resolve(RoomsListingSeo::TITLE, 'zh', 'en', $read) === null, 'Unset Chinese title fell back to English.');
    check(RoomsListingSeo::resolve(RoomsListingSeo::DESCRIPTION, 'en', 'en', $read) === null, 'Unset description invented a value.');
    check(RoomsListingSeo::resolve(RoomsListingSeo::TITLE, null, null, $read) === 'Rooms at B&W Sahara Sky Hotel', 'Single-language site does not use the base key.');
    check(! in_array(null, array_slice($reads, 2, 3), true), 'A non-default language read the default-language key.');
});

test('Rooms listing applies configured copy and keeps the existing output as fallback', function () use ($root): void {
    $source = method_source(PublicController::class, 'getRooms');
    check(str_contains($source, "SeoHelper::setTitle(RoomsListingSeo::value(RoomsListingSeo::TITLE) ?: trans('plugins/hotel::hotel.rooms'))"), 'Title fallback missing.');
    check(str_contains($source, "if (\$description = RoomsListingSeo::value(RoomsListingSeo::DESCRIPTION)) {\n            SeoHelper::setDescription(\$description);"), 'Description is not optional.');
    check(str_contains($source, "Theme::breadcrumb()->add(trans('plugins/hotel::hotel.rooms'), route('public.rooms'))"), 'Breadcrumb label changed with the listing copy.');
    check(str_contains($source, "'numberOfRooms', 'pageHeading'))->render()"), 'Heading not passed to the listing view.');
    $options = file_get_contents("$root/platform/themes/riorelax/functions/theme-options.php");
    foreach ([RoomsListingSeo::TITLE, RoomsListingSeo::DESCRIPTION, RoomsListingSeo::HEADING] as $key) {
        check(preg_match("/'id' => '$key',\s*'section_id' => 'opt-text-subsection-hotel',/", $options) === 1, "Theme option $key not registered in the Hotel section.");
    }
    check(! str_contains(file_get_contents("$root/platform/plugins/hotel/src/Supports/RoomsListingSeo.php"), 'theme_option('), 'Listing copy uses the falling-back theme_option() helper.');
});

test('Rooms listing H1 uses the configured heading, else the translated Rooms label', function () use ($compileBlade, $listingTheme, $themePath, $app): void {
    preg_match("/@php\(Theme::set\('pageTitle'.*\)\)/", file_get_contents("$themePath/views/hotel/rooms.blade.php"), $line);
    check(isset($line[0]), 'Rooms heading line not found.');
    $theme = $listingTheme();
    $compileBlade($line[0], ['pageHeading' => 'غرف فندق B&W Sahara Sky']);
    check($theme->regions['pageTitle'] === 'غرف فندق B&W Sahara Sky', 'Configured heading not used.');
    foreach ([[], ['pageHeading' => null], ['pageHeading' => '']] as $data) {
        $theme = $listingTheme();
        $compileBlade($line[0], $data);
        check($theme->regions['pageTitle'] === 'Rooms', 'Default Rooms heading not kept: ' . json_encode($theme->regions));
    }
});

// 2. Galleries listing H1

test('Galleries listing H1 is the Galleries page name, else the translated plugin label', function () use ($compileBlade, $listingTheme, $phpBlock, $app, $root): void {
    $block = $phpBlock('views/galleries.blade.php');
    check(! str_contains($block, "Theme::set('pageTitle', 'Galleries')"), 'Hard-coded Galleries H1 remains.');
    $theme = $listingTheme(['pageTitle' => 'معارض صور فندق B&W Sahara Sky']);
    $compileBlade($block, []);
    check($theme->regions['pageTitle'] === 'معارض صور فندق B&W Sahara Sky', 'Configured Galleries page name replaced.');
    check($theme->layout === 'full-width' && $theme->regions['breadcrumb'] === true, 'Galleries layout/banner changed.');
    // Fallback uses the plugin's existing translation files, nothing invented here.
    foreach (['en', 'ar', 'zh'] as $locale) {
        $file = $locale === 'en' ? "$root/platform/plugins/gallery/resources/lang/en/gallery.php" : "$root/lang/vendor/plugins/gallery/$locale/gallery.php";
        $app['translator']->getLoader()->addMessages($locale, 'gallery', require $file, 'plugins/gallery');
        $app['translator']->setLoaded([]);
        $app->setLocale($locale);
        $theme = $listingTheme();
        $compileBlade($block, []);
        $expected = (require $file)['galleries'];
        check($theme->regions['pageTitle'] === $expected, "$locale fallback heading: " . json_encode($theme->regions['pageTitle']));
    }
});

// 3. Album filters

$album = fn (int $id, string $name) => new class($id, $name) {
    public string $url;
    public string $image = 'cover.jpg';
    public function __construct(public int $id, public string $name) { $this->url = "https://hotel.example/galleries/album-$id"; }
    public function getKey(): int { return $this->id; }
};

test('Album filters use stable ID selectors that match exactly one grid item each', function () use ($compileBlade, $listingTheme, $themePath, $album): void {
    $listingTheme();
    $albums = collect([$album(3, 'B&W Sahara Sky Hotel Overview Photos'), $album(7, 'صور غرف ديلوكس'), $album(9, 'Deluxe Room (5)'), $album(12, 'Deluxe  Room')]);
    $html = $compileBlade(file_get_contents("$themePath/partials/gallery/galleries.blade.php"), ['galleries' => $albums]);
    preg_match_all('/<button(?: class="active")? data-filter="([^"]*)">([^<]*)<\/button>/', $html, $buttons, PREG_SET_ORDER);
    check(count($buttons) === 5 && $buttons[0][1] === '*', 'Expected All plus one button per album.');
    foreach (array_slice($buttons, 1) as [$whole, $filter]) {
        check((bool) preg_match('/^\.gallery-filter-\d+$/', $filter), "Unsafe filter selector: $filter");
        check(preg_match_all('/class="grid-item ' . preg_quote(substr($filter, 1), '/') . '"/', $html) === 1, "$filter matches no single grid item.");
    }
    check(str_contains($html, '>B&amp;W Sahara Sky Hotel Overview Photos</button>') && str_contains($html, '>صور غرف ديلوكس</button>'), 'Album labels changed.');
    check(str_contains($html, 'alt="B&amp;W Sahara Sky Hotel Overview Photos"') && str_contains($html, '<a href="https://hotel.example/galleries/album-3">'), 'Album cover link/alt changed.');
    check(! preg_match('/class="grid-item [^"]*[&(]/', $html), 'Album name leaked into a CSS class.');
});

// 4. Album page photos and description

$galleryFixture = ['images' => []];
function_exists('gallery_meta_data') || eval('function gallery_meta_data($object) { return $GLOBALS["galleryFixture"]["images"]; }');
function_exists('get_galleries') || eval('function get_galleries(...$arguments) { return collect(); }');
$GLOBALS['galleryFixture'] = &$galleryFixture;
$renderAlbum = function (string $description, array $images) use ($compileBlade, $listingTheme, $themePath, &$galleryFixture): array {
    $theme = $listingTheme();
    $galleryFixture['images'] = $images;
    $gallery = new class($description) { public string $name = 'Overview'; public function __construct(public string $description) {} };
    return [$compileBlade(file_get_contents("$themePath/views/gallery.blade.php"), ['gallery' => $gallery]), $theme];
};

test('Photo descriptions become alt text and captions, never link destinations', function () use ($renderAlbum): void {
    [$html] = $renderAlbum('', [
        ['img' => 'galleries/pool.jpg', 'description' => 'Pool view at <b>sunset</b> & dunes'],
        ['img' => 'galleries/lobby.jpg', 'description' => ''],
        ['img' => 'galleries/old.jpg', 'description' => 'https://evil.example/landing'],
    ]);
    check(str_contains($html, 'alt="Pool view at sunset &amp; dunes"'), 'Description is not the plain-text alt.');
    check(str_contains($html, 'data-sub-html="Pool view at &lt;b&gt;sunset&lt;/b&gt; &amp; dunes"'), 'Lightbox caption changed.');
    check(str_contains($html, '<a href="https://hotel.example/storage/galleries/pool.jpg">'), 'Photo does not link to its own image.');
    check(str_contains($html, 'data-src="https://hotel.example/storage/galleries/galleries/pool.jpg"'), 'Lightbox source changed.');
    check(str_contains($html, 'alt="Overview"'), 'Photo without description has no accessible name inside its link.');
    preg_match_all('/href="([^"]*)"/', $html, $hrefs);
    check(count($hrefs[1]) === 3, 'Expected one link per photo.');
    foreach ($hrefs[1] as $href) {
        check(str_starts_with($href, 'https://hotel.example/storage/galleries/'), "Description used as a link: $href");
    }
});

test('Photos with unsafe image URLs keep the image but get no link', function () use ($renderAlbum): void {
    [$html] = $renderAlbum('', [['img' => 'javascript:alert(1)', 'description' => 'x'], ['img' => 'data:image/png;base64,AAAA', 'description' => 'y']]);
    check(! str_contains($html, '<a '), 'Unsafe image URL linked.');
    check(substr_count($html, '<img ') === 2, 'Photo images dropped.');
});

test('Album description cannot add a second H1 and is no longer an h6', function () use ($renderAlbum): void {
    [$html, $theme] = $renderAlbum('<h1 class="intro">Welcome to Your Sahara Sanctuary</h1><p>Located along the road.</p><H1>Second</H1>', []);
    check($theme->regions['pageTitle'] === 'Overview', 'Album name is not the page H1.');
    check(! preg_match('/<h1\b/i', $html) && ! str_contains($html, '<h6'), 'Description still adds an H1 or h6: ' . $html);
    check(str_contains($html, '<div class="custom-gallery-description text-center"><h2 class="intro">Welcome to Your Sahara Sanctuary</h2><p>Located along the road.</p><h2>Second</h2></div>'), 'Description content changed: ' . $html);
});

test('Album description block shows text and media-only content and hides empty markup', function () use ($renderAlbum): void {
    $visible = [
        'text' => '<p>Located along the road.</p>',
        'image' => '<p><img src="https://hotel.example/storage/map.jpg" alt=""></p>',
        'iframe' => '<iframe src="https://www.youtube.com/embed/abc"></iframe>',
    ];
    foreach ($visible as $kind => $description) {
        [$html] = $renderAlbum($description, []);
        check(str_contains($html, '<div class="custom-gallery-description text-center">' . $description . '</div>'), "$kind-only description hidden or changed: $html");
    }
    // "\u{00A0}" is how HTML Purifier outputs an editor's &nbsp; in an empty paragraph.
    foreach (['', '   ', '<p> </p>', '<p>&nbsp;</p>', "<p>\u{00A0}</p>", '<div><br></div>'] as $empty) {
        [$html] = $renderAlbum($empty, []);
        check(! str_contains($html, 'custom-gallery-description'), 'Empty description renders a block: ' . json_encode($empty));
    }
});

test('Album description keeps the previous h6 appearance', function () use ($root): void {
    $css = file_get_contents("$root/platform/themes/riorelax/layouts/base.blade.php");
    preg_match('/\.custom-gallery-description \{([^}]*)\}/', $css, $rule);
    check(isset($rule[1]), 'No style for the description block.');
    foreach (['color: #101010', 'font-family: var(--heading-font), sans-serif', 'font-size: 16px', 'font-weight: 600', 'line-height: 1.2', 'margin-bottom: .5rem'] as $declaration) {
        check(str_contains($rule[1], $declaration), "Missing h6 metric: $declaration");
    }
});

$app->setLocale($listingState['locale']);
foreach ($listingState['facades'] as $facade => $previous) {
    if ($previous !== null) {
        $facade::swap($previous);
    } else {
        $facade::clearResolvedInstance((new ReflectionMethod($facade, 'getFacadeAccessor'))->invoke(null));
    }
}
