<?php

namespace App\Services;

use Botble\Hotel\Models\Customer;
use Botble\SocialLogin\Facades\SocialService;
use Botble\SocialLogin\Http\Controllers\SocialLoginController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HotelGoogleSession
{
    public const RETURN_KEY = 'hotel_google_return';

    public function __construct(private HotelTokenService $tokens)
    {
    }

    public static function enabled(): bool
    {
        return is_plugin_active('hotel') && is_plugin_active('social-login')
            && SocialService::setting('enable')
            && SocialService::getProviderEnabled('google')
            && (bool) SocialService::setting('google_app_id')
            && (bool) SocialService::setting('google_app_secret');
    }

    public function start(Request $request): Response
    {
        abort_unless(self::enabled(), 404);
        $request->session()->put(self::RETURN_KEY, self::returnPath($request->query('return_to')));
        if (auth('customer')->check()) {
            return $this->complete(auth('customer')->user(), $request);
        }
        $request->merge(['guard' => 'customer']);

        return app(SocialLoginController::class)->redirectToProvider('google', $request);
    }

    public function complete(Customer $customer, Request $request): Response
    {
        $returnTo = self::returnPath($request->session()->pull(self::RETURN_KEY));
        $pair = $this->tokens->issue($customer, $request);

        // Tokens never enter the URL or browser storage. The frontend refreshes
        // through its existing API to obtain the short-lived access token.
        return (new \Illuminate\Http\RedirectResponse($returnTo))
            ->withCookie($this->tokens->cookie($pair['refresh_token'], $pair['family_expires_at'], $request))
            ->header('Cache-Control', 'no-store');
    }

    public static function returnPath(mixed $path): string
    {
        return is_string($path) && preg_match('~^/(?:login|register|booking/review|ar/(?:login|register|booking/review|dokhool|hesab-gadeed|takhteet-eqama/moragaa)|zh/(?:login|register|booking/review|denglu|zhuce|zhusu-jihua/queren))/$~D', $path)
            ? $path : '/login/';
    }
}
