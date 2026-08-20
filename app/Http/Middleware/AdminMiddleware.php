<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Debug logging
        Log::info('AdminMiddleware check', [
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'url' => $request->fullUrl()
        ]);
        
        if (!auth()->check()) {
            Log::warning('AdminMiddleware: User not authenticated, redirecting to login');
            return redirect()->route('login')->with('error', 'Please log in to access this area.');
        }

        $user = auth()->user();
        
        // Check if user has admin role
        if (!$user->isAdmin()) {
            Log::warning('AdminMiddleware: User not admin', ['user_id' => $user->id, 'role' => $user->role]);
            auth()->logout();
            $request->session()->flush();
            return redirect()->route('login')->with('error', 'Access denied. Admin access required.');
        }

        Log::info('AdminMiddleware: Access granted', ['user_id' => $user->id]);
        return $next($request);
    }
}
