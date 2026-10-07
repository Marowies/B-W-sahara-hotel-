<?php

use Botble\Theme\Supports\RobotsTxt;
use Botble\Theme\Http\Controllers\PublicController as ThemePublicController;

test('Robots keeps custom crawl directives and replaces stale sitemap hosts once', function (): void {
    $rules = "User-agent: *\r\nDisallow: /custom/\r\nsItEmAp: https://old.example/map.xml\r\n Sitemap: https://other.example/map.xml\n";
    foreach (['http://localhost:8000/sitemap.xml', 'https://hotel.example/sitemap.xml'] as $url) {
        $body = RobotsTxt::render($rules, $url);
        check(str_contains($body, 'Disallow: /custom/'), 'Custom crawl rules lost.');
        check(substr_count(strtolower($body), 'sitemap:') === 1, 'Duplicate sitemap directive.');
        check(str_contains($body, 'Sitemap: ' . $url), 'Wrong sitemap host.');
        check(! str_contains($body, 'old.example') && ! str_contains($body, 'other.example'), 'Stale host retained.');
    }
});

test('Robots default is served dynamically and Botble editor cannot create a static override', function () use ($source): void {
    check(! is_file($source . '/public/robots.txt'), 'Static robots bypasses Laravel.');
    $routes = file_get_contents($source . '/routes/web.php');
    check(str_contains($routes, "Route::get('robots.txt'") && str_contains($routes, "route('public.sitemap')"), 'Dynamic named sitemap route missing.');
    check(str_contains($routes, 'text/plain; charset=UTF-8'), 'Robots response is not plain text.');
    $form = file_get_contents($source . '/platform/packages/theme/src/Forms/RobotsTxtEditorForm.php');
    $controller = file_get_contents($source . '/platform/packages/theme/src/Http/Controllers/ThemeController.php');
    check(str_contains($form, 'RobotsTxt::path()') && str_contains($controller, 'RobotsTxt::path()'), 'Editor storage paths disagree.');
    check(! str_contains($controller, "move(public_path(), 'robots.txt')"), 'Upload creates a static override.');
    $defaults = RobotsTxt::DEFAULT_CONTENT;
    check(str_contains($defaults, 'User-agent: *') && ! str_contains($defaults, 'wp-'), 'Unexpected default crawl rules.');
});

test('Homepage fallback sets a localized named canonical and uses existing CMS SEO settings', function (): void {
    $method = method_source(ThemePublicController::class, 'getIndex');
    check(str_contains($method, "SeoHelper::meta()->setUrl(route('public.index'))"), 'Homepage canonical missing.');
    check(str_contains($method, "theme_option('seo_title') ?: Theme::getSiteTitle()"), 'Homepage title ignores CMS SEO setting.');
    check(str_contains($method, "SeoHelper::setDescription(theme_option('seo_description'))"), 'Homepage description ignores CMS setting.');
    check(str_contains($method, "theme_option('seo_index', true)"), 'Homepage ignores indexability setting.');
});
