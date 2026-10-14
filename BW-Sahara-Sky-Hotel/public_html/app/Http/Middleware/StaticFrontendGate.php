<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Keeps visitors on the static frontend when it is deployed in public/.
 *
 * Apache serves the frontend's own pages directly; only addresses it does not know reach Laravel.
 * Without this gate those addresses fall through to the old CMS theme (old design, old 404).
 * Here they are answered with a 301 to the matching frontend page (old CMS URLs, aliases,
 * wrong case, ?lang=) or the frontend's localized 404 page. Admin, API, auth, account and
 * payment routes are never touched. Inactive when public/route-map.json is absent (no frontend build).
 */
class StaticFrontendGate
{
    /** First path segments that always belong to Laravel. */
    private const BACKEND = [
        'admin', 'api', 'auth', 'ajax', 'storage', 'vendor', 'themes', 'customer', 'customers', 'up',
        'payment', 'payments', 'paypal', 'stripe', 'razorpay', 'paystack', 'sslcommerz', 'mollie',
        'webhook', 'webhooks', 'callback', 'callbacks', 'checkout', 'ical', 'newsletter', 'captcha',
        'social-login', 'language', 'change-currency', 'media', 'filemanager', 'robots.txt',
    ];

    /** Route names of the old CMS theme's public pages, replaced by the static frontend. */
    private const THEME_ROUTES = [
        'public.index', 'public.single', 'public.search', 'public.galleries', 'public.rooms',
        'public.sitemap', 'public.sitemap.index',
    ];

    /** Old CMS slugs and common aliases => frontend route id. */
    private const ALIASES = [
        '' => 'home', 'home' => 'home', 'index' => 'home', 'index.html' => 'home', 'index.php' => 'home', 'search' => 'home',
        'rooms' => 'rooms', 'room' => 'rooms', 'accommodation' => 'rooms', 'domes' => 'rooms',
        'about' => 'about', 'about-us' => 'about', 'our-story' => 'about', 'story' => 'about',
        'contact' => 'contact', 'contact-us' => 'contact', 'contacts' => 'contact',
        'gallery' => 'gallery', 'galleries' => 'gallery', 'photos' => 'gallery',
        'blog' => 'blog', 'news' => 'blog', 'journal' => 'blog', 'posts' => 'blog',
        'faq' => 'faq', 'faqs' => 'faq',
        'services' => 'services', 'service' => 'services',
        'experiences' => 'experiences', 'experience' => 'experiences', 'activities' => 'experiences', 'tours' => 'experiences',
        'booking' => 'booking', 'book' => 'booking', 'reservation' => 'booking', 'reservations' => 'booking',
        'privacy' => 'privacy', 'privacy-policy' => 'privacy', 'cookie-policy' => 'privacy',
        'terms' => 'terms', 'term-and-conditions' => 'terms', 'terms-and-conditions' => 'terms', 'terms-of-service' => 'terms',
        'login' => 'login', 'signin' => 'login', 'sign-in' => 'login',
        'register' => 'register', 'signup' => 'register', 'sign-up' => 'register',
        'forgot-password' => 'forgot', 'password-reset' => 'forgot',
    ];

    private ?array $map = null;

    public function handle(Request $request, Closure $next): Response
    {
        $routes = $this->routes();
        if (! $routes || ! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $uri = $request->getRequestUri();
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        // /index.php/... is the front controller's own name; never a public address.
        $path = preg_replace('~^/index\.php(?=/|$)~i', '', $path) ?: '/';
        $decoded = rawurldecode($path);
        $segments = array_values(array_filter(explode('/', strtolower($decoded)), 'strlen'));
        $first = $segments[0] ?? '';
        if (in_array($first, [...self::BACKEND, strtolower((string) config('core.base.general.admin_dir', 'admin'))], true)) {
            return $next($request);
        }

        $locale = in_array($first, ['ar', 'zh'], true) ? $first : 'en';
        $lang = strtolower((string) $request->query('lang', ''));
        $hasLang = in_array($lang, ['en', 'ar', 'zh'], true);
        $themeOrMissing = $path !== parse_url($uri, PHP_URL_PATH) || $this->isThemeOrMissing($request);

        // Laravel's own pages (booking form, account, ...) keep their address, ?lang included.
        if ($themeOrMissing) {
            $target = $this->target($decoded, $segments, $hasLang ? $lang : $locale, $routes);
            if ($target !== null) {
                $query = $request->query();
                unset($query['lang']);
                $location = $target . ($query ? '?' . http_build_query($query) : '');
                if ($location !== $uri) {
                    return new RedirectResponse($location, 301, ['Cache-Control' => 'public, max-age=3600']);
                }
                // Already the frontend address (the web server passed it on): answer with that page.
                $page = public_path(ltrim($target, '/') . 'index.html');
                if (is_file($page)) {
                    return new Response((string) file_get_contents($page), 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-cache']);
                }
            }

            return $this->notFound($locale);
        }

        $response = $next($request);

        // Errors from other public routes (e.g. an unknown booking link) get the frontend's page, not the old theme's.
        if ($response->getStatusCode() === 404 && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $this->notFound($locale);
        }

        return $response;
    }

    /** The localized frontend address for an old, aliased or mistyped path; null when there is none. */
    public function target(string $decoded, array $segments, string $locale, array $routes): ?string
    {
        $column = array_search($locale, ['en', 'ar', 'zh'], true);
        $byPath = $routes['byPath'];

        // Exact frontend page apart from case, trailing slash, index.html or language.
        $normal = '/' . implode('/', $segments);
        $normal = preg_replace('~/index\.html$~', '', $normal);
        $normal = $normal === '/' ? '/' : $normal . '/';
        if (isset($byPath[$normal])) {
            return $routes['byId'][$byPath[$normal]][$column];
        }

        if (in_array($segments[0] ?? '', ['ar', 'zh', 'en'], true)) {
            array_shift($segments);
        }
        $id = self::ALIASES[$segments[0] ?? ''] ?? null;
        if ($id === null) {
            return null;
        }
        if ($id === 'rooms' && isset($segments[1])) {
            $id = $this->roomId($segments[1], $routes) ?? 'rooms';
        }

        return $routes['byId'][$id][$column] ?? null;
    }

    /** Old CMS room slugs such as b-w-sahara-sky-hotel-superior-room-double. */
    private function roomId(string $slug, array $routes): ?string
    {
        if (str_contains($slug, 'dome') || str_contains($slug, 'glass')) {
            return isset($routes['byId']['domes']) ? 'domes' : null;
        }
        preg_match('~(deluxe|superior|standard)~', $slug, $category);
        preg_match('~(single|double|triple)~', $slug, $type);
        $id = $category && $type ? 'room-' . $category[1] . '-' . $type[1] : null;

        return $id && isset($routes['byId'][$id]) ? $id : null;
    }

    private function isThemeOrMissing(Request $request): bool
    {
        try {
            $route = app('router')->getRoutes()->match($request);
        } catch (Throwable) {
            return true;
        }

        return in_array($route->getName(), self::THEME_ROUTES, true);
    }

    private function notFound(string $locale): Response
    {
        $file = public_path($locale === 'en' ? '404.html' : $locale . '/404.html');
        if (! is_file($file)) {
            $file = public_path('404.html');
        }

        return new Response(is_file($file) ? (string) file_get_contents($file) : 'Not found', 404, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /** @return array{byId: array<string, array{0:string,1:string,2:string}>, byPath: array<string,string>}|null */
    private function routes(): ?array
    {
        if ($this->map !== null) {
            return $this->map ?: null;
        }
        $this->map = [];
        $file = public_path('route-map.json');
        if (! config('frontend-gate.enabled', true) || ! is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);
        foreach (($data['routes'] ?? []) as $row) {
            // The design-review page is internal: it is not a public destination.
            if (! isset($row['id'], $row['en'], $row['ar'], $row['zh']) || in_array($row['id'], ['404', 'approval'], true)) {
                continue;
            }
            $this->map['byId'][$row['id']] = [$row['en'], $row['ar'], $row['zh']];
            foreach ([$row['en'], $row['ar'], $row['zh']] as $path) {
                $this->map['byPath'][strtolower(rawurldecode($path))] = $row['id'];
            }
        }

        return $this->map ?: null;
    }
}
