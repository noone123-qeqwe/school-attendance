<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminApiTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        $abilities = $token?->abilities ?? [];

        // Wildcard abilities on older tokens do not establish OTP verification.
        if (!in_array('admin-2fa-verified', $abilities, true)) {
            return response()->json(['message' => 'Admin verification is required.'], 403);
        }

        return $next($request);
    }
}
