<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveClientIpFromTrustedHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = trim((string) config('app.client_ip_header'));

        if ($header !== '') {
            $clientIp = $request->header($header);

            if (is_string($clientIp) && filter_var($clientIp, FILTER_VALIDATE_IP) !== false) {
                // Override the proxy socket address before Laravel's throttle,
                // audit, and authorization middleware read Request::ip().
                $request->server->set('REMOTE_ADDR', $clientIp);

                // Render terminates TLS at its edge. Accept only its HTTPS
                // signal and never let a forwarded HTTP value downgrade URLs.
                if (strtolower(trim((string) $request->header('X-Forwarded-Proto'))) === 'https') {
                    $request->server->set('HTTPS', 'on');
                }
            }
        }

        return $next($request);
    }
}
