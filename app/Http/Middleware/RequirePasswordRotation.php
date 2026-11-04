<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordRotation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->ensurePasswordChangeRequired()) {
            return response()->json([
                'status' => 'error',
                'code' => 'password_change_required',
                'password_change_required' => true,
                'message' => 'Reset your password before using this account. Use the password recovery flow to continue.',
            ], 403);
        }

        return $next($request);
    }
}
