<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffEntry
{
    public function handle(Request $request, Closure $next): Response
    {
        $prefix = trim((string) config('core.base.general.admin_dir', 'admin'), '/');
        $path = strtolower(trim(rawurldecode($request->getPathInfo()), '/'));
        $path = preg_replace('~^index\.php/~', '', $path);
        // Retire the old entry only when the CMS is configured with a replacement.
        if ($prefix !== 'admin' && ($path === 'admin' || str_starts_with($path, 'admin/') || $path === 'login/admin')) {
            return new Response('Not Found', 404, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);
        }

        $response = $next($request);
        if ($path === strtolower($prefix) || str_starts_with($path, strtolower($prefix) . '/')) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
