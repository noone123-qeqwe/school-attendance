<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            // Allow them to visit the password reset form and submission route
            if (!$request->routeIs('password.change.form') && !$request->routeIs('password.change.submit') && !$request->routeIs('logout')) {
                return redirect()->route('password.change.form');
            }
        }

        return $next($request);
    }
}
