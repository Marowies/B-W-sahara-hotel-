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
        $post = $request->isMethod('POST');

        // Laravel may route on a different path than REQUEST_URI shows (/public/register, /xx/login), so every plausible
        // reading of the URL is checked, not only the first.
        foreach ($this->candidates($request, $path) as $candidate) {
            if ($this->matches($candidate, self::CLOSED) || ($post && $this->matches($candidate, self::CLOSED_POST)) || $this->isForeignSocial($candidate)) {
                return $this->hidden();
            }
        }

        // There is no guest sign-in any more: the sign-in, sign-up and account pages (and the post-payment pages of the old
        // internal checkout) are not reachable, so page requests go to the home page in the visitor's language.
        if (in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            $raw = strtolower(trim((string) preg_replace('~/+~', '/', rawurldecode((string) parse_url($request->getRequestUri(), PHP_URL_PATH))), '/'));
            $raw = preg_replace('~^index\.php(?=/|$)/?~', '', $raw);
            if (preg_match('~^(ar|zh)/(?:dokhool|hesab-gadeed|esteeadet-kalemet-moroor|denglu|zhuce|zhaohui-mima)$~', $raw, $m)) {
                return $this->home('/' . $m[1] . '/');
            }
            if (preg_match('~^(ar/takhteet-eqama|zh/zhusu-jihua)/(?:moayena|yulan)$~', $raw, $m)) {
                return $this->home('/' . $m[1] . '/');
            }
            foreach ($this->candidates($request, $path) as $candidate) {
                if (preg_match('~^(?:customers?|login|register|password|forgot-password|booking/confirmation)(?:/|$)~', $candidate) && ! str_starts_with($candidate, 'customer/ajax')) {
                    return $this->home('/');
                }
            }
        }

        $customerId = $path === 'customer/logout' ? auth('customer')->id() : null;

        $response = $next($request);

        // The account menu's "Sign out" link reaches this URL when JavaScript does not intercept it. Closing only the page
        // session would let the refresh cookie sign the visitor straight back in, so the token family goes too.
        if ($path === 'customer/logout') {
            $this->tokens->logout((string) $request->cookie(HotelTokenService::COOKIE) ?: null, $request->bearerToken());
            // The refresh cookie is scoped to /api/hotel/auth and is not sent here, so end all of this customer's sessions.
            if ($customerId) {
                $this->tokens->revokeAllFor($customerId, 'logout');
            }
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

    /** @return list<string> The path with a "public/" prefix or any language-code segment removed, plus Laravel's own path. */
    private function candidates(Request $request, string $path): array
    {
        $out = [$path, strtolower(trim(preg_replace('~/+~', '/', rawurldecode($request->getPathInfo())), '/'))];
        foreach ($out as $p) {
            $q = $p;
            for ($i = 0; $i < 3; $i++) {
                $q = preg_replace('~^(?:public|[a-z]{2}(?:[-_][a-z]{2,4})?)/~', '', $q, 1, $n);
                if (! $n) {
                    break;
                }
                $out[] = $q;
            }
        }

        return array_values(array_unique($out));
    }

    /** Only Google sign-in is allowed; Botble's other providers link accounts by an unverified email. */
    private function isForeignSocial(string $path): bool
    {
        return (bool) preg_match('~^auth/(?!(?:google|callback)(?:/|$))[^/]+~', $path) || (bool) preg_match('~^auth/callback/(?!google(?:/|$))~', $path);
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

    private function home(string $to): Response
    {
        return new \Symfony\Component\HttpFoundation\RedirectResponse($to, 302, ['Cache-Control' => 'private, no-store']);
    }

    private function hidden(): Response
    {
        return new Response('Not Found', 404, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
