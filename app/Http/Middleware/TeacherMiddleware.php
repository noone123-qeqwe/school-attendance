<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('TeacherMiddleware: Start', [
            'url' => $request->url(),
            'auth_check' => auth()->check(),
            'session_id' => session()->getId(),
        ]);

        if (!auth()->check()) {
            \Log::warning('TeacherMiddleware: Not authenticated, redirecting to login');
            return redirect()->route('login')->with('error', 'Please log in to access this area.');
        }

        $user = auth()->user();
        \Log::info('TeacherMiddleware: User found', [
            'user_id' => $user->id,
            'role' => $user->role,
            'is_teacher' => $user->isTeacher(),
        ]);

        // Check if user has teacher role
        if (!$user->isTeacher()) {
            \Log::warning('TeacherMiddleware: User is not a teacher, logging out', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);
            auth()->logout();
            $request->session()->flush();
            return redirect()->route('login')->with('error', 'Access denied. Teacher access required.');
        }

        // Set or refresh session role if missing
        if (!$request->session()->has('user_role')) {
            $request->session()->put('user_role', $user->role);
            $request->session()->save();
            \Log::info('TeacherMiddleware: Set user_role in session', ['role' => $user->role]);
        }

        $sessionRole = $request->session()->get('user_role');

        // Verify session role matches user role
        if ($sessionRole !== 'teacher') {
            \Log::warning('TeacherMiddleware: Session role mismatch', [
                'session_role' => $sessionRole,
                'user_role' => $user->role,
            ]);
            // Fix session role mismatch
            $request->session()->put('user_role', $user->role);
            $request->session()->save();
        }

        \Log::info('TeacherMiddleware: Access granted, continuing');
        return $next($request);
    }
}