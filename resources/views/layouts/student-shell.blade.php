@php
    $studentUser = Auth::user();
    $shell = $studentShellData ?? [
        'hasActiveAssistantAssignment' => (bool) ($hasActiveAssistantAssignment ?? false),
        'classesCount' => 0,
        'recordsCount' => 0,
        'excusesCount' => 0,
        'pendingExcusesCount' => 0,
        'unreadNotificationsCount' => 0,
        'initials' => 'ST',
        'shortName' => $studentUser?->name ?? 'Student',
        'subjectsQuickList' => [],
    ];
    $isStandaloneScannerRoute = request()->routeIs('mobile.scan');
    $isGateRoute = request()->routeIs('password.change.form', 'student.pending-approval') || (bool) ($studentUser?->must_change_password);
    $hasCustomAvatar = !empty($studentUser?->profile_image);
    $avatarUrl = $hasCustomAvatar ? $studentUser->profile_photo_url_with_version : null;

    $resolvedPageTitle = html_entity_decode(trim((string) View::yieldContent('page-title')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($resolvedPageTitle === '') {
        $resolvedPageTitle = match (true) {
            request()->routeIs('home') => 'Dashboard',
            request()->routeIs('student.classes', 'student.schedule') => 'Classes & Schedule',
            request()->routeIs('student.calendar', 'student.attendance.calendar', 'attendance.records') => 'Attendance & Calendar',
            request()->routeIs('excuses*') => 'Excuse Submissions',
            request()->routeIs('notifications*') => 'Notifications',
            request()->routeIs('settings.biometrics.*') => 'Biometrics',
            request()->routeIs('settings') => 'Settings',
            request()->routeIs('profile*') => 'Profile',
            request()->routeIs('student-assistant.*') => 'Student Assistant',
            request()->routeIs('mobile.peer-snap') => 'Peer Snap Verification',
            request()->routeIs('mobile.scan') => 'Scan QR Code',
            default => 'Student Portal',
        };
    }

    $sfCommands = [
        [
            'label' => 'Scan QR Code for Attendance',
            'hint' => 'Open live classroom QR scanner (Shortcut: Q)',
            'keywords' => 'scan qr camera checkin check-in attendance',
            'url' => route('mobile.scan', ['open_scanner' => 1]),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>',
        ],
        [
            'label' => 'Enter 6-Digit Instructor PIN',
            'hint' => 'Type the session code shown in class (Shortcut: P)',
            'keywords' => 'pin code manual session checkin check-in',
            'url' => route('mobile.scan', ['open_code' => 1]),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="9" y2="9"/><line x1="4" x2="20" y1="15" y2="15"/><line x1="10" x2="8" y1="3" y2="21"/><line x1="16" x2="14" y1="3" y2="21"/></svg>',
        ],
        [
            'label' => 'Submit an Excuse Request',
            'hint' => 'Upload documentation for an absence',
            'keywords' => 'excuse absence letter medical upload submit',
            'url' => route('excuses.create_general'),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>',
        ],
        [
            'label' => 'Go to Dashboard',
            'hint' => 'Overview, next class, and live status',
            'keywords' => 'home dashboard overview',
            'url' => route('home'),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>',
        ],
        [
            'label' => 'Go to Classes & Schedule',
            'hint' => 'Enrolled subjects and weekly timetable',
            'keywords' => 'classes schedule subjects courses timetable',
            'url' => route('student.classes'),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
        ],
        [
            'label' => 'Go to Attendance & Calendar',
            'hint' => 'Monthly calendar, records, and CSV export',
            'keywords' => 'attendance calendar history logs records export csv',
            'url' => route('student.calendar'),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>',
        ],
        [
            'label' => 'Go to Excuse Submissions',
            'hint' => 'Track pending, approved, and rejected requests',
            'keywords' => 'excuses status pending approved',
            'url' => route('excuses'),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>',
        ],
        [
            'label' => 'Open Student Settings',
            'hint' => 'Profile, guardians, biometrics, device & appearance',
            'keywords' => 'settings profile guardians password email biometrics device recovery theme',
            'url' => route('settings'),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
        ],
        [
            'label' => 'Toggle Light / Dark Theme',
            'hint' => 'Switch between Dark Oxblood and Light Porcelain',
            'keywords' => 'theme dark light oxblood porcelain appearance mode',
            'action' => 'toggleTheme',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>',
        ],
    ];

    foreach ($shell['subjectsQuickList'] as $subj) {
        $sfCommands[] = [
            'label' => $subj['code'] . ' — ' . $subj['name'],
            'hint' => $subj['room'] . ' · ' . $subj['instructor'],
            'keywords' => 'course subject class ' . $subj['code'] . ' ' . $subj['name'] . ' ' . $subj['room'] . ' ' . $subj['instructor'],
            'url' => route('student.classes', ['course' => $subj['code']]),
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/></svg>',
        ];
    }

    $bnHomeActive = request()->routeIs('home');
    $bnClassesActive = request()->routeIs('student.classes', 'student.schedule');
    $bnCalendarActive = request()->routeIs('student.calendar', 'student.attendance.calendar', 'attendance.records');
    $bnExcusesActive = request()->routeIs('excuses*');
@endphp
<!doctype html>
<html lang="en" data-student-theme="dark" class="dark">
<head>
    <script @cspNonce>
        (function () {
            try {
                var saved = localStorage.getItem('student_portal_theme');
                var theme = saved === 'light' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-student-theme', theme);
                document.documentElement.classList.toggle('dark', theme === 'dark');
            } catch (e) {}
        })();
    </script>
    @include('partials.alert-banner-assets')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth-user-id" content="{{ Auth::id() ?? '' }}">
    <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    <title>{{ config('app.name') }} | @yield('portal-title', 'Student Portal')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    @include('partials.pwa-tags')
    @if(request()->routeIs('settings', 'profile'))
        <link rel="stylesheet" href="{{ asset('css/profile-photo-manager.css') }}?v={{ filemtime(public_path('css/profile-photo-manager.css')) }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/student-frontend.css') }}?v={{ filemtime(public_path('css/student-frontend.css')) }}">

    <script @cspNonce src="{{ asset('js/gps-sampler.js') }}?v={{ filemtime(public_path('js/gps-sampler.js')) }}"></script>
    @vite(['resources/js/app.js'])
    @stack('styles')

    <script type="speculationrules" @cspNonce>
        {!! json_encode([
            'prefetch' => [[
                'source' => 'list',
                'urls' => [
                    route('home'),
                    route('student.classes'),
                    route('student.calendar'),
                    route('excuses'),
                ],
                'eagerness' => 'moderate',
            ]],
        ], JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body @class([
    'sf-body',
    'student-portal',
    'sf-scanner-mode' => $isStandaloneScannerRoute,
    'scanner-page-layout' => $isStandaloneScannerRoute,
    'attendance-verification-page' => View::hasSection('focused-attendance-verification'),
])
    data-notif-poll-url="{{ $isGateRoute ? '' : route('notifications.poll') }}"
    data-notif-read-url="{{ $isGateRoute ? '' : route('notifications.read') }}"
    data-scan-qr-url="{{ route('mobile.scan', ['open_scanner' => 1]) }}"
    data-scan-pin-url="{{ route('mobile.scan', ['open_code' => 1]) }}">

    <a href="#mainContent" class="sf-skip-link">Skip to main content</a>

    <div class="sf-shell">
        @unless($isStandaloneScannerRoute)
        <aside class="sf-sidebar" id="sidebar" aria-label="Sidebar">
            @hasSection('role-sidebar')
                @yield('role-sidebar')
            @else
                @include('layouts.sidebars.student')
            @endif

            <div class="sf-sidebar-footer">
                <div class="sf-account-popover" id="sfAccountPopover" role="menu" aria-label="Account options">
                    <div class="sf-popover-head">
                        <strong>{{ $studentUser->name }}</strong>
                        <span>ID {{ $studentUser->student_number ?: $studentUser->email }}</span>
                    </div>
                    <a href="{{ route('settings') }}" class="sf-popover-item" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span>Settings</span>
                    </a>
                    <button type="button" class="sf-popover-item is-danger" data-trigger-logout role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        <span>Log out</span>
                    </button>
                </div>

                <button type="button" class="sf-account-trigger" id="sfSidebarAccountBtn" aria-haspopup="menu" aria-label="Open account menu">
                    <span class="sf-avatar">
                        @if($hasCustomAvatar)
                            <img src="{{ $avatarUrl }}" class="user-avatar-img" alt="{{ $studentUser->name }}">
                        @else
                            {{ $shell['initials'] }}
                        @endif
                    </span>
                    <span class="sf-account-meta">
                        <span class="sf-account-name" style="display:block;">{{ $shell['shortName'] }}</span>
                        <span class="sf-account-role" style="display:block;">Student</span>
                    </span>
                    <svg class="sf-account-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>
                </button>

                <button type="button" class="sf-theme-icon-btn" data-action="toggle-theme" title="Switch Light / Dark Theme" aria-label="Switch Light or Dark Theme">
                    <svg data-theme-icon-sun viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    <svg data-theme-icon-moon style="display:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                </button>
            </div>
        </aside>
        @endunless

        <div class="sf-main-wrap">
            @unless($isStandaloneScannerRoute)
            <header class="sf-topbar" id="topHeader">
                <div class="sf-topbar-left">
                    <img src="{{ asset('images/logo.png') }}" class="sf-mobile-crest" alt="School logo">
                    <div class="sf-topbar-title">{{ $resolvedPageTitle }}</div>
                </div>

                <div class="sf-topbar-right">
                    <button type="button"
                            id="headerSearchBar"
                            class="sf-cmd-trigger"
                            onclick="window.openCommandPalette?.()"
                            aria-label="Search or jump to pages and courses (Ctrl + K)"
                            title="Search or jump to... (Ctrl + K)">
                        <span class="sf-cmd-trigger-left">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <span>Search or jump to...</span>
                        </span>
                        <span class="sf-kbd-group" aria-hidden="true">
                            <kbd class="sf-kbd" id="headerSearchModKey">Ctrl</kbd>
                            <kbd class="sf-kbd">K</kbd>
                        </span>
                    </button>

                    <div class="sf-notif-wrap">
                        <button type="button" class="sf-icon-btn" id="topNavNotifBtn" aria-label="Notifications" aria-expanded="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            <span class="sf-notif-dot" id="topNavNotifDot" @if(($shell['unreadNotificationsCount'] ?? 0) <= 0) hidden @endif></span>
                        </button>
                        <div class="sf-notif-dropdown" id="sfNotifDropdown" role="region" aria-label="Recent notifications">
                            <div class="sf-notif-dd-head">
                                <span class="sf-notif-dd-title">Notifications</span>
                                <button type="button" class="sf-notif-dd-mark" id="sfNotifMarkAllBtn" onclick="window.markAllRead?.()" @if(($shell['unreadNotificationsCount'] ?? 0) <= 0) hidden @endif>
                                    Mark all read
                                </button>
                            </div>
                            <div class="sf-notif-dd-list" id="topNavNotifList">
                                <div class="sf-notif-dd-empty">Loading notifications...</div>
                            </div>
                            <a href="{{ route('notifications') }}" class="sf-notif-dd-foot">
                                View all notifications
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>

                    <button type="button" class="sf-mobile-avatar-btn" id="sfMobileAvatarBtn" aria-label="Open account menu for {{ $studentUser->name }}">
                        <span class="sf-avatar">
                            @if($hasCustomAvatar)
                                <img src="{{ $avatarUrl }}" class="user-avatar-img" alt="{{ $studentUser->name }}">
                            @else
                                {{ $shell['initials'] }}
                            @endif
                        </span>
                    </button>
                </div>
            </header>
            @endunless

            <main class="{{ $isStandaloneScannerRoute ? '' : 'sf-content' }}{{ request()->routeIs('settings') ? ' is-narrow' : '' }}" id="mainContent">
                @yield('content')
            </main>
        </div>
    </div>

    @unless($isStandaloneScannerRoute)
    <nav class="sf-bottom-nav" id="mobileBottomNav" aria-label="Primary student navigation">
        <a href="{{ route('home') }}"
           class="sf-bn-item {{ $bnHomeActive ? 'active' : '' }}"
           @if($bnHomeActive) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Home</span>
        </a>

        <a href="{{ route('student.classes') }}"
           class="sf-bn-item {{ $bnClassesActive ? 'active' : '' }}"
           @if(request()->routeIs('student.classes')) aria-current="page" @elseif($bnClassesActive) aria-current="location" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            <span>Classes</span>
        </a>

        <a href="{{ route('mobile.scan') }}" class="sf-bn-fab" aria-label="Scan QR Code for Attendance">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
        </a>

        <a href="{{ route('student.calendar') }}"
           class="sf-bn-item {{ $bnCalendarActive ? 'active' : '' }}"
           @if(request()->routeIs('student.calendar')) aria-current="page" @elseif($bnCalendarActive) aria-current="location" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
            <span>Calendar</span>
        </a>

        <a href="{{ route('excuses') }}"
           class="sf-bn-item {{ $bnExcusesActive ? 'active' : '' }}"
           @if(request()->routeIs('excuses')) aria-current="page" @elseif($bnExcusesActive) aria-current="location" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
            <span>Excuses</span>
        </a>
    </nav>
    @endunless

    {{-- Mobile Centered Account Menu Modal --}}
    <div class="sf-modal-backdrop" id="sfMobileAccountModal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Account menu">
        <div class="sf-modal-card is-sm">
            <div style="padding:18px;border-bottom:1px solid var(--sf-border);display:flex;align-items:center;gap:12px;">
                <span class="sf-avatar is-lg">
                    @if($hasCustomAvatar)
                        <img src="{{ $avatarUrl }}" class="user-avatar-img" alt="{{ $studentUser->name }}">
                    @else
                        {{ $shell['initials'] }}
                    @endif
                </span>
                <div style="min-width:0;">
                    <div style="font-size:14px;font-weight:700;color:var(--sf-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $studentUser->name }}</div>
                    <div class="sf-mono" style="font-size:11.5px;color:var(--sf-text-muted);">ID {{ $studentUser->student_number ?: $studentUser->email }}</div>
                </div>
            </div>
            <div style="padding:8px;display:flex;flex-direction:column;gap:4px;">
                <a href="{{ route('settings') }}" class="sf-popover-item" style="padding:11px 12px;font-size:13.5px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Settings</span>
                </a>
                <button type="button" class="sf-popover-item" style="padding:11px 12px;font-size:13.5px;" data-action="toggle-theme">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    <span>Switch Light / Dark Theme</span>
                </button>
                <div style="height:1px;background:var(--sf-border);margin:4px 8px;"></div>
                <button type="button" class="sf-popover-item is-danger" style="padding:11px 12px;font-size:13.5px;" data-trigger-logout>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    <span>Log out</span>
                </button>
            </div>
        </div>
        <button type="button" class="sf-modal-circle-close" data-close-modal="sfMobileAccountModal" aria-label="Close account menu">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    {{-- Logout Confirmation Modal --}}
    <form id="globalLogoutForm" action="{{ route('logout') }}" method="POST" style="display:none;">
        @csrf
    </form>
    <div class="sf-modal-backdrop" id="logoutConfirmModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="logoutConfirmTitle">
        <div class="sf-modal-card is-sm">
            <div class="sf-modal-head">
                <div>
                    <h3 id="logoutConfirmTitle">Confirm Log Out</h3>
                    <p>End your current Smart Attendance session</p>
                </div>
                <button type="button" class="sf-modal-close" data-close-modal="logoutConfirmModal" aria-label="Close">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="sf-modal-body">
                <p style="margin:0;color:var(--sf-text-secondary);font-size:13px;">Are you sure you want to log out of your student portal session?</p>
            </div>
            <div class="sf-modal-foot">
                <button type="button" class="sf-btn sf-btn-ghost" data-close-modal="logoutConfirmModal">Cancel</button>
                <button type="button" class="sf-btn sf-btn-danger" id="confirmLogoutModalBtn">Log Out</button>
            </div>
        </div>
    </div>

    {{-- Linear Command Palette (Ctrl+K) --}}
    <div class="sf-modal-backdrop" id="cmdPaletteOverlay" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Command palette">
        <div class="sf-cmd-card">
            <div class="sf-cmd-input-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" id="cmdPaletteInput" class="sf-cmd-input" placeholder="Type a command or search courses..." autocomplete="off" spellcheck="false">
                <kbd class="sf-kbd">ESC</kbd>
            </div>
            <div class="sf-cmd-list" id="cmdPaletteItems"></div>
        </div>
    </div>

    {{-- Linear Toast Container --}}
    <div class="sf-toast-container" id="toastContainer" role="status" aria-live="polite" aria-atomic="false"></div>

    @unless($isStandaloneScannerRoute || $isGateRoute)
        @include('components.mobile.install-prompt')
    @endunless
    @unless($isGateRoute)
        @include('partials.student-presence-guardian')
    @endunless

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script @cspNonce>
        window.__SF_COMMANDS__ = @json($sfCommands);
    </script>
    <script @cspNonce src="{{ asset('js/student-frontend.js') }}?v={{ filemtime(public_path('js/student-frontend.js')) }}"></script>
    <script @cspNonce>
        window.openDsModal = window.openSfModal;
        window.closeDsModal = window.closeSfModal;

        document.addEventListener('DOMContentLoaded', function () {
            var isApple = /(Mac|iPhone|iPod|iPad)/i.test(navigator.platform || navigator.userAgent);
            var modKey = document.getElementById('headerSearchModKey');
            if (modKey && isApple) modKey.textContent = '⌘';

            var mobileAvatarBtn = document.getElementById('sfMobileAvatarBtn');
            if (mobileAvatarBtn) {
                mobileAvatarBtn.addEventListener('click', function () {
                    window.openSfModal?.('sfMobileAccountModal');
                });
            }

            document.querySelectorAll('[data-trigger-logout]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    window.closeSfModal?.('sfMobileAccountModal');
                    window.openSfModal?.('logoutConfirmModal');
                });
            });

            var confirmLogoutBtn = document.getElementById('confirmLogoutModalBtn');
            if (confirmLogoutBtn) {
                confirmLogoutBtn.addEventListener('click', function () {
                    document.getElementById('globalLogoutForm')?.submit();
                });
            }

            @if(session('success')) window.showToast?.(@json(session('success')), 'success'); @endif
            @if(session('error')) window.showToast?.(@json(session('error')), 'error'); @endif
            @if(session('warning')) window.showToast?.(@json(session('warning')), 'warning'); @endif
            @if(session('info')) window.showToast?.(@json(session('info')), 'info'); @endif
            @if(session('status')) window.showToast?.(@json(session('status')), 'info'); @endif
        });
    </script>

    @yield('scripts')
    @stack('scripts')
    @include('partials.pwa-body')
</body>
</html>
