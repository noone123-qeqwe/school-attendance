<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Installed & Latest Application Versions
    |--------------------------------------------------------------------------
    */
    'installed_version' => env('APP_INSTALLED_VERSION', '1.4.2'),
    'default_version' => env('APP_LATEST_VERSION', '1.4.3'),

    /*
    |--------------------------------------------------------------------------
    | Version-Based Changelog & Update Metadata
    |--------------------------------------------------------------------------
    |
    | Each release defines dynamic update details:
    | - title: Update headline or theme
    | - description: Short release overview
    | - features: 2 to 4 actual new features
    | - improvements: Key optimizations & UX enhancements
    | - bugFixes: Actual issues addressed
    | - security: Security patches (only rendered when applicable)
    |
    */
    'releases' => [
        '1.4.3' => [
            'version' => '1.4.3',
            'version_tag' => 'v1.4.3',
            'title' => 'QR Attendance & System Reliability Update',
            'description' => 'A new version of Smart Attendance is ready with enhanced scanning, real-time OTP deliverability, and system telemetry.',
            'features' => [
                'Student QR scanner with fast code detection',
                'Improved attendance history tracking',
                'Teacher QR generation improvements',
            ],
            'improvements' => [
                'Faster page loading and dashboard metrics',
                'Better mobile navigation and touch responsiveness',
            ],
            'bugFixes' => [
                'Fixed OTP verification rate limit issue',
                'Fixed attendance refresh and real-time sync problem',
            ],
            'security' => [
                'Updated authentication security and token entropy',
            ],
            'released_at' => '2026-09-05',
        ],

        '1.4.2' => [
            'version' => '1.4.2',
            'version_tag' => 'v1.4.2',
            'title' => 'Classroom Attendance Sync',
            'description' => 'Reliability update focusing on classroom synchronization and schedule display.',
            'features' => [
                'Real-time classroom session status indicator',
                'Export attendance reports to CSV format',
            ],
            'improvements' => [
                'Optimized database queries for student listings',
                'Enhanced offline caching for service worker',
            ],
            'bugFixes' => [
                'Fixed intermittent student profile loading delays',
                'Resolved duplicate check-in timestamp issue',
            ],
            'security' => [],
            'released_at' => '2026-09-01',
        ],

        '1.4.1' => [
            'version' => '1.4.1',
            'version_tag' => 'v1.4.1',
            'title' => 'Mobile Performance Update',
            'description' => 'Performance and user experience optimizations for mobile devices.',
            'features' => [
                'Haptic vibration feedback for mobile attendance scans',
                'Live network status and background sync indicator',
            ],
            'improvements' => [
                'Reduced initial service worker asset cache footprint',
                'Smoother slide animations for navigation drawers',
            ],
            'bugFixes' => [
                'Fixed iOS Safari standalone viewport scrolling bug',
            ],
            'security' => [
                'Patched session timeout edge case in background tabs',
            ],
            'released_at' => '2026-08-25',
        ],

        '1.4.0' => [
            'version' => '1.4.0',
            'version_tag' => 'v1.4.0',
            'title' => 'Attendance Portal Modernization',
            'description' => 'Modernized progressive web application design with dark mode aesthetic and install prompt.',
            'features' => [
                'PWA home screen install support for mobile and desktop',
                'Real-time student and parent attendance dashboard',
            ],
            'improvements' => [
                'Glassmorphism interface with tailored color system',
                'Low-latency service worker caching',
            ],
            'bugFixes' => [
                'Initial release stability improvements',
            ],
            'security' => [],
            'released_at' => '2026-08-15',
        ],
    ],
];
