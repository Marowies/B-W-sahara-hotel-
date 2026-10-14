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
    public const PENDING_KEY = 'hotel_google_otp';

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
        // Google identifies the mailbox; only the emailed OTP may create a session.
        $this->tokens->logout($request->cookie(HotelTokenService::COOKIE), $request->bearerToken());
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        $result = app(\App\Http\Controllers\HotelCustomerAuthController::class)->requestCodeForEmail($customer->email);
        $data = $result->getData(true);
        $request->session()->put(self::PENDING_KEY, [
            'email' => $customer->email, 'pending' => $result->getStatusCode() === 200,
            'status' => $result->getStatusCode(), 'resend_in' => $data['resend_in'] ?? 60,
            'resend_at' => now()->addSeconds($data['resend_in'] ?? $data['retry_after'] ?? 60)->timestamp,
            'retry_after' => $data['retry_after'] ?? null,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        // Neither codes, email addresses nor tokens enter the return URL.
        return (new \Illuminate\Http\RedirectResponse($returnTo))
            ->withCookie($this->tokens->cookie(null, null, $request))
            ->header('Cache-Control', 'no-store');
    }

    public function pending(Request $request): Response
    {
        $pending = $request->session()->get(self::PENDING_KEY);
        if (! $pending || $pending['expires_at'] <= now()->timestamp) {
            $request->session()->forget(self::PENDING_KEY);
            $pending = ['pending' => false];
        } else {
            $pending['resend_in'] = max(0, ($pending['resend_at'] ?? now()->timestamp) - now()->timestamp);
            if ($pending['retry_after'] ?? null) {
                $pending['retry_after'] = $pending['resend_in'];
            }
            unset($pending['resend_at'], $pending['expires_at']);
        }

        return (new \Illuminate\Http\JsonResponse($pending))->header('Cache-Control', 'no-store');
    }

    public static function returnPath(mixed $path): string
    {
        if (! is_string($path)) {
            return '/login/';
        }
        [$base, $query] = array_pad(explode('?', $path, 2), 2, null);
        if (! preg_match('~^/(?:login|register|booking/review|ar/(?:login|register|booking/review|dokhool|hesab-gadeed|takhteet-eqama/moragaa)|zh/(?:login|register|booking/review|denglu|zhuce|zhusu-jihua/queren))/$~D', $base)) {
            return '/login/';
        }
        if ($query === null) {
            return $base;
        }
        // A sign-in page may carry one account page to continue to (sent there by RedirectIfNotCustomer); nothing else.
        parse_str($query, $params);
        $next = $params['next'] ?? null;

        return ! str_contains($base, 'booking/') && count($params) === 1 && is_string($next)
            && preg_match('~^/customer/[a-z0-9/-]*$~D', $next)
            ? $base . '?' . http_build_query(['next' => $next]) : $base;
    }
}
