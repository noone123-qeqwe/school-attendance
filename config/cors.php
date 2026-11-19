<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| Cross-origin API access is limited to exact origins listed in
| CORS_ALLOWED_ORIGINS. Leave it unset to allow only APP_URL. Do not use '*'.
|
*/

$configuredOriginsValue = env('CORS_ALLOWED_ORIGINS');
$configuredOrigins = explode(',', (string) ($configuredOriginsValue ?: env('APP_URL', 'http://localhost')));
$allowedOrigins = [];

foreach ($configuredOrigins as $configuredOrigin) {
    $parts = parse_url(trim($configuredOrigin));
    if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) ||
        !in_array(strtolower($parts['scheme']), ['http', 'https'], true) ||
        isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) ||
        (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/')) {
        continue;
    }

    $host = strtolower($parts['host']);
    if (str_contains($host, '*')) {
        continue;
    }

    $scheme = strtolower($parts['scheme']);
    $origin = $scheme . '://' . $host;
    if (isset($parts['port']) && !(($scheme === 'http' && $parts['port'] === 80) || ($scheme === 'https' && $parts['port'] === 443))) {
        $origin .= ':' . $parts['port'];
    }

    $allowedOrigins[] = $origin;
}

$allowedOriginPatterns = array_map(
    static fn (string $origin): string => '#\A' . preg_quote($origin, '#') . '\z#',
    array_values(array_unique($allowedOrigins))
);

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [],
    'allowed_origins_patterns' => $allowedOriginPatterns,
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],
    'exposed_headers' => [],
    'max_age' => 600,
    'supports_credentials' => false,
];
