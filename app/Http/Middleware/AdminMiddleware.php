<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this area.');
        }

        $user = auth()->user();

        // Check if user has admin role
        if (!$user->isAdmin()) {
            if ($user->isTeacher()) {
                return redirect()->route('teacher.dashboard')->with('error', 'Access denied. Administrator access required.');
            } elseif ($user->isParent()) {
                return redirect()->route('parent.dashboard')->with('error', 'Access denied. Administrator access required.');
            } elseif ($user->isStudent()) {
                return redirect()->route('home')->with('error', 'Access denied. Administrator access required.');
            }
            return redirect()->route('login')->with('error', 'Access denied. Admin access required.');
        }

        return $next($request);
    }
}
