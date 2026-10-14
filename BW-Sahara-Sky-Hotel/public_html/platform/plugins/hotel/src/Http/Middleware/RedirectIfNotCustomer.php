<?php

namespace Botble\Hotel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotCustomer
{
    public function handle(Request $request, Closure $next, string $guard = 'customer')
    {
        if (! Auth::guard($guard)->check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }

            // The sign-in page restores the session from the refresh cookie and returns to this account page.
            $return = $request->isMethod('GET') && $request->is('customer/*') ? ['next' => $request->getRequestUri()] : [];
            // The public sign-in page in the visitor's language, when the active theme provides it.
            $login = function_exists('riorelax_guest_site_url') ? riorelax_guest_site_url('login') : null;

            return redirect()->guest($login
                ? $login . ($return ? '?' . http_build_query($return) : '')
                : route('customer.login', $return));
        }

        $customer = Auth::guard($guard)->user();

        return $next($request);
    }
}
