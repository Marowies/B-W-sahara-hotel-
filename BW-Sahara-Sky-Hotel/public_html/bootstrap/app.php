<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([\App\Providers\AppServiceProvider::class])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Appended after trusted proxy handling; disabled unless explicitly configured.
        $middleware->append(\App\Http\Middleware\CanonicalHost::class);
        $middleware->append(\App\Http\Middleware\StaffEntry::class);
        // Closes password/URL sign-in routes and keeps signed-in pages out of shared caches.
        $middleware->append(\App\Http\Middleware\HardenCustomerAuth::class);
        $middleware->append(\App\Http\Middleware\LimitHotelTraffic::class);
        // Old CMS pages and unknown public addresses go to the static frontend's pages and 404.
        $middleware->append(\App\Http\Middleware\StaticFrontendGate::class);
        $middleware->append(\App\Http\Middleware\GuestPageCache::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
