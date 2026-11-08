<?php

return [

    'backup' => [
        'process_timeout' => env('BACKUP_PROCESS_TIMEOUT', 300),
        'max_restore_size_mb' => env('BACKUP_MAX_RESTORE_SIZE_MB', 512),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
        'from' => env('RESEND_FROM', 'onboarding@resend.dev'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    'hosting' => [
        'is_cloud' => env('RENDER', false) || env('RENDER_SERVICE_ID') || env('RAILWAY_ENVIRONMENT'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'semaphore' => [
        'api_key'     => env('SEMAPHORE_API_KEY'),
        'sender_name' => env('SEMAPHORE_SENDER_NAME', 'OsmenaAtt'),
        'enabled'     => env('SMS_ENABLED', false),
    ],

];
