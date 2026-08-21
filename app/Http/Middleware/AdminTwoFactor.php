<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip 2FA check if already on the 2FA routes
        if ($request->routeIs('admin.2fa.form') || $request->routeIs('admin.2fa.verify') || $request->routeIs('admin.2fa.resend')) {
            return $next($request);
        }

        if ($request->user() && $request->user()->isAdmin()) {
            // Bypass 2FA in local and testing environments only
            if (app()->environment('local', 'testing')) {
                $request->session()->put('admin_2fa_verified', true);
                return $next($request);
            }

            if (!$request->session()->get('admin_2fa_verified')) {
                return redirect()->route('admin.2fa.form');
            }
        }

        return $next($request);
    }
}
