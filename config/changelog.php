<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Installed & Latest Application Versions
    |--------------------------------------------------------------------------
    */
    'installed_version' => env('APP_INSTALLED_VERSION', '2.3.3'),
    'default_version' => env('APP_LATEST_VERSION', '2.3.3'),

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
        '2.3.3' => [
            'version' => '2.3.3',
            'version_tag' => 'v2.3.3',
            'title' => 'Mobile QR Scanner Fix',
            'description' => 'Fixed the mobile QR scanner so tapping the Scan button immediately opens the camera scanner inline without navigating away.',
            'features' => [
                'Mobile Scan button now opens the QR camera scanner instantly',
                'Dedicated mobile scan page that auto-launches the scanner on load',
            ],
            'improvements' => [
                'Scanner modal is now available on all mobile pages, not just the desktop view',
                'After a successful scan, app redirects to home screen showing updated attendance status',
                'Enhanced haptic feedback patterns for scan success and error states',
            ],
            'bugFixes' => [
                'Fixed Scan button navigating to desktop home instead of opening the camera',
                'Fixed missing triggerHaptic function reference in the mobile scanner',
                'Fixed scanner not auto-closing and refreshing correctly on mobile',
            ],
            'security' => [],
            'released_at' => '2026-09-11',
        ],
        '2.3.2' => [
            'version' => '2.3.2',
            'version_tag' => 'v2.3.2',
            'title' => 'Smart Attendance Branding & App Update',
            'description' => 'Updated application branding to Smart Attendance with automatic over-the-air launcher icon and home screen synchronization.',
            'features' => [
                'Official app name and home screen title updated to Smart Attendance',
                'Automatic in-place home screen label synchronization without requiring re-download',
            ],
            'improvements' => [
                'Enhanced manifest cache-busting for immediate mobile client detection',
                'Updated service worker cache and offline manifest resolution',
            ],
            'bugFixes' => [],
            'security' => [],
            'released_at' => '2026-09-08',
        ],
        '2.3.1' => [
            'version' => '2.3.1',
            'version_tag' => 'v2.3.1',
            'title' => 'Biometric Detection & Sign-In Workflow Update',
            'description' => 'Enhanced biometric sign-in behavior with device capability pre-checks, intuitive account setup popups, password verification, and seamless credential management.',
            'features' => [
                'Automatic biometric capability detection (Supported, Unsupported, Not Configured)',
                'Biometric sign-in setup popup for unregistered accounts',
                'Password verification modal before passkey/biometric creation',
                'Success confirmation and direct biometric login after registration',
            ],
            'improvements' => [
                'Optimized account isolation for multiple users on a single device',
                'Standardized biometric management across Admin, Teacher, Student, and Parent profile settings',
                'Instant PWA update popup notification when new releases are pushed',
            ],
            'bugFixes' => [
                'Resolved issue where tapping Sign In with Fingerprint on unregistered accounts would do nothing or error',
                'Fixed device credential deletion and state reset upon disabling biometrics',
            ],
            'security' => [
                'Strict server-side WebAuthn challenge verification',
            ],
            'released_at' => '2026-09-07',
        ],

        '2.3.0' => [
            'version' => '2.3.0',
            'version_tag' => 'v2.3.0',
            'title' => 'Biometric Authentication & PWA Reliability Overhaul',
            'description' => 'Comprehensive update featuring interactive biometric login guidance, universal PWA installation across mobile and desktop, direct APK downloads, and persistent update loop fixes.',
            'features' => [
                'Biometric login guidance modal for unregistered fingerprints and passkeys',
                'Universal PWA installer with platform-tailored iOS Safari & Android Chrome guides',
                'Direct Android APK (.apk) download shortcut from login and update dialogs',
                'Real-time PWA update detection with automatic cache synchronization',
            ],
            'improvements' => [
                'Unified PWA prompt delegation for all mobile and desktop devices',
                'Enhanced service worker lifecycle handling and background sync',
                'Refined dark mode aesthetics, gold accents, and typography',
            ],
            'bugFixes' => [
                'Fixed broken "Install App" button behavior on mobile devices',
                'Resolved persistent update pop-up loop and stale version display',
                'Fixed UTF-8 character corruption and mojibake across templates',
            ],
            'security' => [
                'Hardened WebAuthn authentication verification and CSRF token entropy',
            ],
            'released_at' => '2026-09-07',
        ],

        '2.2.0' => [
            'version' => '2.2.0',
            'version_tag' => 'v2.2.0',
            'title' => 'Mobile App Experience & Icon Update',
            'description' => 'Major mobile redesign with native app experience, improved PWA icon, and fullscreen support.',
            'features' => [
                'True fullscreen mode when installed as PWA',
                'Updated app icon with better text display',
                'Mobile-first navigation with bottom tab bar',
                'Role-based mobile dashboards',
            ],
            'improvements' => [
                'Optimized manifest for better mobile installation',
                'Enhanced app naming for home screen display',
                'Improved safe area handling for notched devices',
            ],
            'bugFixes' => [
                'Fixed app icon text being cut off',
                'Fixed status bar visibility in installed mode',
            ],
            'security' => [],
            'released_at' => '2026-09-06',
        ],

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
