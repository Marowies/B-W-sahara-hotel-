<?php

namespace App\Http\Controllers;

use App\Services\HotelTokenService;
use App\Services\HotelLoginLock;
use Botble\Hotel\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Passwordless customer sign-in: a 6-digit code is emailed, then exchanged for a customer session (Booking.com style).
 */
class HotelCustomerAuthController extends Controller
{
    private const TTL_MINUTES = 10;
    private const RESEND_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function __construct(private HotelTokenService $tokens)
    {
    }

    public function requestCode(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255'], 'locale' => ['nullable', 'in:en,ar,zh']]);
        $email = Str::lower(trim($data['email']));

        return $this->requestCodeForEmail($email, $data['locale'] ?? null);
    }

    /** Shared delivery path for validated email input and the verified Google mailbox. */
    public function requestCodeForEmail(string $email, ?string $locale = null): JsonResponse
    {
        $email = Str::lower(trim($email));
        $locale = \App\Support\HotelMailCopy::locale($locale ?? session('hotel_ui_locale'));

        return app(HotelLoginLock::class)->run($email, function () use ($email, $locale): JsonResponse {

        // Resolving the mail manager applies the CMS email settings, which can
        // override .env. A testing sink must never be reported as real delivery.
        Mail::getFacadeRoot();
        if (! $this->deliversMail(config('mail.default'))) {
            return $this->reply(['message' => 'We could not send the code. Please try again shortly.', 'code' => 'mail_not_configured'], 503);
        }

        $last = DB::table('hotel_login_codes')->where('email', $email)->latest('id')->first();
        if ($last) {
            $retryAt = Carbon::parse($last->created_at)->addSeconds(self::RESEND_SECONDS);
            if ($retryAt->isFuture()) {
                return $this->reply(['message' => 'Please wait before requesting another code.', 'retry_after' => (int) now()->diffInSeconds($retryAt, true) + 1], 429);
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('hotel_login_codes')->where('email', $email)->delete();
        DB::table('hotel_login_codes')->insert([
            'email' => $email, 'code_hash' => $this->hash($email, $code), 'attempts' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES), 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Delivery is synchronous so a failure is reported to the visitor (and the code dropped) instead of being hidden.
        // The time the mail server takes is logged to tell a slow SMTP provider apart from a slow website.
        $started = microtime(true);
        try {
            Mail::to($email)->send(new \App\Mail\HotelLoginCodeMail($code, self::TTL_MINUTES, $locale));
            \Illuminate\Support\Facades\Log::info('hotel.login_code_mail', ['ms' => (int) ((microtime(true) - $started) * 1000)]);
        } catch (Throwable $e) {
            report($e);
            DB::table('hotel_login_codes')->where('email', $email)->delete();

            return $this->reply(['message' => 'We could not send the code. Please try again shortly.'], 503);
        }

        return $this->reply(['sent' => true, 'expires_in' => self::TTL_MINUTES * 60, 'resend_in' => self::RESEND_SECONDS]);
        });
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'code' => ['required', 'digits:6'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);
        $email = Str::lower(trim($data['email']));

        return app(HotelLoginLock::class)->run($email, function () use ($email, $data, $request): JsonResponse {

        $row = DB::table('hotel_login_codes')->where('email', $email)->latest('id')->first();
        if (! $row || Carbon::parse($row->expires_at)->isPast()) {
            return $this->reply(['message' => 'This code has expired. Request a new one.', 'code' => 'expired'], 422);
        }
        if ($row->attempts >= self::MAX_ATTEMPTS) {
            DB::table('hotel_login_codes')->where('id', $row->id)->delete();

            return $this->reply(['message' => 'Too many incorrect attempts. Request a new code.', 'code' => 'locked'], 429);
        }
        if (! hash_equals($row->code_hash, $this->hash($email, $data['code']))) {
            DB::table('hotel_login_codes')->where('id', $row->id)->increment('attempts');

            return $this->reply(['message' => 'The code is incorrect.', 'code' => 'invalid'], 422);
        }

        DB::table('hotel_login_codes')->where('email', $email)->delete();

        $customer = Customer::query()->where('email', $email)->first();
        $isNew = ! $customer;
        if ($isNew) {
            // Until the guest tells us their name, the first part of the email address stands in for it.
            $customer = new Customer();
            $customer->forceFill([
                'first_name' => $this->defaultName($email), 'last_name' => '', 'email' => $email,
                'password' => Str::random(40), 'confirmed_at' => now(),
            ])->save();
        } elseif (! $customer->confirmed_at) {
            $customer->forceFill(['confirmed_at' => now()])->save();
        }

        $pair = $this->tokens->issue($customer, $request);

        // The existing hotel booking/account pages use the same customer guard.
        \Illuminate\Support\Facades\Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        $request->session()->forget(\App\Services\HotelGoogleSession::PENDING_KEY);

        return $this->session($pair, $request, ['authenticated' => true, 'email' => $customer->email] + ($isNew ? ['is_new' => true, 'default_name' => $customer->first_name] : []));
        });
    }

    /** Rotates the HttpOnly refresh cookie and returns a new 15-minute access token. */
    public function refresh(Request $request): JsonResponse
    {
        $token = (string) $request->cookie(HotelTokenService::COOKIE);
        if ($token === '') {
            // A visitor who never signed in on this device: an ordinary answer, not an error on every page load.
            return $this->reply(['authenticated' => false, 'code' => 'signed_out']);
        }
        $result = $this->tokens->refresh($token, $request);
        // The identity comes only from the rotated, server-validated token, never from request data.
        $customer = $result['ok'] ? $this->tokens->authenticate($result['access_token']) : null;
        if (! $customer) {
            // A rejected token (expired, revoked, replayed) also ends the account-page session on this device.
            \Illuminate\Support\Facades\Auth::guard('customer')->logout();

            return $this->reply(['message' => 'Your session ended. Sign in again.', 'code' => 'login_required'], 401)
                ->withCookie($this->tokens->cookie(null, null, $request));
        }
        $this->restorePageSession($customer, $request);

        return $this->session($result, $request, ['authenticated' => true, 'email' => $customer->email]);
    }

    /**
     * The Laravel session (account and booking pages) can expire long before the 30-day token family.
     * A valid refresh re-establishes it for the token's customer, as OTP verification does.
     */
    private function restorePageSession(Customer $customer, Request $request): void
    {
        $guard = \Illuminate\Support\Facades\Auth::guard('customer');
        if ((int) $guard->id() === (int) $customer->getKey()) {
            return;
        }
        if ($guard->check()) {
            $guard->logout();
        }
        $guard->login($customer);
        // New identity on this session: rotate the id against fixation. Unchanged sessions keep theirs for other tabs.
        $request->session()->regenerate();
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokens->logout((string) $request->cookie(HotelTokenService::COOKIE) ?: null, $request->bearerToken());
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        $request->session()->forget(\App\Services\HotelGoogleSession::RETURN_KEY);
        $request->session()->forget(\App\Services\HotelGoogleSession::PENDING_KEY);

        return $this->reply(['authenticated' => false])->withCookie($this->tokens->cookie(null, null, $request));
    }

    /** First sign-in: the guest confirms how we should address them. Signed-in customers only (RequireHotelCustomer). */
    public function saveName(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
        ]);
        $first = trim(strip_tags($data['first_name']));
        if ($first === '') {
            return $this->reply(['message' => 'Please enter your first name.', 'code' => 'name_required'], 422);
        }
        $customer = auth('customer')->user();
        $customer->forceFill(['first_name' => $first, 'last_name' => trim(strip_tags((string) ($data['last_name'] ?? '')))])->save();

        return $this->reply(['saved' => true, 'name' => $customer->name]);
    }

    /** "marwan.ewis22@example.com" -> "Marwan": the first word of the address, never the whole address. */
    private function defaultName(string $email): string
    {
        $words = preg_split('/[^\p{L}]+/u', Str::before($email, '@'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return Str::limit(Str::title($words[0] ?? 'Guest'), 60, '');
    }

    /** Reachable only through RequireHotelCustomer; the payment step calls it before starting. */
    public function checkoutEligibility(): JsonResponse
    {
        return $this->reply(['eligible' => true, 'email' => auth('customer')->user()->email]);
    }

    private function session(array $pair, Request $request, array $extra): JsonResponse
    {
        $response = $this->reply($extra + ['access_token' => $pair['access_token'], 'expires_in' => $pair['expires_in']]);
        if ($pair['refresh_token']) {
            $response->withCookie($this->tokens->cookie($pair['refresh_token'], $pair['family_expires_at'], $request));
        }

        return $response;
    }

    private function hash(string $email, string $code): string
    {
        return hash_hmac('sha256', $email . '|' . $code, (string) config('app.key'));
    }

    private function deliversMail(?string $name, array $visited = []): bool
    {
        if (! $name || in_array($name, $visited, true)) {
            return false;
        }
        $transport = config('mail.mailers.' . $name . '.transport');
        if (! $transport || in_array($transport, ['log', 'array'], true)) {
            return false;
        }
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = config('mail.mailers.' . $name . '.mailers', []);
            if (! is_array($children) || ! $children) {
                return false;
            }
            foreach ($children as $child) {
                if (! is_string($child) || ! $this->deliversMail($child, [...$visited, $name])) {
                    return false;
                }
            }
        }

        return true;
    }

    private function reply(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store');
    }
}
