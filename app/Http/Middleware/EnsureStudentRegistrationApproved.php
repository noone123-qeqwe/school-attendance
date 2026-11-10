<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentRegistrationApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user?->isStudent() || !$user->student_registration_pending) {
            return $next($request);
        }

        if ($request->routeIs('student.pending-approval', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => 'STUDENT_REGISTRATION_PENDING',
                'message' => 'Your school is reviewing your student registration.',
            ], 403);
        }

        return redirect()->route('student.pending-approval');
    }
}
