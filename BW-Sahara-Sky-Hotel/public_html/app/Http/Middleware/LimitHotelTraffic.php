<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LimitHotelTraffic
{
    public function handle(Request $request, Closure $next)
    {
        // IP is resolved by Laravel's trusted-proxy configuration, never an arbitrary header.
        $key = 'hotel-traffic:' . hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 120)) {
            $wait = RateLimiter::availableIn($key);
            $headers = ['Retry-After' => $wait, 'Cache-Control' => 'no-store'];
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Too many requests. Please wait and try again.', 'retry_after' => $wait], 429, $headers);
            }
            return response('<!doctype html><html><meta name="viewport" content="width=device-width,initial-scale=1"><title>Please slow down | B&W Sahara Sky</title><body style="background:#eee3d3;color:#60452f;font-family:system-ui;padding:10vh 8vw"><h1>A little pause.</h1><p>Please wait a minute before trying again.</p></body></html>', 429, $headers);
        }
        RateLimiter::hit($key, 60);
        return $next($request);
    }
}
