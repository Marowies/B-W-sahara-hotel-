<?php

namespace App\Http\Middleware;

use App\Services\HotelTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The email code (and Google) is the only way a guest signs in, so everything that could sign someone in from a URL or a
 * password is closed, and every response that depends on who is signed in is kept out of shared caches.
 *
 * Closed (404): Botble's password login/registration/reset, the emailed "confirm" link (a GET that logs in whoever opens it)
 * and the mobile API sign-in endpoints.
 */
class HardenCustomerAuth
{
    /** @var list<string> Any method. */
    private const CLOSED = ['register/confirm', 'password', 'customer/change-password', 'api/v1/login', 'api/v1/register',
        'api/v1/password', 'api/v1/auth', 'api/v1/email/check', 'api/v1/me', 'api/v1/logout'];

    /** @var list<string> Closed for POST only; the GET pages are redirected to the static frontend. */
    private const CLOSED_POST = ['login', 'register'];

    /** @var list<string> Personalised or session-setting paths: never stored by a browser, proxy or CDN. */
    private const PRIVATE = ['login', 'register', 'customer', 'booking', 'auth', 'api/hotel/auth', 'api/hotel/session', 'api/hotel/checkout'];

    public function __construct(private HotelTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $path = $this->path($request);

        if ($this->matches($path, self::CLOSED) || ($request->isMethod('POST') && $this->matches($path, self::CLOSED_POST))) {
            return $this->hidden();
        }

        $response = $next($request);

        // The account menu's "Sign out" link reaches this URL when JavaScript does not intercept it. Closing only the page
        // session would let the refresh cookie sign the visitor straight back in, so the token family goes too.
        if ($path === 'customer/logout') {
            $this->tokens->logout((string) $request->cookie(HotelTokenService::COOKIE) ?: null, $request->bearerToken());
            $response->headers->setCookie($this->tokens->cookie(null, null, $request));
        }

        if ($this->matches($path, self::PRIVATE)) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            $response->headers->set('Vary', trim($response->headers->get('Vary', '') . ', Cookie, Authorization', ', '));
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    private function path(Request $request): string
    {
        $path = rawurldecode((string) parse_url($request->getRequestUri(), PHP_URL_PATH));
        $path = preg_replace('~^/index\.php(?=/|$)~i', '', $path) ?: '/';
        $path = strtolower(trim(preg_replace('~/+~', '/', $path), '/'));
        // Localised guest URLs such as /ar/login are the same pages.
        return preg_replace('~^(?:ar|zh|en)/~', '', $path) ?: $path;
    }

    /** @param list<string> $prefixes */
    private function matches(string $path, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function hidden(): Response
    {
        return new Response('Not Found', 404, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
