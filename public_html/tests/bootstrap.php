<?php

// Test-only bootstrap: never read .env, shared caches, or a network database.
$root = dirname(__DIR__);
$testRuntime = sys_get_temp_dir() . '/hotel-phpunit-' . bin2hex(random_bytes(8));
foreach (['cache', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $directory) {
    mkdir($testRuntime . '/' . $directory, 0700, true);
}
$GLOBALS['hotelTestRuntime'] = $testRuntime;
$values = [
    'APP_BASE_PATH' => $root,
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'false',
    'APP_URL' => 'http://localhost',
    'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)),
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'DB_HOST' => '127.0.0.1',
    'DB_USERNAME' => '',
    'DB_PASSWORD' => '',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'TRACKING_MODE' => 'off',
    'APP_CONFIG_CACHE' => $testRuntime . '/cache/config.php',
    'APP_PACKAGES_CACHE' => $testRuntime . '/cache/packages.php',
    'APP_SERVICES_CACHE' => $testRuntime . '/cache/services.php',
    'APP_ROUTES_CACHE' => $testRuntime . '/cache/routes.php',
    'APP_EVENTS_CACHE' => $testRuntime . '/cache/events.php',
];
foreach ($values as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv($key . '=' . $value);
}
$loader = require $root . '/vendor/autoload.php';
// All CMS/plugin code comes from this checkout, even when dependencies are reused locally.
foreach (array_merge([$root . '/platform/core/composer.json'], glob($root . '/platform/packages/*/composer.json'), glob($root . '/platform/plugins/*/composer.json')) as $file) {
    $package = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    foreach ($package['autoload']['psr-4'] ?? [] as $prefix => $paths) {
        $loader->setPsr4($prefix, array_map(fn ($path) => dirname($file) . '/' . $path, (array) $paths));
    }
}
foreach (glob($root . '/platform/plugins/*/plugin.json') as $file) {
    $plugin = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if (isset($plugin['namespace'])) { $loader->setPsr4($plugin['namespace'], dirname($file) . '/src'); }
}
$map = [];
foreach ($loader->getClassMap() as $class => $file) {
    foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
        if (str_starts_with($class, $prefix) && (str_starts_with($prefix, 'Botble\\') || $prefix === 'Tests\\' || $prefix === 'App\\')) {
            foreach ($paths as $path) {
                $candidate = $path . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (is_file($candidate)) { $map[$class] = $candidate; break; }
            }
        }
    }
}
$loader->addClassMap($map);
