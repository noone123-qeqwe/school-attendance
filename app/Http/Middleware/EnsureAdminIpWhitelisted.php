<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class EnsureAdminIpWhitelisted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            $whitelistString = Setting::get('admin_ip_whitelist');
            
            if (!empty($whitelistString)) {
                $whitelistedIps = array_map('trim', explode(',', $whitelistString));
                $clientIp = $request->ip();
                
                // Allow localhost by default for local development, unless it's strictly removed
                if (!in_array($clientIp, $whitelistedIps) && $clientIp !== '127.0.0.1' && $clientIp !== '::1') {
                    abort(403, 'Unauthorized IP address.');
                }
            }
        }
        
        return $next($request);
    }
}
