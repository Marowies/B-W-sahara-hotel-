<?php

// Regressions for the six safe code gaps in the 2026-10-08 live audit (cookie link, homepage H1, logo name,
// generic schema, locale aliases, empty archives). Fixtures are in-memory; no hotel facts are invented.

use Botble\Blog\Listeners\RenderingSiteMapListener;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Blog\Models\Tag;
use Botble\Blog\Services\BlogService;
use Botble\CookieConsent\Supports\LearnMoreUrl;
use Botble\Hotel\Supports\HotelSchema;
use Botble\Slug\Models\Slug;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;
use Botble\Theme\Supports\JsonLd;
use Illuminate\Pagination\LengthAwarePaginator;

$root = dirname(__DIR__, 2);
$themePath = "$root/platform/themes/riorelax";

// Shared runner state this file changes; restored at the end so later case files start unchanged.
$swappedFacades = [Botble\Theme\Facades\Theme::class, Botble\Media\Facades\RvMedia::class, Botble\Base\Facades\BaseHelper::class,
    SiteMapManager::class, Botble\SeoHelper\Facades\SeoHelper::class];
$sharedState = ['locale' => $app->getLocale(), 'url' => $app['url'], 'options' => $GLOBALS['seoThemeOptions'] ?? [], 'facades' => []];
foreach ($swappedFacades as $facade) {
    try { $sharedState['facades'][$facade] = $facade::getFacadeRoot(); } catch (Throwable) { $sharedState['facades'][$facade] = null; }
}

// Theme facade double with real region storage, so templates can share the one-H1 flag.
$themeStub = function (array $regions = [], ?string $siteTitle = null, ?string $logo = null): object {
    $theme = new class($regions, $siteTitle, $logo) {
        public function __construct(public array $regions, public ?string $siteTitle, public ?string $logo) {}
        public function get(string $key, $default = null) { return $this->regions[$key] ?? $default; }
        public function set(string $key, $value): self { $this->regions[$key] = $value; return $this; }
        public function getSiteTitle(): ?string { return $this->siteTitle; }
        public function getLogo(): ?string { return $this->logo; }
        public function partial(string $view, array $data = []): string { return ''; }
        public function getThemeNamespace(string $view = ''): string { return "theme.riorelax::$view"; }
    };
    Botble\Theme\Facades\Theme::swap($theme);
    class_exists('Theme') || class_alias(Botble\Theme\Facades\Theme::class, 'Theme');
    class_exists('BaseHelper') || class_alias(Botble\Base\Facades\BaseHelper::class, 'BaseHelper');
    class_exists('RvMedia') || class_alias(Botble\Media\Facades\RvMedia::class, 'RvMedia');
    class_exists('Rvmedia') || class_alias(Botble\Media\Facades\RvMedia::class, 'Rvmedia');
    Botble\Media\Facades\RvMedia::swap(new class {
        public function getImageUrl($image, $size = null, $relative = false, $default = null) { return $image ? "https://hotel.example/storage/$image" : $default; }
        public function getDefaultImage() { return 'https://hotel.example/storage/default.png'; }
    });
    return $theme;
};

// 1. Cookie learn-more link

test('Cookie learn-more paths join the localized homepage with exactly one slash', function (): void {
    $cases = [
        ['/cookie-policy', 'https://hotel.example/ar', 'https://hotel.example/ar/cookie-policy'],
        ['cookie-policy', 'https://hotel.example/zh/', 'https://hotel.example/zh/cookie-policy'],
        ['//cookie-policy', 'https://hotel.example', 'https://hotel.example/cookie-policy'],
        [' /سياسة-الكوكيز ', 'https://hotel.example/ar', 'https://hotel.example/ar/سياسة-الكوكيز'],
        ['https://policies.example/cookies', 'https://hotel.example/ar', 'https://policies.example/cookies'],
        ['HTTP://hotel.example/zh/cookie-policy', 'https://hotel.example/zh', 'HTTP://hotel.example/zh/cookie-policy'],
    ];
    foreach ($cases as [$configured, $home, $expected]) {
        $actual = LearnMoreUrl::resolve($configured, $home);
        check($actual === $expected, "'$configured' on $home became " . var_export($actual, true));
    }
});

test('A missing cookie policy destination renders no link instead of pointing at the homepage', function (): void {
    foreach ([null, '', '   ', '/', '///', ['array']] as $configured) {
        check(LearnMoreUrl::resolve($configured, 'https://hotel.example/ar') === null, 'Empty destination produced a link: ' . json_encode($configured));
    }
    check(LearnMoreUrl::resolve('/cookie-policy', null) === null, 'Link built without a homepage URL.');
});

test('Cookie banner renders the normalized link and keeps its consent controls', function () use ($compileBlade, $root): void {
    $view = file_get_contents("$root/platform/plugins/cookie-consent/resources/views/index.blade.php");
    check(! str_contains($view, "BaseHelper::getHomepageUrl() . '/' ."), 'Unnormalized concatenation remains.');
    foreach (['js-site-notice-agree', 'js-site-notice-reject', 'js-site-notice-customize', 'data-site-cookie-name', 'hotel:consent'] as $control) {
        check(str_contains($view, $control), "Consent control changed: $control");
    }
    preg_match('/<div class="site-notice__message">.*?<\/div>/s', $view, $message);
    check(isset($message[0]), 'Cookie message markup not found.');
    $previous = Botble\Base\Facades\BaseHelper::getFacadeRoot();
    $render = function (string $home, array $options) use ($compileBlade, $message): string {
        $GLOBALS['seoThemeOptions'] = $options;
        Botble\Base\Facades\BaseHelper::swap(new class($home) {
            public function __construct(public string $home) {}
            public function getHomepageUrl() { return $this->home; }
            public function clean($value) { return $value; }
        });
        return $compileBlade($message[0], []);
    };
    try {
        $ar = $render('https://hotel.example/ar', ['cookie_consent_learn_more_url' => '/cookie-policy', 'cookie_consent_learn_more_text' => 'سياسة ملفات تعريف الارتباط']);
        check(str_contains($ar, '<a href="https://hotel.example/ar/cookie-policy">سياسة ملفات تعريف الارتباط</a>'), 'Arabic link: ' . $ar);
        $zh = $render('https://hotel.example/zh', ['cookie_consent_learn_more_url' => 'https://hotel.example/zh/cookie-policy', 'cookie_consent_learn_more_text' => 'Cookie 政策']);
        check(str_contains($zh, 'href="https://hotel.example/zh/cookie-policy"'), 'Translated absolute destination changed: ' . $zh);
        $empty = $render('https://hotel.example', ['cookie_consent_learn_more_url' => '', 'cookie_consent_learn_more_text' => 'Cookie Policy']);
        check(! str_contains($empty, '<a '), 'Link rendered without a destination.');
        check(! preg_match('#https?://[^"]+//#', $ar . $zh), 'Double slash in a cookie link.');
    } finally {
        $GLOBALS['seoThemeOptions'] = [];
        Botble\Base\Facades\BaseHelper::swap($previous);
    }
});

// 2. Homepage H1

$slide = fn (string $title) => new class($title) {
    public ?string $image = 'slide.jpg';
    public ?string $description = null;
    public function __construct(public string $title) {}
    public function getMetaData(string $key, bool $single = false) { return null; }
};
$renderSlider = function (array $regions, array $titles) use ($compileBlade, $themePath, $themeStub, $slide): array {
    $theme = $themeStub($regions);
    $html = $compileBlade(file_get_contents("$themePath/partials/shortcodes/simple-slider/index.blade.php"), ['sliders' => array_map($slide, $titles)]);
    return [$html, $theme];
};
$renderHeroBanner = function (string $title) use ($compileBlade, $themePath): string {
    $shortcode = new class($title) {
        public $background_color = null, $background_image = null, $description = null, $button_label = null, $button_url = null, $form_title = null;
        public function __construct(public string $title) {}
    };
    return $compileBlade(file_get_contents("$themePath/partials/shortcodes/hero-banner-with-booking-form/index.blade.php"), ['shortcode' => $shortcode]);
};

test('Homepage slider renders only its first CMS slide title as the single H1', function () use ($renderSlider): void {
    // A CMS page with the breadcrumb banner switched off (the homepage) sets breadcrumb = "0".
    [$html] = $renderSlider(['breadcrumb' => '0', 'pageTitle' => 'Home'], ['B&W Sahara Sky Hotel', 'Second slide', 'Third slide']);
    check(preg_match_all('/<h1\b/', $html) === 1, 'Expected exactly one H1: ' . $html);
    check((bool) preg_match('/<h1 data-animation="fadeInUp" data-delay=".4s">B&W Sahara Sky Hotel<\/h1>/', $html), 'First slide title is not the H1.');
    check(preg_match_all('/<h2\b/', $html) === 2, 'Later slides must stay H2.');
});

test('Slider skips untitled slides and never promotes a heading under a breadcrumb H1', function () use ($renderSlider): void {
    [$html] = $renderSlider(['breadcrumb' => '0'], ['', 'Configured heading']);
    check(preg_match_all('/<h1\b/', $html) === 1 && str_contains($html, '>Configured heading</h1>'), 'First titled slide not promoted: ' . $html);
    [$inner] = $renderSlider(['breadcrumb' => '1', 'pageTitle' => 'About us'], ['Slide']);
    check(! str_contains($inner, '<h1') && str_contains($inner, '>Slide</h2>'), 'Inner page with breadcrumb H1 gained a second H1.');
    [$none] = $renderSlider(['breadcrumb' => '0'], []);
    check(! str_contains($none, '<h1') && ! str_contains($none, '<h2'), 'Empty slider rendered a heading.');
});

test('Hero banner and slider on one page share a single H1', function () use ($renderSlider, $renderHeroBanner): void {
    [$slider, $theme] = $renderSlider(['breadcrumb' => '0', 'pageTitle' => 'Home'], ['Slider title']);
    $banner = $renderHeroBanner('Banner title');
    check(preg_match_all('/<h1\b/', $slider . $banner) === 1, 'Two hero H1s on one page.');
    check((bool) preg_match('/<h2[^>]*class="mb-15">\s*Banner title\s*<\/h2>/', $banner), 'Second hero title not H2: ' . $banner);
    $theme->regions = ['breadcrumb' => '0', 'pageTitle' => 'Home'];
    check((bool) preg_match('/<h1[^>]*class="mb-15">\s*Banner title\s*<\/h1>/', $renderHeroBanner('Banner title')), 'Banner alone is not the H1.');
});

test('Hero banner is never lazy-loaded, so its H1 is decided in the page render', function () use ($root): void {
    // A lazy shortcode is rendered by a separate AJAX request without the page's breadcrumb/H1 state.
    $source = file_get_contents("$root/platform/themes/riorelax/functions/shortcodes.php");
    $register = strpos($source, "Shortcode::register(\n            'hero-banner-with-booking-form'");
    $ignore = strpos($source, "Shortcode::ignoreLazyLoading(['hero-banner-with-booking-form']);");
    $nextBlock = strpos($source, "if (is_plugin_active('hotel'))");
    check($register !== false && $ignore !== false && $register < $ignore && $ignore < $nextBlock, 'Hero banner can still be lazy-loaded.');
    check(substr_count($source, 'ignoreLazyLoading(') === 1, 'Lazy loading changed for other shortcodes.');
    // The compiler skips the AJAX placeholder for every name in this list (ShortcodeCompiler::render).
    $compiler = file_get_contents("$root/platform/packages/shortcode/src/Compilers/ShortcodeCompiler.php");
    check(str_contains($compiler, "\$compiled->enable_lazy_loading === 'yes' && ! request()->expectsJson() && ! \$this->shouldIgnoreLazyLoading(\$name)"), 'Botble lazy-loading guard changed.');
    $before = Botble\Shortcode\Compilers\ShortcodeCompiler::getIgnoredLazyLoading();
    Botble\Shortcode\Shortcode::ignoreLazyLoading(['hero-banner-with-booking-form']);
    check(in_array('hero-banner-with-booking-form', Botble\Shortcode\Compilers\ShortcodeCompiler::getIgnoredLazyLoading(), true), 'Facade call does not register the exclusion.');
    (new ReflectionProperty(Botble\Shortcode\Compilers\ShortcodeCompiler::class, 'ignoredLazyLoading'))->setValue(null, $before);
});

test('Every stylesheet styling the hero title H2 also styles the H1', function () use ($root): void {
    $selectors = ['.slider-content h2', '.slider-content h2 span', '.s-slider-content h2', '.slider-bg2 .slider-content h2', '.bw-hero-image-clip .slider-content.s-slider-content h2'];
    foreach (['public/themes/riorelax/css/theme.css', 'public/themes/riorelax/plugins/responsive.css',
        'platform/themes/riorelax/public/css/theme.css', 'platform/themes/riorelax/public/plugins/responsive.css',
        'platform/themes/riorelax/assets/sass/components/_slider.scss', 'platform/themes/riorelax/layouts/base.blade.php'] as $file) {
        preg_match_all('/([^{}]+)\{/', file_get_contents("$root/$file"), $rules);
        foreach ($rules[1] as $list) {
            $items = array_map('trim', explode(',', $list));
            foreach (array_intersect($items, $selectors) as $h2) {
                check(in_array(preg_replace('/ h2( span)?$/', ' h1$1', $h2), $items, true), "$file styles '$h2' without its H1.");
            }
        }
    }
});

// 3. Linked logo accessible name

$logoMarkup = function (string $file, string $pattern) use ($themePath): string {
    preg_match($pattern, file_get_contents("$themePath/$file"), $match);
    check(isset($match[0]), "Logo markup not found in $file.");
    return $match[0];
};

test('Linked header logos are named by the CMS site name, then the site title, then a Home label', function () use ($compileBlade, $themeStub, $logoMarkup, $app): void {
    $app['translator']->getLoader()->addMessages('en', 'theme', ['common' => ['home' => 'Home']], 'packages/theme');
    $app['translator']->setLoaded([]);
    $app->setLocale('en');
    // A private route collection: the shared runner router is not modified.
    $routes = new Illuminate\Routing\RouteCollection();
    $routes->add((new Illuminate\Routing\Route(['GET'], '/', fn () => null))->name('public.index'));
    $routes->refreshNameLookups();
    $previousUrl = $app['url'];
    $app->instance('url', new Illuminate\Routing\UrlGenerator($routes, Illuminate\Http\Request::create('https://hotel.example')));
    $templates = [
        $logoMarkup('partials/header.blade.php', '/<div class="logo">.*?<\/div>/s'),
        $logoMarkup('layouts/side-menu.blade.php', '/<div class="logo mb-100">.*?<\/div>/s'),
    ];
    try {
        foreach ($templates as $template) {
            $themeStub([], 'B&W SAHARA SKY HOTEL');
            $GLOBALS['seoThemeOptions'] = ['logo' => 'logo.png', 'site_name' => 'فندق B&W "Sahara" Sky'];
            check(str_contains($compileBlade($template, ['logo' => 'logo.png']), 'alt="فندق B&amp;W &quot;Sahara&quot; Sky"'), 'Site name not escaped once in alt.');
            $GLOBALS['seoThemeOptions'] = ['logo' => 'logo.png'];
            $html = $compileBlade($template, ['logo' => 'logo.png']);
            check(str_contains($html, 'alt="B&amp;W SAHARA SKY HOTEL"') && str_contains($html, '<a href="https://hotel.example">'), 'Site title fallback missing: ' . $html);
            $themeStub([], null);
            check(str_contains($compileBlade($template, ['logo' => 'logo.png']), 'alt="Home"'), 'Linked logo has no accessible name.');
        }
    } finally {
        $GLOBALS['seoThemeOptions'] = [];
        $app->instance('url', $previousUrl);
    }
});

test('Unlinked footer logo stays decorative when no name is configured', function () use ($compileBlade, $themeStub, $logoMarkup): void {
    $template = $logoMarkup('widgets/contact-information/templates/frontend.blade.php', '/<img [^>]*>/');
    try {
        $GLOBALS['seoThemeOptions'] = ['logo' => 'logo.png'];
        $themeStub([], 'B&W SAHARA SKY HOTEL');
        check(str_contains($compileBlade($template, ['logo' => 'logo.png']), 'alt="B&amp;W SAHARA SKY HOTEL"'), 'Footer logo ignores the site title.');
        $themeStub([], null);
        check(str_contains($compileBlade($template, ['logo' => 'logo.png']), 'alt=""'), 'Footer logo invented a label.');
    } finally {
        $GLOBALS['seoThemeOptions'] = [];
    }
});

// 4. Generic schema consistency

test('JSON-LD text decodes CMS entities once and keeps Unicode', function (): void {
    check(JsonLd::text('B&amp;W SAHARA SKY HOTEL') === 'B&W SAHARA SKY HOTEL', 'Entity not decoded.');
    check(JsonLd::text('B&W Sahara Sky 酒店') === 'B&W Sahara Sky 酒店', 'Plain text changed.');
    check(JsonLd::text('<b>فندق</b>  B&amp;W') === 'فندق B&W', 'Tags or whitespace kept.');
    check(JsonLd::text('B&amp;amp;W') === 'B&amp;W', 'Decoded more than once.');
    foreach ([null, '', '  ', '<p></p>', []] as $empty) {
        check(JsonLd::text($empty) === null, 'Empty value kept: ' . json_encode($empty));
    }
});

test('WebSite and Organization nodes have stable ids and the literal brand name', function (): void {
    $website = JsonLd::website('https://hotel.example', 'B&amp;W SAHARA SKY HOTEL');
    $organization = JsonLd::organization('https://hotel.example/', 'B&amp;W SAHARA SKY HOTEL', 'https://hotel.example/storage/logo.png');
    check($website === ['@context' => 'https://schema.org', '@type' => 'WebSite', '@id' => 'https://hotel.example/#website', 'name' => 'B&W SAHARA SKY HOTEL', 'url' => 'https://hotel.example'], 'WebSite: ' . json_encode($website));
    check($organization['@id'] === 'https://hotel.example/#organization' && $organization['name'] === 'B&W SAHARA SKY HOTEL', 'Organization id/name.');
    check($organization['url'] === 'https://hotel.example' && $organization['logo'] === ['@type' => 'ImageObject', 'url' => 'https://hotel.example/storage/logo.png'], 'Organization url/logo.');
    check(array_keys(JsonLd::organization('https://hotel.example', null, null)) === ['@context', '@type', '@id', 'url'], 'Missing settings produced empty properties.');
    $json = JsonLd::encode(JsonLd::website('https://hotel.example', 'Sky</script><script>alert(1)</script> 酒店'));
    check(! str_contains($json, '</script') && str_contains($json, '酒店') && str_contains($json, 'https://hotel.example'), 'Unsafe or escaped JSON: ' . $json);
    check(json_decode($json, true)['name'] === 'Skyalert(1) 酒店', 'JSON-LD does not parse back to plain text.');
});

test('Organization merges into the verified Hotel entity instead of competing with it', function (): void {
    $organization = JsonLd::organization('https://hotel.example', 'B&W SAHARA SKY HOTEL', null);
    $inner = HotelSchema::organization($organization, 'https://hotel.example', 'B&amp;W Sahara Sky Hotel', false);
    check($inner['@id'] === 'https://hotel.example/#hotel' && $inner['name'] === 'B&W Sahara Sky Hotel' && $inner['@type'] === 'Organization', 'Inner page entity: ' . json_encode($inner));
    check(HotelSchema::organization($organization, 'https://hotel.example', 'B&W Sahara Sky Hotel', true) === null, 'Homepage keeps a second business entity next to Hotel.');
    foreach ([null, '', 'Hotel Riorelax'] as $unverified) {
        check(HotelSchema::organization($organization, 'https://hotel.example', $unverified, true) === $organization, 'Organization dropped without a verified hotel name.');
    }
    check(HotelSchema::organization(null, 'https://hotel.example', 'B&W Sahara Sky Hotel', false) === null, 'Organization created from nothing.');
    check(HotelSchema::hotel('https://hotel.example', 'B&W')['@id'] === $inner['@id'], 'Hotel and Organization ids differ.');
});

test('The hotel entity exposes one @id and one url on EN, AR and ZH pages', function (): void {
    // url('') is the unprefixed site root in every locale; the localized homepage is only reachable through hreflang.
    $names = ['en' => 'B&W Sahara Sky Hotel', 'ar' => 'فندق B&W Sahara Sky', 'zh' => 'B&W Sahara Sky 酒店'];
    $nodes = [];
    foreach ($names as $locale => $name) {
        $organization = JsonLd::organization('https://hotel.example', 'B&amp;W SAHARA SKY HOTEL', 'https://hotel.example/storage/logo.png');
        $nodes[] = HotelSchema::hotel('https://hotel.example', $name, [], 'https://hotel.example/storage/logo.png');
        $nodes[] = HotelSchema::organization($organization, 'https://hotel.example', $name, false);
    }
    check(array_unique(array_column($nodes, '@id')) === ['https://hotel.example/#hotel'], 'Hotel entity id differs: ' . json_encode(array_column($nodes, '@id')));
    check(array_unique(array_column($nodes, 'url')) === ['https://hotel.example'], 'Hotel entity url differs: ' . json_encode(array_column($nodes, 'url')));
    check($nodes[3]['name'] === 'فندق B&W Sahara Sky' && $nodes[3]['logo']['url'] === 'https://hotel.example/storage/logo.png', 'Existing Organization data lost in the merge.');
});

test('Without a site_name there is no Hotel node and the Organization keeps its own identity', function (): void {
    foreach ([null, '', '  ', 'Hotel Riorelax'] as $siteName) {
        check(HotelSchema::hotel('https://hotel.example', $siteName) === null, 'Hotel node emitted without a verified name.');
        $organization = HotelSchema::organization(JsonLd::organization('https://hotel.example/', 'B&amp;W SAHARA SKY HOTEL', null), 'https://hotel.example', $siteName, false);
        check($organization['@id'] === 'https://hotel.example/#organization' && $organization['url'] === 'https://hotel.example' && $organization['name'] === 'B&W SAHARA SKY HOTEL', 'Generic Organization changed: ' . json_encode($organization));
        check(HotelSchema::organization($organization, 'https://hotel.example', $siteName, true) === $organization, 'Homepage Organization dropped without a Hotel node.');
    }
});

test('Every generic schema producer encodes once with script-safe flags', function () use ($root): void {
    $header = method_source(Botble\Theme\Theme::class, 'header');
    check(str_contains($header, 'JsonLd::breadcrumbList(array_values($this->breadcrumb->getCrumbs()))') && str_contains($header, 'JsonLd::encode($breadcrumbSchema)'), 'BreadcrumbList is not built and encoded by JsonLd.');
    check(str_contains($header, "JsonLd::encode(JsonLd::website(url('')"), 'WebSite is not built by JsonLd.');
    $page = file_get_contents("$root/platform/packages/page/src/Providers/HookServiceProvider.php");
    check(str_contains($page, 'JsonLd::organization(') && str_contains($page, "apply_filters('page_organization_schema', \$schema, \$page)") && str_contains($page, 'JsonLd::encode($schema)'), 'Organization producer not normalized.');
    $blog = file_get_contents("$root/platform/plugins/blog/src/Providers/HookServiceProvider.php");
    check(str_contains($blog, "PostSchema::make(\$post, setting('blog_post_schema_type', 'NewsArticle'))") && str_contains($blog, 'JsonLd::encode($schema)'), 'Article schema not built by PostSchema.');
    check(! str_contains($blog, "'@type' => 'Person'"), 'Article author still built inline in the provider.');
    $hotel = file_get_contents("$root/platform/plugins/hotel/src/Providers/HotelServiceProvider.php");
    check(str_contains($hotel, "add_filter('page_organization_schema'"), 'Hotel entity does not reconcile the page Organization.');
    foreach ([$header, $page, $blog] as $source) {
        check(! str_contains($source, 'JSON_UNESCAPED_UNICODE)'), 'A schema producer still uses raw json_encode.');
    }
});

// 5. Locale aliases: no code change. Live alternates are verified in the audit evidence; this guards the code side only.

test('Locale alias redirect stays 302 and the hreflang partial does not hard-code the /en prefix', function () use ($root): void {
    $filter = file_get_contents("$root/platform/plugins/language/src/Http/Middleware/LocalizationRedirectFilter.php");
    check(str_contains($filter, 'new RedirectResponse($redirection, 302'), 'Alias status changed without a confirmed permanent-alias policy.');
    $partial = file_get_contents("$root/platform/plugins/language/resources/views/partials/hreflang.blade.php");
    check(! preg_match('#/en/|/en"#', $partial), 'Hreflang partial hard-codes the default prefix.');
});

// 6. Empty archives

test('Empty tag and category archives are noindex, follow; populated ones are untouched', function (): void {
    $robots = new class { public array $meta = []; public function addMeta($name, $content) { $this->meta[$name] = $content; return $this; } };
    Botble\SeoHelper\Facades\SeoHelper::swap(new class($robots) { public function __construct(public object $robots) {} public function meta() { return $this->robots; } });
    $noindex = new ReflectionMethod(BlogService::class, 'noindexEmptyArchive');
    try {
        $noindex->invoke(new BlogService(), new LengthAwarePaginator([], 0, 12));
        check(($robots->meta['robots'] ?? null) === 'noindex, follow', 'Empty paginated archive is indexable.');
        $robots->meta = [];
        $noindex->invoke(new BlogService(), new LengthAwarePaginator(['post'], 1, 12));
        check($robots->meta === [], 'Populated archive was noindexed.');
        $noindex->invoke(new BlogService(), collect());
        check(($robots->meta['robots'] ?? null) === 'noindex, follow', 'Empty collection archive is indexable.');
    } finally {
        Botble\SeoHelper\Facades\SeoHelper::clearResolvedInstance(Botble\SeoHelper\SeoHelper::class);
    }
    $source = method_source(BlogService::class, 'handleFrontRoutes');
    foreach (['CATEGORY', 'TAG'] as $screen) {
        $action = strpos($source, "do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, {$screen}_MODULE_SCREEN_NAME");
        $call = strpos($source, '$this->noindexEmptyArchive($posts)', (int) $action);
        check($action !== false && $call !== false && $call - $action < 200, "$screen archive noindex does not run after the SEO meta hooks.");
    }
});

test('Empty archive message is not a second H1 next to the banner title', function () use ($themePath): void {
    $template = file_get_contents("$themePath/views/templates/posts.blade.php");
    check(! str_contains($template, '<h1') && str_contains($template, "<h2 class=\"text-center h1\">{{ __('Ops! No results found') }}</h2>"), 'Empty-state heading competes with the page H1.');
});

$db = $capsule->getConnection();
$db->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => (int) substr((string) $date, 0, 4), 1);
$db->getPdo()->sqliteCreateFunction('MONTH', fn ($date) => (int) substr((string) $date, 5, 2), 1);
foreach (['posts (id INTEGER PRIMARY KEY, name TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
    'tags (id INTEGER PRIMARY KEY, name TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
    'categories (id INTEGER PRIMARY KEY, name TEXT, status TEXT, parent_id INTEGER, created_at TEXT, updated_at TEXT)',
    'post_tags (tag_id INTEGER, post_id INTEGER)', 'post_categories (category_id INTEGER, post_id INTEGER)'] as $table) {
    $db->statement('CREATE TABLE ' . $table);
}
$macros = Botble\Base\Facades\MacroableModels::getFacadeRoot();
foreach ([Post::class, Tag::class, Category::class] as $model) {
    $model::resolveRelationUsing('slugable', fn ($item) => $item->morphOne(Slug::class, 'reference'));
    $macros->addMacro($model, 'getUrlAttribute', function () {
        return 'https://hotel.example/' . $this->slugable->prefix . '/' . $this->slugable->key;
    });
}
$at = '2026-07-17 19:17:37';
$db->table('posts')->insert([
    ['id' => 1, 'name' => 'Starry night', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at],
    ['id' => 2, 'name' => 'Draft post', 'status' => 'draft', 'created_at' => $at, 'updated_at' => $at],
]);
$db->table('tags')->insert([
    ['id' => 1, 'name' => 'General', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at],
    ['id' => 2, 'name' => 'Desert', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at],
    ['id' => 3, 'name' => 'Draft only', 'status' => 'published', 'created_at' => $at, 'updated_at' => $at],
]);
$db->table('categories')->insert([
    ['id' => 1, 'name' => 'News', 'status' => 'published', 'parent_id' => 0, 'created_at' => $at, 'updated_at' => $at],
    ['id' => 2, 'name' => 'Empty', 'status' => 'published', 'parent_id' => 0, 'created_at' => $at, 'updated_at' => $at],
    ['id' => 3, 'name' => 'Parent', 'status' => 'published', 'parent_id' => 0, 'created_at' => $at, 'updated_at' => $at],
    ['id' => 4, 'name' => 'Child', 'status' => 'published', 'parent_id' => 3, 'created_at' => $at, 'updated_at' => $at],
    ['id' => 5, 'name' => 'Draft parent child', 'status' => 'published', 'parent_id' => 0, 'created_at' => $at, 'updated_at' => $at],
    ['id' => 6, 'name' => 'Draft child', 'status' => 'draft', 'parent_id' => 5, 'created_at' => $at, 'updated_at' => $at],
]);
$db->table('post_tags')->insert([['tag_id' => 2, 'post_id' => 1], ['tag_id' => 3, 'post_id' => 2]]);
$db->table('post_categories')->insert([['category_id' => 1, 'post_id' => 1], ['category_id' => 4, 'post_id' => 1], ['category_id' => 6, 'post_id' => 1]]);
foreach ([[Tag::class, [1 => 'general', 2 => 'desert', 3 => 'draft-only'], 'tag'],
    [Category::class, [1 => 'news', 2 => 'empty', 3 => 'parent', 4 => 'child', 5 => 'draft-parent-child', 6 => 'draft-child'], 'news']] as [$model, $keys, $prefix]) {
    foreach ($keys as $id => $key) {
        $db->table('slugs')->insert(['key' => $key, 'reference_type' => $model, 'reference_id' => $id, 'prefix' => $prefix]);
    }
}

$blogSitemap = function (?string $key): object {
    $recorder = new class {
        public array $urls = [];
        public array $sitemaps = [];
        public function add(string $url, $date = null, string $priority = '1.0', string $freq = 'daily'): self { $this->urls[] = $url; return $this; }
        public function addSitemap(string $loc, $date = null): self { $this->sitemaps[] = $loc; return $this; }
        public function route(?string $key = null): string { return "https://hotel.example/$key.xml"; }
        public function createPaginatedSitemaps(string $key, int $count, $date = null): void { $this->sitemaps[] = "https://hotel.example/$key.xml"; }
        public function extractPaginationDataByPattern(...$arguments): ?array { return null; }
    };
    SiteMapManager::swap($recorder);
    (new RenderingSiteMapListener())->handle(new RenderingSiteMapEvent($key));
    return $recorder;
};

test('Blog tag and category sitemaps list only archives that render published posts', function () use ($blogSitemap): void {
    check($blogSitemap('blog-tags')->urls === ['https://hotel.example/tag/desert'], 'Tag sitemap: ' . implode(', ', $blogSitemap('blog-tags')->urls));
    $categories = $blogSitemap('blog-categories')->urls;
    sort($categories);
    check($categories === ['https://hotel.example/news/child', 'https://hotel.example/news/news', 'https://hotel.example/news/parent'], 'Category sitemap: ' . implode(', ', $categories));
});

test('Blog sitemap index omits archive sitemaps that would be empty', function () use ($blogSitemap, $db): void {
    $index = $blogSitemap(null)->sitemaps;
    check(in_array('https://hotel.example/blog-tags.xml', $index, true) && in_array('https://hotel.example/blog-categories.xml', $index, true), 'Populated archive sitemaps missing: ' . implode(', ', $index));
    $db->table('post_tags')->where('tag_id', 2)->delete();
    try {
        check(! in_array('https://hotel.example/blog-tags.xml', $blogSitemap(null)->sitemaps, true), 'Index lists a tag sitemap with no populated tags.');
    } finally {
        $db->table('post_tags')->insert(['tag_id' => 2, 'post_id' => 1]);
    }
});

test('Category sitemap follows published posts through published child categories only', function () use ($blogSitemap, $db): void {
    $links = $db->table('post_categories')->get()->map(fn ($row) => (array) $row)->all();
    try {
        // Only the draft child (6) keeps a post: its published parent (5) and every other category are empty.
        $db->table('post_categories')->where('category_id', '!=', 6)->delete();
        check($blogSitemap('blog-categories')->urls === [], 'A draft child category populated its parent: ' . implode(', ', $blogSitemap('blog-categories')->urls));
        check(! in_array('https://hotel.example/blog-categories.xml', $blogSitemap(null)->sitemaps, true), 'Index lists a category sitemap with no populated categories.');
        // A published post in a published child makes the child and its parent indexable.
        $db->table('post_categories')->insert(['category_id' => 4, 'post_id' => 1]);
        $urls = $blogSitemap('blog-categories')->urls;
        sort($urls);
        check($urls === ['https://hotel.example/news/child', 'https://hotel.example/news/parent'], 'Child-populated parent missing: ' . implode(', ', $urls));
        check(in_array('https://hotel.example/blog-categories.xml', $blogSitemap(null)->sitemaps, true), 'Populated category sitemap missing from index.');
        // A draft post never populates an archive.
        $db->table('post_categories')->delete();
        $db->table('post_categories')->insert(['category_id' => 1, 'post_id' => 2]);
        check($blogSitemap('blog-categories')->urls === [], 'Draft post populated a category.');
    } finally {
        $db->table('post_categories')->delete();
        $db->table('post_categories')->insert($links);
    }
});

// Article schema (uses the Post URL macro registered with the blog fixtures above).

$article = function (array $attributes, ?object $author = null) use ($app): Post {
    $app->bound('date') || $app->instance('date', new Illuminate\Support\DateFactory());
    $post = (new Post())->forceFill($attributes + ['created_at' => '2026-07-17 19:17:37', 'updated_at' => '2026-07-17 19:17:39', 'author_type' => $author ? stdClass::class : '']);
    $post->setRelation('slugable', (new Slug())->forceFill(['key' => 'starry-night', 'prefix' => 'news']));
    $author && $post->setRelation('author', $author);
    return $post;
};

test('Article schema keeps published values and omits empty optional properties', function () use ($article, $themeStub): void {
    $themeStub([], 'B&amp;W SAHARA SKY HOTEL', 'logo.png');
    $schema = Botble\Blog\Supports\PostSchema::make($article(['name' => 'One in a Billion &amp; Starry Night', 'description' => '<p>Stars</p>', 'image' => 'night.jpg'], (object) ['name' => 'Admin zain Admin']), 'BlogPosting');
    check($schema['@type'] === 'BlogPosting' && $schema['mainEntityOfPage'] === ['@type' => 'WebPage', '@id' => 'https://hotel.example/news/starry-night'], 'Type/page changed.');
    check($schema['headline'] === 'One in a Billion & Starry Night' && $schema['description'] === 'Stars', 'Text not decoded once.');
    check($schema['image'] === ['@type' => 'ImageObject', 'url' => 'https://hotel.example/storage/night.jpg'], 'Image lost.');
    check($schema['publisher'] === ['@type' => 'Organization', 'name' => 'B&W SAHARA SKY HOTEL', 'logo' => ['@type' => 'ImageObject', 'url' => 'https://hotel.example/storage/logo.png']], 'Publisher: ' . json_encode($schema['publisher']));
    check($schema['datePublished'] === '2026-07-17T19:17:37+00:00' && $schema['dateModified'] === '2026-07-17T19:17:39+00:00', 'Dates lost.');
    check(json_decode(JsonLd::encode($schema), true) === $schema, 'Article JSON-LD does not round-trip.');

    $themeStub([], null, null);
    $empty = Botble\Blog\Supports\PostSchema::make($article(['name' => 'Untitled story', 'description' => '', 'image' => null]), 'Unknown');
    check($empty['@type'] === 'NewsArticle' && $empty['headline'] === 'Untitled story', 'Valid values removed or type not defaulted.');
    check(! array_key_exists('description', $empty) && ! array_key_exists('author', $empty) && ! array_key_exists('publisher', $empty), 'Empty optional property emitted: ' . json_encode($empty));
    check(! array_key_exists('image', $empty), 'Placeholder image used as article structured data.');
    check(! str_contains(JsonLd::encode($empty), 'null') && ! str_contains(JsonLd::encode($empty), '""'), 'Null or empty value serialized.');
});

test('Article author is a name only, never an unverified profile URL', function () use ($article, $themeStub): void {
    $themeStub([], 'B&W SAHARA SKY HOTEL');
    $schema = Botble\Blog\Supports\PostSchema::make($article(['name' => 'Story'], (object) ['name' => 'B&amp;W Team']));
    check($schema['author'] === ['@type' => 'Person', 'name' => 'B&W Team'], 'Author: ' . json_encode($schema['author'] ?? null));
    check(! array_key_exists('author', Botble\Blog\Supports\PostSchema::make($article(['name' => 'Story'], (object) ['name' => '  '])) ), 'Nameless author emitted.');
});

// Restore the shared runner state captured at the top of this file.
$app->setLocale($sharedState['locale']);
$app->instance('url', $sharedState['url']);
$GLOBALS['seoThemeOptions'] = $sharedState['options'];
foreach ($sharedState['facades'] as $facade => $previous) {
    if ($previous !== null) {
        $facade::swap($previous);
    } else {
        $facade::clearResolvedInstance((new ReflectionMethod($facade, 'getFacadeAccessor'))->invoke(null));
    }
}
