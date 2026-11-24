<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExcludeDataEntryAdmin
{
    /**
     * Restrict routes that are hidden from data_entry sub-role administrators.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isAdmin() && $user->admin_sub_role === 'data_entry') {
            abort(403, 'Unauthorized. Data entry administrators cannot access this resource.');
        }

        return $next($request);
    }
}
