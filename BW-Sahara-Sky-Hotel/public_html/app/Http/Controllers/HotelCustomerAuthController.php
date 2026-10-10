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
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $email = Str::lower(trim($data['email']));

        return app(HotelLoginLock::class)->run($email, function () use ($email): JsonResponse {

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

        try {
            Mail::raw(
                "Your B&W Sahara Sky verification code is {$code}\nرمز التحقق الخاص بك: {$code}\n\nIt expires in " . self::TTL_MINUTES . ' minutes. If you did not request it, ignore this email.',
                fn ($message) => $message->to($email)->subject('Your verification code · B&W Sahara Sky')
            );
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
        if (! $customer) {
            $name = trim($data['name'] ?? '') ?: Str::before($email, '@');
            $customer = new Customer();
            $customer->forceFill([
                'first_name' => Str::limit($name, 60, ''), 'last_name' => '-', 'email' => $email,
                'password' => Str::random(40), 'confirmed_at' => now(),
            ])->save();
        } elseif (! $customer->confirmed_at) {
            $customer->forceFill(['confirmed_at' => now()])->save();
        }

        $pair = $this->tokens->issue($customer, $request);

        return $this->session($pair, $request, ['authenticated' => true, 'email' => $customer->email]);
        });
    }

    /** Rotates the HttpOnly refresh cookie and returns a new 15-minute access token. */
    public function refresh(Request $request): JsonResponse
    {
        $token = (string) $request->cookie(HotelTokenService::COOKIE);
        if ($token === '') {
            return $this->reply(['message' => 'Sign in with your email code to continue.', 'code' => 'login_required'], 401);
        }
        $result = $this->tokens->refresh($token, $request);
        if (! $result['ok']) {
            return $this->reply(['message' => 'Your session ended. Sign in again.', 'code' => 'login_required'], 401)
                ->withCookie($this->tokens->cookie(null, null, $request));
        }
        $customer = Customer::query()->find($this->customerId($result['access_token']));

        return $this->session($result, $request, ['authenticated' => true, 'email' => $customer?->email]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokens->logout((string) $request->cookie(HotelTokenService::COOKIE) ?: null, $request->bearerToken());

        return $this->reply(['authenticated' => false])->withCookie($this->tokens->cookie(null, null, $request));
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

    private function customerId(string $access): ?int
    {
        $claims = json_decode((string) base64_decode(strtr(explode('.', $access)[1] ?? '', '-_', '+/')), true);

        return $claims['cid'] ?? null;
    }

    private function hash(string $email, string $code): string
    {
        return hash_hmac('sha256', $email . '|' . $code, (string) config('app.key'));
    }

    private function reply(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store');
    }
}
