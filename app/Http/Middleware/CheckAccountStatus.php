<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request to verify that the active user account is not disabled.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has been deactivated. Please contact the school administrator.',
                        'account_disabled' => true
                    ], 403);
                }

                return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact the school administrator.');
            }
        }

        return $next($request);
    }
}
