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
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this area.');
        }

        $user = auth()->user();

        // Check if user has teacher role
        if (!$user->isTeacher()) {
            return redirect()->route('login')->with('error', 'Access denied. Teacher access required.');
        }

        // Set or refresh session role if missing
        if (!$request->session()->has('user_role')) {
            $request->session()->put('user_role', $user->role);
            $request->session()->save();
        }

        $sessionRole = $request->session()->get('user_role');

        // Verify session role matches user role
        if ($sessionRole !== 'teacher') {
            // Fix session role mismatch
            $request->session()->put('user_role', $user->role);
            $request->session()->save();
        }

        return $next($request);
    }
}