<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Allow access if user is a student, regardless of session role
        // (session role might not be set immediately after login)
        if (!$user->isStudent()) {
            auth()->logout();
            $request->session()->flush();
            return redirect()->route('login')->with('error', 'Please login with student credentials.');
        }

        return $next($request);
    }
}