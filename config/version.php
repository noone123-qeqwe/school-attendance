<?php

$versionFile = base_path('version.json');
$versionData = [];

if (file_exists($versionFile)) {
    $raw = @file_get_contents($versionFile);
    if (!empty($raw)) {
        $decoded = @json_decode($raw, true);
        if (is_array($decoded)) {
            $versionData = $decoded;
        }
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | Application Release Version & Build Configuration
    |--------------------------------------------------------------------------
    |
    | Single centralized source of truth for the application version.
    | Reads from root version.json with optional environment overrides.
    |
    */
    'version' => env('APP_VERSION', $versionData['version'] ?? '2.4.0'),
    'build' => env('APP_BUILD', $versionData['build'] ?? date('Ymd') . '.001'),
    'commit' => env('APP_COMMIT', $versionData['commit'] ?? null),
    'release_date' => env('APP_RELEASE_DATE', $versionData['release_date'] ?? date('Y-m-d')),
    'channel' => env('APP_CHANNEL', $versionData['channel'] ?? 'stable'),
    'name' => env('APP_DISPLAY_NAME', $versionData['name'] ?? 'Smart Classroom Attendance System'),
];
