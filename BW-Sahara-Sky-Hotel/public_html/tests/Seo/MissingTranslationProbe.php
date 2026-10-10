<?php
if ($argc !== 6) { throw new RuntimeException('Usage: php MissingTranslationProbe.php TEST_AUTOLOAD TEST_APP SYNTHETIC_SQLITE PATH slug|all'); }
require $argv[1];
Illuminate\Support\Env::getRepository()->set('APP_RUNNING_IN_CONSOLE', 'false');
$root = $argv[2];
$fixture = tempnam(sys_get_temp_dir(), 'seo-regression-');
copy($argv[3], $fixture);
register_shutdown_function(function () use ($fixture) { @unlink($fixture); });
$app = require $root . '/bootstrap/app.php';
$app->useEnvironmentPath($root)->loadEnvironmentFrom('.env');
$app->useStoragePath($root . '/storage');
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    foreach ([$argv[4]] as $path) {
        $request = Illuminate\Http\Request::create('http://127.0.0.1:8765' . $path);
        $app->instance('request', $request);
        $kernel->bootstrap();
        if (config('database.default') !== 'sqlite' || ! $app->environment('testing')) { throw new RuntimeException('SQLite guard failed'); }
        config(['database.connections.sqlite.database' => $fixture]);
        $app['db']->purge();
        $app['db']->table('slugs_translations')->where('key', 'synthetic-room-zh')->delete();
        if ($argv[5] === 'all') {
            $app['db']->table('ht_rooms_translations')->where('lang_code', 'zh_CN')->delete();
        }
        // Test-only adapters for existing MySQL date expressions on the synthetic SQLite database.
        $pdo = $app['db']->connection()->getPdo();
        $pdo->sqliteCreateFunction('YEAR', fn ($value) => $value ? (int) substr($value, 0, 4) : null);
        $pdo->sqliteCreateFunction('MONTH', fn ($value) => $value ? (int) substr($value, 5, 2) : null);
        $response = $kernel->handle($request);
        $html = $response->getContent();
        unset($pdo);
        $app['db']->disconnect();
        $dom = new DOMDocument();
        @$dom->loadHTML($html ?: '<html></html>');
        $xpath = new DOMXPath($dom);
        $canonical = [];
        foreach ($xpath->query('//link[@rel="canonical"]') as $node) { $canonical[] = $node->getAttribute('href'); }
        $hreflang = [];
        foreach ($xpath->query('//link[@hreflang]') as $node) { $hreflang[$node->getAttribute('hreflang')] = $node->getAttribute('href'); }
        $schemas = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) { $schemas[] = json_decode($node->textContent, true, 512, JSON_THROW_ON_ERROR); }
        $links = [];
        foreach ($xpath->query('//a[@href]') as $node) { if (str_contains($node->getAttribute('href'), 'synthetic-room')) { $links[] = $node->getAttribute('href'); } }
        $robots = [];
        foreach ($xpath->query('//meta[@name="robots"]') as $node) { $robots[] = $node->getAttribute('content'); }
        echo json_encode(['path' => $path, 'status' => $response->getStatusCode(), 'location' => $response->headers->get('Location'), 'content_type' => $response->headers->get('Content-Type'), 'html_lang' => $dom->documentElement?->getAttribute('lang'), 'canonical' => $canonical, 'hreflang' => $hreflang, 'robots' => $robots, 'schemas' => $schemas, 'room_links' => array_values(array_unique($links))], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    }
} catch (Throwable $e) {
    echo json_encode(['bootstrap_error' => get_class($e), 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES) . "\n";
    exit(1);
}
