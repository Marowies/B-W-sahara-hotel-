<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireHotelBookingSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! \Illuminate\Support\Facades\Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please sign in before booking.', 'code' => 'login_required'], 401)->header('Cache-Control', 'no-store');
            }
            // The public sign-in page in the visitor's language, when the active theme provides it.
            return redirect(function_exists('riorelax_guest_site_url') ? riorelax_guest_site_url('login') : '/login/')->header('Cache-Control', 'no-store');
        }
        return $next($request);
    }
}
