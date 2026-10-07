<?php

$themePath = dirname(__DIR__, 2) . '/platform/themes/riorelax';

test('Room gallery keeps the first slide eager and lazy-loads the rest', function () use ($compileBlade, $themePath): void {
    preg_match('/@foreach \(\$room->images as \$img\)\s*<a [^\n]*\n\s*<img [^\n]*\n\s*<\/a>\s*@endforeach/',file_get_contents("$themePath/views/hotel/room.blade.php"), $match);
    check(isset($match[0]), 'Room slider markup not found.');
    class_exists('RvMedia') || class_alias(Botble\Media\Facades\RvMedia::class, 'RvMedia');
    Botble\Media\Facades\RvMedia::swap(new class { public function getImageUrl($image, $size = null) { return "https://hotel.example/storage/$image"; } });
    $room = new class { public array $images = ['a.jpg', 'b.jpg', 'c.jpg']; public string $name = 'Sky Suite'; };
    $html = $compileBlade($match[0], ['room' => $room]);
    preg_match_all('/<img[^>]*>/', $html, $images);
    check(count($images[0]) === 3, 'Expected three slides.');
    check(! str_contains($images[0][0], 'loading='), 'First (LCP) slide is lazy.');
    check(str_contains($images[0][0], 'fetchpriority="high"'), 'First room image is not prioritized.');
    check(substr_count($html, 'fetchpriority="high"') === 1, 'Competing high-priority carousel images.');
    check(substr_count($html, 'decoding="async"') === 3, 'Carousel image decoding is not asynchronous.');
    check(str_contains($images[0][1], 'loading="lazy"') && str_contains($images[0][2], 'loading="lazy"'), 'Later slides are not lazy.');
    check(str_contains($images[0][0], 'alt="Sky Suite"'), 'Slide alt missing.');
});

test('Below-the-fold cards lazy-load; the masonry gallery grid does not', function () use ($themePath): void {
    foreach (['partials/rooms/item.blade.php', 'views/hotel/room-category.blade.php', 'partials/blog/post/item.blade.php', 'views/gallery.blade.php'] as $file) {
        check(str_contains(file_get_contents("$themePath/$file"), 'loading="lazy"'), "$file is not lazy-loaded.");
    }
    // Isotope measures images on load; lazy images would collapse the masonry layout.
    check(! str_contains(file_get_contents("$themePath/partials/gallery/galleries.blade.php"), 'loading="lazy"'), 'Masonry gallery uses lazy images.');
});

test('Decorative background and shape images have empty alt text', function () use ($themePath): void {
    foreach (['feature-area', 'featured-amenities', 'news', 'pricing', 'service-list', 'booking-form'] as $shortcode) {
        $source = file_get_contents("$themePath/partials/shortcodes/$shortcode/index.blade.php");
        check(! preg_match("/alt=\"\{\{ __\('(Background image|Background image 1|Background image 2|Shape image)'\) \}\}\"/", $source), "$shortcode labels a decorative image.");
    }
});
