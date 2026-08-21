<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID Public & Private Keys
    |--------------------------------------------------------------------------
    |
    | Voluntary Application Server Identification (VAPID) keys for Web Push.
    | You can generate keys via `php artisan webpush:vapid`.
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL', 'mailto:admin@school-attendance.edu')),
        'public_key' => env('VAPID_PUBLIC_KEY', null),
        'private_key' => env('VAPID_PRIVATE_KEY', null),
        'pem_file' => env('VAPID_PEM_FILE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Notification Options
    |--------------------------------------------------------------------------
    |
    | Default icons, TTL, and priority for pushed payloads.
    |
    */

    'defaults' => [
        'icon' => env('WEBPUSH_DEFAULT_ICON', '/images/icons/icon-192x192.png'),
        'badge' => env('WEBPUSH_DEFAULT_BADGE', '/images/icons/icon-72x72.png'),
        'ttl' => (int) env('WEBPUSH_TTL', 86400), // 24 hours
        'urgency' => env('WEBPUSH_URGENCY', 'high'), // very-low, low, normal, high
    ],

];
