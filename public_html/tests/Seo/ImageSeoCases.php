<?php

// Image SEO batch: decorative/duplicate alt text (C6), no empty image sources (C7), homepage hero preload (C2).
// Real templates rendered with doubles; no CMS records or image assets are involved.

use Botble\Theme\Supports\HeroImagePreload;

$root = dirname(__DIR__, 2);
$themePath = "$root/platform/themes/riorelax";

$imageFacades = [Botble\Theme\Facades\Theme::class, Botble\Media\Facades\RvMedia::class, Illuminate\Support\Facades\Route::class];
$imageState = ['facades' => [], 'filters' => $GLOBALS['seoFilters']['theme_front_meta'] ?? []];
foreach ($imageFacades as $facade) {
    try { $imageState['facades'][$facade] = $facade::getFacadeRoot(); } catch (Throwable) { $imageState['facades'][$facade] = null; }
}

$imageDoubles = function (array $regions = [], ?string $routeName = null): object {
    $theme = new class($regions) {
        public function __construct(public array $regions) {}
        public function get(string $key, $default = null) { return $this->regions[$key] ?? $default; }
        public function set(string $key, $value): self { $this->regions[$key] = $value; return $this; }
        public function partial(string $view, array $data = []): string { return ''; }
    };
    Botble\Theme\Facades\Theme::swap($theme);
    Botble\Media\Facades\RvMedia::swap(new class {
        public function getImageUrl($url, $size = null, $relativePath = false, $default = null) {
            return $url ? 'https://hotel.example/storage/' . ($size ? "$size/" : '') . $url : $default;
        }
        public function getDefaultImage($relative = false, $size = null) { return 'https://hotel.example/vendor/core/core/base/images/placeholder.png'; }
    });
    Illuminate\Support\Facades\Route::swap(new class($routeName) {
        public function __construct(public ?string $name) {}
        public function currentRouteName(): ?string { return $this->name; }
    });
    class_exists('Theme') || class_alias(Botble\Theme\Facades\Theme::class, 'Theme');
    class_exists('RvMedia') || class_alias(Botble\Media\Facades\RvMedia::class, 'RvMedia');
    class_exists('Arr') || class_alias(Illuminate\Support\Arr::class, 'Arr');
    class_exists('BaseHelper') || class_alias(Botble\Base\Facades\BaseHelper::class, 'BaseHelper');
    $GLOBALS['seoFilters']['theme_front_meta'] = [];
    return $theme;
};
$imgLine = function (string $file, int $line) use ($themePath): string {
    return file($themePath . '/' . $file)[$line - 1] ?? '';
};

// C6: decorative or duplicate images are silent; meaningful alt text stays.

test('Decorative and duplicate images have empty alt text', function () use ($imgLine): void {
    $silent = [
        ['partials/shortcodes/service-list/index.blade.php', 17, 'second copy of the service image; the heading names it'],
        ['partials/shortcodes/featured-amenities/index.blade.php', 35, 'second copy of the amenity icon; the heading names it'],
        ['views/hotel/room.blade.php', 41, 'thumbnail strip repeating the main slider'],
        ['views/hotel/room.blade.php', 72, 'amenity icon beside its visible name'],
        ['partials/main-menu.blade.php', 6, 'menu icon beside the link text'],
        ['partials/shortcodes/testimonials/index.blade.php', 35, 'review stars glyph'],
        ['partials/shortcodes/testimonials/index.blade.php', 41, 'quote glyph'],
        ['partials/shortcodes/about-us/styles/style-1.blade.php', 4, 'floating animation shape'],
        ['partials/shortcodes/about-us/styles/style-2.blade.php', 4, 'floating animation shape'],
        ['partials/shortcodes/services/index.blade.php', 4, 'floating animation shape'],
        ['partials/shortcodes/newsletter/index.blade.php', 4, 'floating animation shape'],
        ['partials/shortcodes/why-choose-us/index.blade.php', 4, 'background animation shape'],
    ];
    foreach ($silent as [$file, $line, $why]) {
        $source = $imgLine($file, $line);
        check(str_contains($source, '<img ') && str_contains($source, 'alt=""'), "$file:$line ($why) is not alt=\"\": " . trim($source));
    }
});

test('Content photos, unlabeled amenity icons and functional controls keep their alt text', function () use ($imgLine): void {
    $kept = [
        ['partials/shortcodes/service-list/index.blade.php', 14, 'alt="{{ $service->name }}"'],
        ['partials/shortcodes/featured-amenities/index.blade.php', 32, 'alt="{{ $amenity->name }}"'],
        ['partials/rooms/item.blade.php', 60, 'alt="{{ $amenity->name }}"'],
        ['views/hotel/room-category.blade.php', 41, 'alt="{{ $amenity->name }}"'],
        ['views/hotel/room.blade.php', 35, 'alt="{{ $room->name }}"'],
        ['partials/rooms/item.blade.php', 17, 'alt="{{ $room->name }}"'],
        ['partials/gallery/galleries.blade.php', 22, 'alt="{{ $gallery->name }}"'],
        ['partials/shortcodes/testimonials/index.blade.php', 29, 'alt="{{ $testimonial->name }}"'],
        ['partials/shortcodes/about-us/styles/style-1.blade.php', 12, 'alt="{{ $shortcode->title }}"'],
        ['partials/shortcodes/services/index.blade.php', 12, 'alt="{{ $shortcode->title }}"'],
        ['partials/shortcodes/why-choose-us/index.blade.php', 47, 'alt="{{ $shortcode->title }}"'],
        ['partials/shortcodes/intro-video/index.blade.php', 25, 'alt="{{ __(\'Button play\') }}"'],
    ];
    foreach ($kept as [$file, $line, $alt]) {
        check(str_contains($imgLine($file, $line), $alt), "$file:$line lost its meaningful alt.");
    }
});

test('Room thumbnail strip is silent while the main slides keep the room name', function () use ($compileBlade, $imageDoubles, $themePath): void {
    $imageDoubles();
    preg_match('/<div class="room-details-slider-nav">.*?<\/div>/s', file_get_contents("$themePath/views/hotel/room.blade.php"), $nav);
    check(isset($nav[0]), 'Thumbnail strip not found.');
    $room = new class { public array $images = ['a.jpg', 'b.jpg']; public string $name = 'Deluxe Single Room'; };
    $html = $compileBlade($nav[0], ['room' => $room]);
    check(substr_count($html, 'alt=""') === 2 && ! str_contains($html, 'Deluxe Single Room'), 'Thumbnails repeat the room name: ' . $html);
});

// C7: no empty image sources.

test('Room and room-category cards never render an empty image source', function () use ($compileBlade, $imageDoubles, $themePath): void {
    // The room card block renders whole; the category card's link needs request/hotel helpers, so only its image line
    // (the changed code) renders, behind the same "@if ($images = $room->images)" guard.
    foreach (['partials/rooms/item.blade.php', 'views/hotel/room-category.blade.php'] as $file) {
        $source = file_get_contents("$themePath/$file");
        if (str_contains($file, 'room-category')) {
            preg_match('/<img src="\{\{ RvMedia::getImageUrl\(Arr::first[^\n]*/', $source, $line);
            $block = isset($line[0]) ? ["@if (\$images = \$room->images)\n{$line[0]}\n@endif"] : [];
        } else {
            preg_match('/@if \(\$images = \$room->images\).*?@endif/s', $source, $block);
        }
        check(isset($block[0]), "Card image block not found in $file.");
        $imageDoubles();
        foreach ([[['', 'rooms/deluxe.jpg'], 'https://hotel.example/storage/medium/rooms/deluxe.jpg'],
            [['', null], 'https://hotel.example/vendor/core/core/base/images/placeholder.png']] as [$images, $expected]) {
            $room = new class($images) { public string $name = 'Deluxe Single Room'; public string $url = 'https://hotel.example/rooms/deluxe'; public function __construct(public array $images) {} };
            $html = $compileBlade($block[0], ['room' => $room, 'roomUrl' => $room->url, 'startDate' => '2026-10-08', 'endDate' => '2026-10-09']);
            check(str_contains($html, 'src="' . $expected . '"'), "$file did not use $expected: $html");
            check(! str_contains($html, 'src=""') && str_contains($html, 'alt="Deluxe Single Room"'), "$file rendered an empty source or lost its alt.");
        }
        $empty = new class { public array $images = []; public string $name = 'x'; public string $url = 'u'; };
        check(! str_contains($compileBlade($block[0], ['room' => $empty, 'roomUrl' => 'u', 'startDate' => '', 'endDate' => '']), '<img'), "$file renders an image for a room without images.");
    }
});

test('Gallery covers fall back to the placeholder instead of an empty source', function () use ($compileBlade, $imageDoubles, $themePath): void {
    $imageDoubles();
    $albums = collect([
        new class { public string $name = 'Overview'; public ?string $image = null; public string $url = 'https://hotel.example/galleries/overview'; public function getKey() { return 1; } },
        new class { public string $name = 'Deluxe Room'; public ?string $image = 'galleries/deluxe.jpg'; public string $url = 'https://hotel.example/galleries/deluxe-room'; public function getKey() { return 2; } },
    ]);
    $html = $compileBlade(file_get_contents("$themePath/partials/gallery/galleries.blade.php"), ['galleries' => $albums]);
    check(str_contains($html, 'src="https://hotel.example/vendor/core/core/base/images/placeholder.png" alt="Overview"'), 'Cover without image has no placeholder.');
    check(str_contains($html, 'src="https://hotel.example/storage/medium/galleries/deluxe.jpg" alt="Deluxe Room"'), 'Real cover changed.');
    check(! str_contains($html, 'src=""'), 'Empty cover source rendered.');
});

// C2: one preload, homepage top hero only.

$slide = fn (?string $image, string $title = '') => new class($image, $title) {
    public ?string $description = null;
    public function __construct(public ?string $image, public string $title) {}
    public function getMetaData(string $key, bool $single = false) { return null; }
};
$preloads = fn (): array => (preg_match_all('/<link rel="preload" as="image" href="([^"]*)" fetchpriority="high">/', (string) apply_filters('theme_front_meta', null), $m) ? $m[1] : []);

test('Homepage hero preloads exactly its first slide, once per page', function () use ($compileBlade, $imageDoubles, $themePath, $slide, $preloads): void {
    $theme = $imageDoubles(['breadcrumb' => '0', 'pageTitle' => 'Home'], 'public.index');
    $slider = file_get_contents("$themePath/partials/shortcodes/simple-slider/index.blade.php");
    $html = $compileBlade($slider, ['sliders' => collect([$slide('sliders/first.jpg', 'Welcome'), $slide('sliders/second.jpg', 'Second')])]);
    check($preloads() === ['https://hotel.example/storage/sliders/first.jpg'], 'Preloads: ' . json_encode($preloads()));
    check(str_contains($html, 'background-image:url(https://hotel.example/storage/sliders/first.jpg)'), 'Preload URL differs from the painted background.');
    // A second hero on the same page adds nothing.
    $banner = new class { public $background_color = null, $background_image = 'banners/hero.jpg', $title = null, $description = null, $button_label = null, $button_url = null, $form_title = null; };
    $compileBlade(file_get_contents("$themePath/partials/shortcodes/hero-banner-with-booking-form/index.blade.php"), ['shortcode' => $banner]);
    check(count($preloads()) === 1 && $theme->regions['heroImagePreload'] === 'https://hotel.example/storage/sliders/first.jpg', 'Second hero added a preload.');

    // Untitled slides never claim the hero heading; the preload flag alone must stop the next hero.
    $imageDoubles(['breadcrumb' => '0'], 'public.index');
    $compileBlade($slider, ['sliders' => collect([$slide('sliders/first.jpg')])]);
    $compileBlade(file_get_contents("$themePath/partials/shortcodes/hero-banner-with-booking-form/index.blade.php"), ['shortcode' => $banner]);
    check($preloads() === ['https://hotel.example/storage/sliders/first.jpg'], 'Untitled slider let a second hero preload: ' . json_encode($preloads()));
});

test('Hero banner alone preloads its background on the homepage', function () use ($compileBlade, $imageDoubles, $themePath, $preloads): void {
    $imageDoubles(['breadcrumb' => '0'], 'public.index');
    $banner = new class { public $background_color = null, $background_image = 'banners/hero.jpg', $title = 'Title', $description = null, $button_label = null, $button_url = null, $form_title = null; };
    $html = $compileBlade(file_get_contents("$themePath/partials/shortcodes/hero-banner-with-booking-form/index.blade.php"), ['shortcode' => $banner]);
    check($preloads() === ['https://hotel.example/storage/banners/hero.jpg'], 'Banner preload: ' . json_encode($preloads()));
    check(str_contains($html, "background-image: url('https://hotel.example/storage/banners/hero.jpg')"), 'Banner background changed.');
});

test('No hero preload on inner pages, below a banner H1, or without an image', function () use ($compileBlade, $imageDoubles, $themePath, $slide, $preloads): void {
    $slider = file_get_contents("$themePath/partials/shortcodes/simple-slider/index.blade.php");
    foreach ([
        'inner page route' => [['breadcrumb' => '0'], 'public.single', [$slide('sliders/first.jpg')]],
        'below a breadcrumb H1' => [['breadcrumb' => '1', 'pageTitle' => 'About'], 'public.index', [$slide('sliders/first.jpg')]],
        'first slide without image' => [['breadcrumb' => '0'], 'public.index', [$slide(null), $slide('sliders/second.jpg')]],
        'no slides' => [['breadcrumb' => '0'], 'public.index', []],
    ] as $case => [$regions, $route, $slides]) {
        $imageDoubles($regions, $route);
        $compileBlade($slider, ['sliders' => collect($slides)]);
        check($preloads() === [], "$case added a preload: " . json_encode($preloads()));
    }
});

test('Hero preload tag escapes the URL once', function (): void {
    check(HeroImagePreload::tag('https://hotel.example/storage/a.jpg?v=1&w=2') === '<link rel="preload" as="image" href="https://hotel.example/storage/a.jpg?v=1&amp;w=2" fetchpriority="high">', 'Preload tag escaping changed.');
});

$GLOBALS['seoFilters']['theme_front_meta'] = $imageState['filters'];
foreach ($imageState['facades'] as $facade => $previous) {
    if ($previous !== null) {
        $facade::swap($previous);
    } else {
        $facade::clearResolvedInstance((new ReflectionMethod($facade, 'getFacadeAccessor'))->invoke(null));
    }
}
