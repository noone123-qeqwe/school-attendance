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
        // Skip IP whitelist check in local development
        if (config('app.env') === 'local') {
            return $next($request);
        }

        if (Auth::check() && Auth::user()->role === 'admin') {
            $whitelistString = Setting::get('admin_ip_whitelist');
            
            if (!empty($whitelistString)) {
                $whitelistedIps = array_map('trim', explode(',', $whitelistString));
                $clientIpHeader = trim((string) config('app.client_ip_header'));
                $clientIp = $clientIpHeader !== ''
                    ? $request->header($clientIpHeader)
                    : $request->ip();

                // A configured proxy header is trusted only when it contains a
                // single valid address. Fail closed rather than treating the
                // shared proxy address as an administrator's client address.
                if (!is_string($clientIp) || filter_var($clientIp, FILTER_VALIDATE_IP) === false) {
                    abort(403, 'Unable to verify client IP address.');
                }
                
                if (!in_array($clientIp, $whitelistedIps) && $clientIp !== '127.0.0.1' && $clientIp !== '::1') {
                    abort(403, 'Unauthorized IP address.');
                }
            }
        }
        
        return $next($request);
    }
}
