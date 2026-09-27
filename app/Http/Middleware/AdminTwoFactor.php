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
        if (!$request->session()->get('admin_2fa_verified', false)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Admin verification is required.'], 403);
            }

            return redirect()->route('admin.2fa.form');
        }

        return $next($request);
    }
}
