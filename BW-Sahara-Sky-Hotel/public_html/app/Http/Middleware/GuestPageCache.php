<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestPageCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->is('customer', 'customer/*', 'booking', 'booking/*')) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
