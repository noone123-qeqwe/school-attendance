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
            return redirect()->route('login')->with('error', 'Access denied. Admin access required.');
        }

        return $next($request);
    }
}
