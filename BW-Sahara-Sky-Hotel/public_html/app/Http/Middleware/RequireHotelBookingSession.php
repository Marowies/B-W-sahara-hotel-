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
            return redirect('/login/')->header('Cache-Control', 'no-store');
        }
        return $next($request);
    }
}
