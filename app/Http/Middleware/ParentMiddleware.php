<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ParentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to access the parent portal.');
        }

        $user = Auth::user();
        if ($user->role !== 'parent') {
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard')->with('error', 'Access denied. Parent portal access required.');
            } elseif ($user->isTeacher()) {
                return redirect()->route('teacher.dashboard')->with('error', 'Access denied. Parent portal access required.');
            } elseif ($user->isStudent()) {
                return redirect()->route('home')->with('error', 'Access denied. Parent portal access required.');
            }
            return redirect()->route('login')->with('error', 'Access denied. Parent portal access required.');
        }
        return $next($request);
    }
}
