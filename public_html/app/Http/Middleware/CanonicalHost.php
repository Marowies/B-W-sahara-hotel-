<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = config('canonical-host', []);
        $origin = rtrim((string) ($settings['origin'] ?? ''), '/');
        $url = parse_url($origin);

        // Fail closed: no inferred host, credentials, path or production target on staging.
        if (! ($settings['enabled'] ?? false) || ! is_array($url)
            || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
            || isset($url['user']) || isset($url['pass'])
            || ! filter_var($url['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || isset($url['query']) || isset($url['fragment'])
            || ! empty($url['path']) || str_starts_with(strtolower($url['host']), 'www.')
            || (app()->environment() !== 'production'
                && strtolower($url['host']) === ($settings['production_host'] ?? 'bwsaharaskyhotel.com'))) {
            return $next($request);
        }

        // Compare hosts only, never forwarded scheme: TLS termination cannot cause a loop.
        // Request::getHost respects only Laravel's configured trusted proxies.
        if (strtolower($request->getHost()) !== 'www.' . strtolower($url['host'])
            || ! in_array($request->getMethod(), ['GET', 'HEAD'], true)
            || $request->headers->has('Cookie') || $request->cookies->count() > 0
            || $request->headers->has('Authorization')
            || $request->headers->has('X-XSRF-TOKEN')) {
            return $next($request);
        }

        foreach (explode('/', strtolower(trim(rawurldecode($request->getPathInfo()), '/'))) as $segment) {
            if (in_array($segment, $settings['excluded_paths'] ?? [], true)) {
                return $next($request);
            }
        }
        foreach ($request->query->keys() as $key) {
            if (in_array(strtolower($key), $settings['sensitive_query_keys'] ?? [], true)) {
                return $next($request);
            }
        }

        $uri = $request->getRequestUri();
        if (! str_starts_with($uri, '/') || preg_match('/[\x00-\x20\x7f]/', $origin . $uri)) {
            return $next($request);
        }
        $status = ($settings['status'] ?? 302) === 301
            && ($settings['permanent_confirmed'] ?? false) ? 301 : 302;

        return new RedirectResponse($origin . $uri, $status, [
            // Browsers/CDNs must not retain a temporary rollout decision.
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
