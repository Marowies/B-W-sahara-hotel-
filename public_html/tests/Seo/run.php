<?php

// Standalone SEO regressions: production SEO classes, hotel controllers and theme templates.
// No database, network, hotel data or production settings are used.
// php tests/Seo/run.php [optional read-only dependency autoload path]
$GLOBALS['seoFilters'] = [];
function apply_filters($name, $value, ...$arguments) {
    foreach ($GLOBALS['seoFilters'][$name] ?? [] as $callback) {
        $value = $callback($value, ...$arguments);
    }
    return $value;
}
function add_filter($name, $callback, ...$arguments): void { $GLOBALS['seoFilters'][$name][] = $callback; }
function do_action(...$arguments): void {}
function add_action(...$arguments): void {}
function is_plugin_active(string $name): bool { return false; }
function theme_option(string $key, $default = null) { return $GLOBALS['seoThemeOptions'][$key] ?? $default; }

$source = dirname(__DIR__, 2);
$autoload = $argv[1] ?? $source . '/vendor/autoload.php';
if (! is_file($autoload)) {
    fwrite(STDERR, "Install dependencies or supply an existing read-only vendor/autoload.php path.\n");
    exit(2);
}
$loader = require $autoload;
$loader->addPsr4('Botble\\Hotel\\', $source . '/platform/plugins/hotel/src', true);
$loader->addPsr4('Botble\\Language\\', $source . '/platform/plugins/language/src', true);
$loader->addPsr4('Botble\\SeoHelper\\', $source . '/platform/packages/seo-helper/src', true);
$loader->addPsr4('Botble\\Theme\\', $source . '/platform/packages/theme/src', true);
$loader->addPsr4('Botble\\Base\\', $source . '/platform/core/base/src', true);
$loader->addPsr4('Botble\\Blog\\', $source . '/platform/plugins/blog/src', true);
$loader->addPsr4('Botble\\CookieConsent\\', $source . '/platform/plugins/cookie-consent/src', true);
require_once $source . '/platform/core/base/helpers/constants.php';
require_once $source . '/platform/plugins/hotel/helpers/constants.php';

$app = new Illuminate\Foundation\Application($source);
$app->instance('config', new Illuminate\Config\Repository([
    'app' => ['locale' => 'en'],
    'packages' => ['seo-helper' => ['general' => ['misc' => ['default' => []]]]],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
Illuminate\Container\Container::setInstance($app);
$app->instance('events', new Illuminate\Events\Dispatcher($app));
$app->instance('translator', new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader(), 'en'));
$app->instance(Botble\Base\Supports\MacroableModels::class, new class {
    public function modelHasMacro(...$arguments) { return false; }
});
Botble\Base\Facades\BaseHelper::swap(new class {
    public function html($value) { return new Illuminate\Support\HtmlString(e((string) $value)); }
    public function clean($value) { return $value; }
});

$results = [];
function test(string $name, Closure $callback): void {
    global $results;
    try { $callback(); $results[$name] = 'PASS'; }
    catch (Throwable $exception) { $results[$name] = 'FAIL: ' . $exception->getMessage(); }
}
function check(bool $condition, string $message): void {
    if (! $condition) { throw new RuntimeException($message); }
}
// Source of one method, for contract checks on controllers that need the full CMS to execute.
function method_source(string $class, string $method): string {
    $reflection = new ReflectionMethod($class, $method);
    $lines = file($reflection->getFileName());
    return implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
}

require __DIR__ . '/IndexabilityCases.php';
require __DIR__ . '/TechnicalCases.php';
require __DIR__ . '/HreflangCases.php';
require __DIR__ . '/SitemapCases.php';
require __DIR__ . '/OnPageCases.php';
require __DIR__ . '/StructuredDataCases.php';
require __DIR__ . '/ImageCases.php';
require __DIR__ . '/SafeGapCases.php';
require __DIR__ . '/ListingTemplateCases.php';
require __DIR__ . '/ImageSeoCases.php';

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
exit(count(array_filter($results, fn ($result) => str_starts_with($result, 'FAIL'))) ? 1 : 0);
