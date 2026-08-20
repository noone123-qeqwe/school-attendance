<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAuditor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && $user->admin_sub_role === 'auditor') {
            // Check if request is a state-modifying action (POST, PUT, PATCH, DELETE)
            if (in_array(strtolower($request->method()), ['post', 'put', 'patch', 'delete'])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action. Auditors have read-only access.',
                    ], 403);
                }

                return back()->with('error', 'Unauthorized action. Auditors have read-only access.');
            }
        }

        return $next($request);
    }
}
