<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => trim((string) env('MAIL_HOST', env('EMAIL_HOST', 'smtp.gmail.com'))),
            'port' => (int) env('MAIL_PORT', env('EMAIL_PORT', 587)),
            'encryption' => trim((string) env('MAIL_ENCRYPTION', env('MAIL_SCHEME', env('EMAIL_ENCRYPTION', 'tls')))),
            'username' => trim((string) env('MAIL_USERNAME', env('EMAIL_USER', env('EMAIL_USERNAME', 'osmenacolleges.attendance@gmail.com')))),
            'password' => preg_replace('/\s+/', '', (string) env('MAIL_PASSWORD', env('EMAIL_PASSWORD', env('EMAIL_API_KEY', 'zskulbswpldmxqfp')))),
            'timeout' => (int) env('MAIL_TIMEOUT', 10),
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'smtp_ssl' => [
            'transport' => 'smtp',
            'host' => trim((string) env('MAIL_HOST', env('EMAIL_HOST', 'smtp.gmail.com'))),
            'port' => 465,
            'encryption' => 'ssl',
            'username' => trim((string) env('MAIL_USERNAME', env('EMAIL_USER', env('EMAIL_USERNAME', 'osmenacolleges.attendance@gmail.com')))),
            'password' => preg_replace('/\s+/', '', (string) env('MAIL_PASSWORD', env('EMAIL_PASSWORD', env('EMAIL_API_KEY', 'zskulbswpldmxqfp')))),
            'timeout' => (int) env('MAIL_TIMEOUT', 10),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => trim((string) env('MAIL_FROM_ADDRESS', env('EMAIL_FROM', env('MAIL_USERNAME', 'osmenacolleges.attendance@gmail.com')))),
        'name' => trim((string) env('MAIL_FROM_NAME', env('EMAIL_FROM_NAME', env('APP_NAME', 'Smart Classroom Attendance System')))),
    ],

];
