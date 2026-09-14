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
        'public_key' => env('VAPID_PUBLIC_KEY', 'BK8Q6zu_r0OsAEcbwJ5GEev2ncfOWS7Ha6jaigTuIpYA9NiQck9rJewU9al1b35uwSGnWH-Ml4NWjHdNZGNxY9E'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'b4L9DQ-qLfnidLX59KhCTjKMZaiKbkxh2snIecEDyP8'),
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
