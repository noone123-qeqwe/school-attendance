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
        
        if (!$user->isStudent()) {
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isTeacher()) {
                return redirect()->route('teacher.dashboard');
            } elseif ($user->isParent()) {
                return redirect()->route('parent.dashboard');
            }
            return redirect()->route('login')->with('error', 'Please login with student credentials.');
        }

        return $next($request);
    }
}