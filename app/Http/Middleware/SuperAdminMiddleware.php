<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            // Null or empty sub_role means full admin by default, but we treat super_admin explicitly
            // For backward compatibility, if admin_sub_role is null, we assume they are super admin
            $subRole = Auth::user()->admin_sub_role;
            if ($subRole !== 'super_admin' && $subRole !== null) {
                return abort(403, 'Unauthorized action. Super Admin privileges required.');
            }
            return $next($request);
        }
        
        return abort(403, 'Unauthorized.');
    }
}
