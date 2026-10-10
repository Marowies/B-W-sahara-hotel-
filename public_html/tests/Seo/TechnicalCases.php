<?php

use Botble\Theme\Supports\RobotsTxt;
use Botble\Theme\Http\Controllers\PublicController as ThemePublicController;

test('Robots preserves custom rules and additional sitemap directives', function (): void {
    $rules = "User-agent: *\r\nDisallow: /custom/\r\nsItEmAp: https://old.example/map.xml\r\n Sitemap: https://other.example/map.xml\n";
    foreach (['http://localhost:8000/sitemap.xml', 'https://hotel.example/sitemap.xml'] as $url) {
        $body = RobotsTxt::render($rules, $url);
        check(str_contains($body, 'Disallow: /custom/'), 'Custom crawl rules lost.');
        check(substr_count(strtolower($body), 'sitemap:') === 3, 'Duplicate sitemap directive.');
        check(str_contains($body, 'Sitemap: ' . $url), 'Wrong sitemap host.');
        check(str_contains($body, 'old.example') && str_contains($body, 'other.example'), 'Custom sitemap lost.');
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

test('Robots upgrade backs up exact custom bytes and removes the static override', function (): void {
    $dir = sys_get_temp_dir() . '/robots-' . bin2hex(random_bytes(8));
    mkdir($dir);
    $rules = "User-agent: CustomBot\r\nDisallow: /private/\r\nCrawl-delay: 12\r\nSitemap: https://maps.example/custom.xml\r\n";
    try {
        file_put_contents($dir . '/legacy', $rules);
        RobotsTxt::migrateLegacy($dir . '/legacy', $dir . '/storage', $dir . '/backup');
        check(! file_exists($dir . '/legacy'), 'Static override remains.');
        check(file_get_contents($dir . '/backup') === $rules && file_get_contents($dir . '/storage') === $rules, 'Rules changed during upgrade.');
        check(RobotsTxt::render(RobotsTxt::render($rules, 'https://hotel.example/sitemap.xml'), 'https://hotel.example/sitemap.xml') === RobotsTxt::render($rules, 'https://hotel.example/sitemap.xml'), 'Rendering is not idempotent.');
    } finally {
        foreach (glob($dir . '/*') as $path) { unlink($path); }
        rmdir($dir);
    }
});

test('Robots upgrade refuses existing storage or backup and keeps the legacy override', function (): void {
    foreach (['storage', 'backup'] as $existing) {
        $dir = sys_get_temp_dir() . '/robots-' . bin2hex(random_bytes(8));
        mkdir($dir);
        try {
            file_put_contents($dir . '/legacy', 'custom rules');
            file_put_contents($dir . '/' . $existing, 'existing rules');
            $failed = false;
            try { RobotsTxt::migrateLegacy($dir . '/legacy', $dir . '/storage', $dir . '/backup'); }
            catch (RuntimeException) { $failed = true; }
            check($failed && file_get_contents($dir . '/' . $existing) === 'existing rules' && is_file($dir . '/legacy'), 'Upgrade overwrote a file or retired static rules prematurely.');
        } finally {
            foreach (glob($dir . '/*') as $path) { unlink($path); }
            rmdir($dir);
        }
    }
});
