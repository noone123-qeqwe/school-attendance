<?php

namespace App\Http\Middleware;

use App\Services\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $key = str_starts_with($feature, 'feature.') ? $feature : 'feature.' . $feature;
        if (!app(FeatureFlagService::class)->enabled($key)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'FEATURE_DISABLED',
                    'message' => 'This feature is currently unavailable.',
                ], 403);
            }

            abort(403, 'This feature is currently unavailable.');
        }

        return $next($request);
    }
}
