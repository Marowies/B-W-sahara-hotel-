<?php

use Botble\Hotel\Http\Controllers\PublicController;
use Botble\Language\Facades\Language;
use Botble\Language\LanguageManager;
use Botble\Language\Listeners\AddHrefLangListener;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

// Locales mirror the theme's stored option keys (en_US default, ar, zh_CN); runtime values come from the database.
$hreflangLocales = [
    'en' => ['lang_code' => 'en_US', 'lang_locale' => 'en'],
    'ar' => ['lang_code' => 'ar', 'lang_locale' => 'ar'],
    'zh' => ['lang_code' => 'zh_CN', 'lang_locale' => 'zh'],
];
$hreflangUrls = [
    'en' => 'https://hotel.example/rooms/deluxe-room',
    'ar' => 'https://hotel.example/ar/rooms/deluxe-room',
    'zh' => 'https://hotel.example/zh/rooms/deluxe-room',
];
$languageStub = new class($hreflangLocales, $hreflangUrls) {
    public function __construct(public array $locales, public array $urls) {}
    public function getSupportedLocales(): array { return $this->locales; }
    public function getDefaultLocale(): string { return 'en'; }
    public function getLocalizedURL($locale = null, $url = null, array $attributes = [], $force = true): string { return $this->urls[$locale] . '/'; }
    public function formatLocaleForHrefLang(?string $code): ?string {
        return (new ReflectionClass(LanguageManager::class))->newInstanceWithoutConstructor()->formatLocaleForHrefLang($code);
    }
    public array $switcherUrls = []; public function supportedModels(): array { return [Botble\Page\Models\Page::class]; } public function setSwitcherURLs(array $urls): self { $this->switcherUrls = $urls; return $this; }
};
$compileBlade = function (string $template, array $data) use ($app): string {
    $files = new Filesystem();
    $__env = new Illuminate\View\Factory(new Illuminate\View\Engines\EngineResolver(), new Illuminate\View\FileViewFinder($files, []), $app['events']);
    $compiled = (new BladeCompiler($files, sys_get_temp_dir()))->compileString($template);
    extract($data);
    ob_start();
    eval('?>' . $compiled);
    return ob_get_clean();
};

test('Hotel listing and detail pages dispatch the event that emits hreflang', function (): void {
    check(str_contains(method_source(PublicController::class, 'getRooms'), 'event(new RenderingSingleEvent(new Slug()))'), 'getRooms emits no hreflang.');
    foreach (['getRoom', 'getRoomCategory', 'getPlace', 'getService', 'getFood'] as $method) {
        check(str_contains(method_source(PublicController::class, $method), 'event(new RenderingSingleEvent($slug))'), "$method emits no hreflang.");
    }
});

test('Booking and checkout pages do not join hreflang clusters', function (): void {
    foreach (['getBooking', 'checkoutSuccess', 'postBooking', 'postCheckout'] as $method) {
        check(! str_contains(method_source(PublicController::class, $method), 'RenderingSingleEvent'), "$method emits hreflang.");
    }
});

test('Hreflang codes derive from each language code and URLs are absolute and reciprocal', function () use ($app, $languageStub, $hreflangUrls): void {
    Language::swap($languageStub);
    $app->instance('url', new Illuminate\Routing\UrlGenerator(new Illuminate\Routing\RouteCollection(), Illuminate\Http\Request::create($hreflangUrls['ar'])));
    $listener = new class extends AddHrefLangListener {
        public function urls(): array { return $this->generateHreflangUrls(null, null); }
    };
    foreach (['en', 'ar', 'zh'] as $current) {
        $app->setLocale($current);
        $urls = $listener->urls();
        $codes = array_keys($urls);
        sort($codes);
        check($codes === ['ar', 'en', 'en-us', 'zh', 'zh-cn'], "Codes on $current: " . implode(',', $codes));
        check($urls['en-us'] === $hreflangUrls['en'] && $urls['ar'] === $hreflangUrls['ar'] && $urls['zh-cn'] === $hreflangUrls['zh'], "Wrong targets on $current.");
        foreach ($urls as $url) {
            check(str_starts_with($url, 'https://') && ! str_ends_with($url, '/'), "Non-absolute or slashed URL: $url");
        }
    }
});

test('Hreflang partial renders x-default plus one alternate per code', function () use ($app, $compileBlade, $languageStub, $hreflangUrls): void {
    Language::swap($languageStub);
    class_exists('Language') || class_alias(Language::class, 'Language');
    $app->instance('url', new Illuminate\Routing\UrlGenerator(new Illuminate\Routing\RouteCollection(), Illuminate\Http\Request::create($hreflangUrls['zh'])));
    $template = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/language/resources/views/partials/hreflang.blade.php');
    $html = $compileBlade($template, ['hreflangUrls' => ['en-us' => $hreflangUrls['en'], 'en' => $hreflangUrls['en'], 'ar' => $hreflangUrls['ar'], 'zh-cn' => $hreflangUrls['zh'], 'zh' => $hreflangUrls['zh']]]);
    check(preg_match_all('/rel="alternate"/', $html) === 6, 'Unexpected alternate count: ' . $html);
    check((bool) preg_match('#href="https://hotel\.example/rooms/deluxe-room"\s+hreflang="x-default"#', $html), 'x-default should point to the default-language URL.');
    check((bool) preg_match('#href="https://hotel\.example/zh/rooms/deluxe-room"\s+hreflang="zh-cn"#', $html), 'Chinese alternate missing.');
});

test('HTML lang is a valid BCP 47 tag for every locale form', function () use ($app, $compileBlade): void {
    preg_match('/<html lang="[^"]*">/', file_get_contents(dirname(__DIR__, 2) . '/platform/themes/riorelax/layouts/base.blade.php'), $match);
    check(isset($match[0]), 'html lang attribute missing.');
    foreach (['en' => 'en', 'ar' => 'ar', 'zh' => 'zh', 'zh_CN' => 'zh-CN'] as $locale => $expected) {
        $app->setLocale($locale);
        $html = $compileBlade($match[0], []);
        check($html === "<html lang=\"$expected\">", "Locale $locale rendered $html");
    }
});

test('x-default uses the resolved default-language slug from the alternate cluster', function () use ($compileBlade, $languageStub): void {
    Language::swap($languageStub);
    $template = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/language/resources/views/partials/hreflang.blade.php');
    $html = $compileBlade($template, ['hreflangUrls' => ['en-us' => 'https://hotel.example/rooms/translated-default', 'ar' => 'https://hotel.example/ar/rooms/arabic-slug']]);
    check((bool) preg_match('#href="https://hotel.example/rooms/translated-default"\s+hreflang="x-default"#', $html), 'x-default ignored the resolved translated URL.');
});

test('Generic hreflang targets remain reciprocal with multiple regions of one language', function () use ($app, $languageStub): void {
    $originalLocales = $languageStub->locales;
    $originalUrls = $languageStub->urls;
    try {
        $languageStub->locales['en-gb'] = ['lang_code' => 'en_GB', 'lang_locale' => 'en-gb'];
        $languageStub->urls['en-gb'] = 'https://hotel.example/en-gb/rooms/deluxe-room';
        Language::swap($languageStub);
        $listener = new class extends AddHrefLangListener {
            public function urls(): array { return $this->generateHreflangUrls(null, null); }
        };
        $expected = null;
        foreach (['en', 'en-gb', 'ar', 'zh'] as $locale) {
            $app->setLocale($locale);
            $urls = $listener->urls();
            $expected ??= $urls;
            check($urls === $expected, 'Alternates changed depending on the current language.');
            check($urls['en'] === $languageStub->urls['en'], 'Generic English target is unstable.');
        }
    } finally {
        $languageStub->locales = $originalLocales;
        $languageStub->urls = $originalUrls;
    }
});

test('Resolved language switcher URLs are available before the header is rendered', function () use ($languageStub, $hreflangUrls): void {
    Language::swap($languageStub);
    $languageStub->switcherUrls = [];
    $listener = new class($hreflangUrls) extends AddHrefLangListener {
        public function __construct(private array $targets) {}
        protected function generateHreflangUrls(?string $type, int|string|null $id): array {
            return ['en-us' => $this->targets['en'], 'ar' => $this->targets['ar'], 'zh-cn' => $this->targets['zh']];
        }
    };
    defined('THEME_FRONT_HEADER') || define('THEME_FRONT_HEADER', 'theme_front_header');
    $saved = $GLOBALS['seoFilters'][THEME_FRONT_HEADER] ?? [];
    try {
        $listener->handle(new Botble\Theme\Events\RenderingSingleEvent(new Botble\Slug\Models\Slug()));
        check(count($languageStub->switcherUrls) === 3, 'Switcher targets are unavailable until header rendering.');
        check(array_column($languageStub->switcherUrls, 'url') === array_values($hreflangUrls), 'Early switcher destinations are wrong.');
    } finally { $GLOBALS['seoFilters'][THEME_FRONT_HEADER] = $saved; }
});