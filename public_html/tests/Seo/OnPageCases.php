<?php

use Botble\Hotel\Http\Controllers\PublicController;

$renderBreadcrumbs = function (?string $pageTitle, array $crumbs) use ($compileBlade): string {
    Botble\Theme\Facades\Theme::swap(new class($pageTitle, $crumbs) {
        public function __construct(public ?string $pageTitle, public array $crumbs) {}
        public function get(string $key) { return $key === 'pageTitle' ? $this->pageTitle : null; }
        public function breadcrumb(): object { return new class($this->crumbs) { public function __construct(public array $crumbs) {} public function getCrumbs(): array { return $this->crumbs; } }; }
        public function asset(): object { return new class { public function url(string $path): string { return "https://hotel.example/themes/riorelax/$path"; } }; }
    });
    class_exists('Theme') || class_alias(Botble\Theme\Facades\Theme::class, 'Theme');
    class_exists('BaseHelper') || class_alias(Botble\Base\Facades\BaseHelper::class, 'BaseHelper');
    return $compileBlade(file_get_contents(dirname(__DIR__, 2) . '/platform/themes/riorelax/partials/breadcrumbs.blade.php'), []);
};

test('Page title in the breadcrumb banner is the single H1', function () use ($renderBreadcrumbs): void {
    $html = $renderBreadcrumbs('Sky Suite', [
        ['label' => 'Home', 'url' => 'https://hotel.example'],
        ['label' => 'Rooms', 'url' => 'https://hotel.example/rooms'],
        ['label' => 'Sky Suite', 'url' => 'https://hotel.example/rooms/sky-suite'],
    ]);
    check(preg_match_all('/<h1\b/', $html) === 1 && str_contains($html, '<h1>Sky Suite</h1>'), 'Expected one H1: ' . $html);
    check(! str_contains($html, '<h2'), 'Page title still rendered as H2.');
    check(str_contains($html, '<nav aria-label="breadcrumb">') && str_contains($html, 'aria-current="page">Sky Suite</li>'), 'Breadcrumb semantics missing.');
    check(str_contains($html, '<a href="https://hotel.example/rooms">Rooms</a>'), 'Parent crumb is not a link.');
});

test('Banner without a page title renders no empty H1', function () use ($renderBreadcrumbs): void {
    check(! str_contains($renderBreadcrumbs(null, []), '<h1'), 'Empty H1 rendered.');
});

test('Every stylesheet styling the banner title also styles the H1', function (): void {
    $root = dirname(__DIR__, 2);
    foreach (['public/themes/riorelax/css/theme.css', 'public/themes/riorelax/plugins/responsive.css',
        'platform/themes/riorelax/public/css/theme.css', 'platform/themes/riorelax/public/plugins/responsive.css',
        'platform/themes/riorelax/assets/sass/components/_breadcrumb.scss'] as $file) {
        $css = file_get_contents("$root/$file");
        $h2 = preg_match_all('/\.breadcrumb-title h2/', $css);
        $paired = preg_match_all('/\.breadcrumb-title h1,\s*\.breadcrumb-title h2/', $css);
        check($h2 > 0 && $h2 === $paired, "$file styles the banner H2 without the H1.");
    }
});

test('Room and room-category breadcrumbs link back to the rooms listing', function (): void {
    foreach (['getRoom' => '$room->name', 'getRoomCategory' => '$category->name'] as $method => $label) {
        $source = method_source(PublicController::class, $method);
        $listing = strpos($source, "->add(trans('plugins/hotel::hotel.rooms'), route('public.rooms'))");
        $item = strpos($source, "->add($label");
        check($listing !== false && $item !== false && $listing < $item, "$method breadcrumb lacks the rooms parent.");
    }
});

test('Configured homepage metadata falls back to its existing CMS page fields', function (): void {
    $method = method_source(Botble\Page\Services\PageService::class, 'handleFrontRoutes');
    check(str_contains($method, "theme_option('seo_title') ?: Theme::getSiteTitle() ?: \$page->name"), 'Homepage title has no CMS page fallback.');
    check(str_contains($method, "theme_option('seo_description') ?: \$page->description"), 'Homepage description has no CMS page fallback.');
    check(! str_contains($method, '$seoDescription = $page->content'), 'Shortcode content must not become a description.');
});
