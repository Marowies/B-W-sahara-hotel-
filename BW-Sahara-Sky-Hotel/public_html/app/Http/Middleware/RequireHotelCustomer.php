<?php

namespace App\Http\Middleware;

use App\Services\HotelTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the customer from the 15-minute Bearer access token.
 * Every booking-payment endpoint must use it as `RequireHotelCustomer::class` (required): no valid token, no payment.
 * Use `RequireHotelCustomer::class . ':optional'` to only identify the customer when a token is present.
 */
class RequireHotelCustomer
{
    public function __construct(private HotelTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $customer = $this->tokens->authenticate($request->bearerToken());
        if ($customer) {
            auth('customer')->setUser($customer);
        } elseif ($mode !== 'optional') {
            return response()->json(['message' => 'Sign in with your email code to continue.', 'code' => 'login_required'], 401)
                ->header('Cache-Control', 'no-store');
        }

        return $next($request);
    }
}
