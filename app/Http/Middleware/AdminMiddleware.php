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
            auth()->logout();
            $request->session()->flush();
            return redirect()->route('login')->with('error', 'Access denied. Admin access required.');
        }

        // Set or refresh session role if missing
        if (!$request->session()->has('user_role')) {
            $request->session()->put('user_role', $user->role);
            $request->session()->save();
        }

        $sessionRole = $request->session()->get('user_role');

        // Verify session role matches user role
        if ($sessionRole !== 'admin') {
            // Fix session role mismatch
            $request->session()->put('user_role', $user->role);
            $request->session()->save();
        }

        return $next($request);
    }
}
