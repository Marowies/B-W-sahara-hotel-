<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = require dirname(__DIR__) . '/bootstrap/app.php';
        // Use a non-existent environment file so local or production .env is never loaded.
        $app->useEnvironmentPath($GLOBALS['hotelTestRuntime'])->loadEnvironmentFrom('.env.phpunit-disabled');
        $app->useStoragePath($GLOBALS['hotelTestRuntime'] . '/storage');
        if (preg_match('/^[A-Za-z]:/', $GLOBALS['hotelTestRuntime'])) {
            $app->addAbsoluteCachePathPrefix(substr($GLOBALS['hotelTestRuntime'], 0, 2));
        }
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function () use ($app): void {
            if (! $app->environment('testing') || config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
                throw new \RuntimeException('Unsafe PHPUnit environment before providers boot.');
            }
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        if (! $app->environment('testing') || config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Unsafe PHPUnit environment.');
        }
        \Illuminate\Support\Facades\Http::preventStrayRequests();

        return $app;
    }
}
