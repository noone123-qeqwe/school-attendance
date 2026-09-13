<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Installed & Latest Application Versions
    |--------------------------------------------------------------------------
    */
    'installed_version' => env('APP_INSTALLED_VERSION', '2.4.4'),
    'default_version' => env('APP_LATEST_VERSION', '2.4.4'),

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
        '2.4.4' => [
            'version' => '2.4.4',
            'version_tag' => 'v2.4.4',
            'title' => 'Continuous Semantic Version Progression & Deployment Resiliency',
            'description' => 'Permanent resolution for version progression stalls across system updates and cloud deployments with durable database persistence and post-update cache invalidation.',
            'features' => [
                'Unstoppable consecutive version auto-progression across 1-click update consoles and background releases',
                'Durable database-first release state storage resilient to read-only container filesystems and cloud restarts',
                'Dynamic semver comparison eliminating artificial version floor traps',
            ],
            'improvements' => [
                'Post-update cache invalidation guaranteeing immediate visibility of fresh version badges across all dashboards',
                'Graceful multi-manifest disk synchronization with non-blocking error recovery',
                'Real-time Service Worker cache busting and PWA asset alignment on every release',
            ],
            'bugFixes' => [
                'Fixed system release version moving once to 2.4.2 and remaining frozen on subsequent updates',
                'Fixed outdated compiled view cache serving stale release tags after full system updates',
            ],
            'security' => [],
            'released_at' => '2026-09-13',
        ],
        '2.4.3' => [
            'version' => '2.4.3',
            'version_tag' => 'v2.4.3',
            'title' => 'Dual Account & Email Verification Security in Forgot Password Flow',
            'description' => 'Two-factor account recovery requiring matching Student/Employee ID and registered email before OTP generation, zero email leakage, and anti-tampering protection.',
            'features' => [
                'Strict dual verification requiring matching Account ID and registered Gmail before OTP issuance',
                'Session-bound recovery tokens preventing URL parameter spoofing across multi-step reset forms',
            ],
            'improvements' => [
                'Clean contextual error feedback on mismatched credentials without leaking account existence',
                'Unified account verification across both web and mobile API forgot password endpoints',
            ],
            'bugFixes' => [
                'Fixed arbitrary email submissions requesting OTPs for unrelated user accounts',
            ],
            'security' => [
                'Multi-factor identity confirmation before triggering transactional reset tokens',
            ],
            'released_at' => '2026-09-13',
        ],
        '2.4.2' => [
            'version' => '2.4.2',
            'version_tag' => 'v2.4.2',
            'title' => 'Automatic Concurrency-Safe Student ID Generation System',
            'description' => 'Automated Student ID sequence generation with atomic database locking, eliminating manual ID entry and preventing race-condition duplicate IDs.',
            'features' => [
                'Automatic unique Student ID allocation in YYYYNNNN format (e.g. 20260001)',
                'Concurrency-safe pessimistic locking ensuring zero ID collisions during simultaneous student registrations',
            ],
            'improvements' => [
                'Automatic sequence recovery from highest existing database identifier on initial startup',
                'Immediate QR card generation for newly generated Student IDs upon registration',
            ],
            'bugFixes' => [
                'Fixed manual Student ID typing overhead and duplicate ID assignment errors during bulk intake',
            ],
            'security' => [],
            'released_at' => '2026-09-13',
        ],
        '2.4.1' => [
            'version' => '2.4.1',
            'version_tag' => 'v2.4.1',
            'title' => 'Forgot Password Deliverability & Version Progression Update',
            'description' => 'Transactional email delivery overhaul via direct Brevo HTTP API, streamlined email-only authentication workflows, and dynamic system release version progression.',
            'features' => [
                'Guaranteed transactional OTP delivery for all users across any email domain bypassing cloud SMTP blocks',
                'Simplified and secure email-focused password reset verification interface',
                'Dynamic semantic version auto-synchronization ensuring version numbers advance cleanly upon update',
            ],
            'improvements' => [
                'Prioritized direct HTTPS port 443 delivery for zero cloud firewall throttling',
                'Automatic fallback and graceful retry across secondary email mailers',
                'Seamless case-insensitive and trimmed email identifier normalization',
            ],
            'bugFixes' => [
                'Fixed Forgot Password OTP emails failing to reach external recipients due to provider sandbox restrictions',
                'Fixed system release version remaining frozen at v2.4.0 after updates and continuous deployments',
                'Fixed session authentication state loss during OTP password reset completion',
            ],
            'security' => [
                'Zero secret leakage in client logs and robust cryptographic 6-digit OTP generation',
                'Isolated per-user verification attempt rate limiting and cooldown enforcement',
            ],
            'released_at' => '2026-09-13',
        ],
        '2.4.0' => [
            'version' => '2.4.0',
            'version_tag' => 'v2.4.0',
            'title' => 'Continuous Presence Verification & Anti-Escape Attendance Security',
            'description' => 'Major attendance security overhaul introducing periodic geofence presence verification, background heartbeat staleness audits, anti-teleportation velocity filters, and proxy device protection.',
            'features' => [
                'Continuous background presence verification preventing students from checking in and immediately leaving campus',
                'Periodic geofence auditing with configurable grace periods and automatic escape status transition',
                'Multi-account proxy device protection preventing buddy clock-in from the same phone',
                'Real-time attendance registration synchronization across web and mobile attendance history',
            ],
            'improvements' => [
                'Strict GPS teleportation leap filtering (>40 m/s) and mock-location sensor accuracy checks',
                'Instant location permission revocation detection with automatic presence penalty',
                'Campus fallback geofencing for sessions created on desktop teacher stations without GPS hardware',
                'Dynamic system semantic version tracking and automated release updates across all UI dashboards',
            ],
            'bugFixes' => [
                'Fixed attendance records not persisting or appearing in student Attendance History after successful scan',
                'Fixed QR token validation desynchronization between teacher projector and student scanner',
                'Fixed teacher QR session countdown timer parsing and session restoration on page reload',
                'Fixed system version stuck on v2.3.5 across client dashboards and 1-click update consoles',
            ],
            'security' => [
                'Multi-account device fingerprint binding preventing proxy attendance',
                'Heartbeat staleness tracking detecting closed tabs and background tab suspension',
                'Strict tamper-resistant geolocation validation and mock coordinate suppression',
            ],
            'released_at' => '2026-09-12',
        ],
        '2.3.6' => [
            'version' => '2.3.6',
            'version_tag' => 'v2.3.6',
            'title' => 'Mobile Scanner UI/UX Polish & Cross-Environment Code Validation',
            'description' => 'Redesigned mobile attendance QR scanner layout, fixed dual camera feed conflicts, and improved cross-environment token rotation tolerance.',
            'features' => [
                'Redesigned mobile QR Attendance Scanner with balanced layout, polished controls, and smooth video viewport',
                'Cross-environment token rotation tolerance ensuring valid scans even across time drift',
            ],
            'improvements' => [
                'Eliminated split camera video feed on high-DPI mobile devices',
                'Optimized QR decoding frame rate for instant barcode capture',
            ],
            'bugFixes' => [
                'Fixed false "QR code invalid or expired" errors caused by clock skew',
                'Fixed camera orientation distortion on portrait mobile displays',
            ],
            'security' => [],
            'released_at' => '2026-09-11',
        ],
        '2.3.5' => [
            'version' => '2.3.5',
            'version_tag' => 'v2.3.5',
            'title' => 'Mobile Camera Lifecycle, Permissions & Version Sync Fix',
            'description' => 'Resolved mobile QR camera permissions lifecycle and continuous version synchronization across all client devices and admin update controls.',
            'features' => [
                'Direct inline camera permissions dispatch with continuous video stream retention',
                'Seamless manual 6-digit attendance code entry workflow with instant visual validation',
                'Continuous over-the-air PWA and service worker version tracking that automatically triggers on all build bumps',
            ],
            'improvements' => [
                'Strict Content Security Policy (CSP) nonce compliance across all dynamic modal and scanner handlers',
                'Instant client cache invalidation and reliable multi-device update broadcast',
                'Real-time telemetry badge updates in Admin System Maintenance Center',
            ],
            'bugFixes' => [
                'Fixed version check becoming stuck after a single update by decoupling timestamp persistence from prompt display',
                'Fixed unresponsive Allow Camera and Enter Code actions on mobile QR scanner',
                'Fixed missing version badge refresh in 1-Click System Update console',
            ],
            'security' => [],
            'released_at' => '2026-09-11',
        ],
        '2.3.4' => [
            'version' => '2.3.4',
            'version_tag' => 'v2.3.4',
            'title' => 'Mobile Camera Scanner & CSP Fix',
            'description' => 'Resolved unresponsive mobile Scan button with direct inline camera permissions dispatch, CSP script nonce security, and instant viewfinder activation.',
            'features' => [
                'Direct-action mobile QR scanner trigger with immediate camera permissions request',
                'Dedicated full-screen mobile camera viewfinder overlay with haptic scanning feedback',
            ],
            'improvements' => [
                'Strict Content Security Policy (CSP) script nonce compliance for all dynamic event listeners',
                'Eliminated touch-event race conditions on mobile bottom navigation buttons',
                'Instant attendance record validation and seamless status redirection',
            ],
            'bugFixes' => [
                'Fixed unresponsive Scan button in mobile bottom navigation capsule',
                'Fixed JavaScript event binding race preventing camera from launching on mobile devices',
                'Fixed missing triggerHaptic helper in standalone scanner view',
            ],
            'security' => [],
            'released_at' => '2026-09-11',
        ],
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
