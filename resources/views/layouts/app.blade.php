@if(Auth::check() && Auth::user()->isStudent())
@include('layouts.student-shell')
@else
<!doctype html>
<html lang="en">
<head>
    @include('partials.alert-banner-assets')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth-user-id" content="{{ Auth::id() ?? '' }}">
    <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    <title>{{ config('app.name') }} | @yield('portal-title', match(auth()->user()?->role) { 'admin' => 'Admin Portal', 'teacher' => 'Teacher Portal', 'parent' => 'Parent Portal', default => 'Student Portal' })</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    @auth
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    @else
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @endauth
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}?v={{ filemtime(public_path('css/design-tokens.css')) }}">
    @php
        $isStudentUser = (bool) Auth::user()?->isStudent();
        $isStandaloneScannerRoute = request()->routeIs('mobile.scan');
        $studentNeedsEnterpriseStyles = !$isStudentUser || request()->routeIs('student.calendar', 'student.attendance.calendar', 'excuses*');
        $studentHasInlinePageHeading = $isStudentUser && (request()->routeIs('student.classes', 'student.schedule', 'student.calendar', 'student.attendance.calendar', 'excuses*', 'notifications*', 'profile*') || View::hasSection('student-inline-heading'));
    @endphp
    @unless($isStandaloneScannerRoute)
        <link rel="stylesheet" href="{{ asset('css/premium.css') }}?v={{ filemtime(public_path('css/premium.css')) }}">
        @if($studentNeedsEnterpriseStyles)
            <link rel="stylesheet" href="{{ asset('css/dashboard-enterprise.css') }}?v={{ filemtime(public_path('css/dashboard-enterprise.css')) }}">
        @endif
    @endunless
    @auth
        <link rel="stylesheet" href="{{ asset('css/command-palette.css') }}?v={{ filemtime(public_path('css/command-palette.css')) }}">
        @if(request()->routeIs('settings'))
            <link rel="stylesheet" href="{{ asset('css/settings-page.css') }}?v={{ filemtime(public_path('css/settings-page.css')) }}">
        @endif
        @if(request()->routeIs('settings', 'profile', 'teacher.profile', 'parent.profile'))
            <link rel="stylesheet" href="{{ asset('css/profile-photo-manager.css') }}?v={{ filemtime(public_path('css/profile-photo-manager.css')) }}">
        @endif
        @if($isStandaloneScannerRoute)
            <link rel="stylesheet" href="{{ asset('css/student-scanner.css') }}?v={{ filemtime(public_path('css/student-scanner.css')) }}">
            <link rel="stylesheet" href="{{ asset('css/student-scanner-page.css') }}?v={{ filemtime(public_path('css/student-scanner-page.css')) }}">
        @endif
        @if(Auth::user()->isAdmin())
            <link rel="stylesheet" href="{{ asset('css/admin-saas.css') }}?v={{ filemtime(public_path('css/admin-saas.css')) }}">
            <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ filemtime(public_path('css/admin-theme.css')) }}">
        @endif
    @endauth
    @include('partials.pwa-tags')
    <script @cspNonce src="{{ asset('js/gps-sampler.js') }}?v={{ filemtime(public_path('js/gps-sampler.js')) }}"></script>
    @vite(['resources/css/app.css'])
    @auth
        @vite(['resources/js/app.js'])
    @endauth
    @stack('styles')
    @unless($isStandaloneScannerRoute)
        @unless($isStudentUser)
            <link rel="stylesheet" href="{{ asset('css/experience-refinements.css') }}?v={{ filemtime(public_path('css/experience-refinements.css')) }}">
        @endunless
        @if(request()->routeIs('settings', 'admin.settings') && !$isStudentUser)
            <link rel="stylesheet" href="{{ asset('css/settings-polish.css') }}?v={{ filemtime(public_path('css/settings-polish.css')) }}">
        @endif
        <link rel="stylesheet" href="{{ asset('css/mobile-enterprise.css') }}?v={{ filemtime(public_path('css/mobile-enterprise.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/desktop-layout.css') }}?v={{ filemtime(public_path('css/desktop-layout.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/portal-layout-refinements.css') }}?v={{ filemtime(public_path('css/portal-layout-refinements.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/system-ui-modern.css') }}?v={{ filemtime(public_path('css/system-ui-modern.css')) }}">
        @if(Auth::user()?->isTeacher())
            <link rel="stylesheet" href="{{ asset('css/portal-unified-foundation.css') }}?v={{ filemtime(public_path('css/portal-unified-foundation.css')) }}">
        @endif
    @endunless
    @if(Auth::check() && Auth::user()->isTeacher() && !request()->routeIs('teacher.*.pdf'))
        @if(!request()->routeIs('teacher.*.preview'))
            <link rel="stylesheet" href="{{ asset('css/teacher-portal-redesign.css') }}?v={{ filemtime(public_path('css/teacher-portal-redesign.css')) }}">
            <link rel="stylesheet" href="{{ asset('css/teacher-simple.css') }}?v={{ filemtime(public_path('css/teacher-simple.css')) }}">
        @endif
        <link rel="stylesheet" href="{{ asset('css/teacher-portal-master.css') }}?v={{ filemtime(public_path('css/teacher-portal-master.css')) }}">
    @endif
    @if(Auth::user()?->isAdmin() && !request()->routeIs('admin.*.pdf'))
        <link rel="stylesheet" href="{{ asset('css/admin-portal-redesign.css') }}?v={{ filemtime(public_path('css/admin-portal-redesign.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/admin-simple.css') }}?v={{ filemtime(public_path('css/admin-simple.css')) }}">
    @endif
    @if(Auth::user()?->isParent() && !request()->routeIs('parent.*.report'))
        <link rel="stylesheet" href="{{ asset('css/parent-portal-redesign.css') }}?v={{ filemtime(public_path('css/parent-portal-redesign.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/parent-simple.css') }}?v={{ filemtime(public_path('css/parent-simple.css')) }}">
    @endif
    @if(Auth::user()?->isStudent() && request()->routeIs('settings'))
        <link rel="stylesheet" href="{{ asset('css/student-settings.css') }}?v={{ filemtime(public_path('css/student-settings.css')) }}">
    @endif
    @if(Auth::user()?->isStudent())
        @if(request()->routeIs('settings'))
            <link rel="stylesheet" href="{{ asset('css/student-desktop-settings.css') }}?v={{ filemtime(public_path('css/student-desktop-settings.css')) }}">
        @endif
        @unless($isStandaloneScannerRoute)
            <link rel="stylesheet" href="{{ asset('css/student-portal-navigation.css') }}?v={{ filemtime(public_path('css/student-portal-navigation.css')) }}">
        @endunless
        <link rel="stylesheet" href="{{ asset('css/student-portal-master.css') }}?v={{ filemtime(public_path('css/student-portal-master.css')) }}">
        @if(request()->routeIs('home'))
            <link rel="stylesheet" href="{{ asset('css/student-dashboard.css') }}?v={{ filemtime(public_path('css/student-dashboard.css')) }}">
        @endif
        <link rel="stylesheet" href="{{ asset('css/student-motion-craft.css') }}?v={{ filemtime(public_path('css/student-motion-craft.css')) }}">
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
    @endif
</head>

<body @class(['student-portal' => $isStudentUser, 'student-has-page-title' => $studentHasInlinePageHeading, 'teacher-portal' => Auth::user()?->isTeacher(), 'admin-portal' => Auth::user()?->isAdmin(), 'parent-portal' => Auth::user()?->isParent(), 'attendance-verification-page' => View::hasSection('focused-attendance-verification'), 'scanner-page-layout' => $isStandaloneScannerRoute]) @if($isStudentUser && !request()->routeIs('settings')) data-student-layout="simple" @endif>

    @php
        $parentUnreadCount = Auth::user()?->isParent() ? \App\Support\ParentNavigation::unreadCount(Auth::user()) : 0;
    @endphp

    <!-- Skip to main content link for keyboard/screen readers -->
    <a href="#mainContent" class="visually-hidden-focusable skip-to-main-link" style="position: absolute; top: 8px; left: 8px; background: #D4AF37; color: #141010; padding: 8px 16px; border-radius: 8px; font-weight: 700; z-index: 10000; text-decoration: none;">
        Skip to main content
    </a>

    @auth
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <aside class="sidebar" id="sidebar">
        @section('role-sidebar')


        @auth
            @if(Auth::user()->isAdmin())
                @include('layouts.sidebars.admin')
            @elseif(Auth::user()->isTeacher())
                @include('layouts.sidebars.teacher')
            @elseif(Auth::user()->isParent())
                @include('layouts.sidebars.parent')
            @else
                @include('layouts.sidebars.student')
            @endif
        @endauth
        @show
        @if(Auth::user()->isTeacher())
            <button type="button" class="burger-btn sidebar-edge-toggle d-none d-md-flex" id="burgerBtn" onclick="toggleSidebar()" aria-controls="sidebar" aria-expanded="true" aria-label="Collapse sidebar" title="Toggle sidebar">
                <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
            </button>
        @endif
    </aside>
    @endauth

    <div class="main-wrapper">
        <header class="top-header {{ Auth::check() ? '' : 'guest' }}" id="topHeader">
            @auth
                <div class="header-left">
                    @unless(Auth::user()->isTeacher())
                    <!-- Burger -->
                    <button type="button" class="burger-btn d-none d-md-flex" id="burgerBtn" onclick="toggleSidebar()" aria-controls="sidebar" aria-expanded="true" aria-label="Collapse sidebar">
                        <i class="bi bi-layout-sidebar-inset"></i>
                    </button>
                    @endunless
                    @php
                        $isRootPage = request()->routeIs('home') || request()->routeIs('admin.dashboard') || request()->routeIs('teacher.dashboard') || request()->routeIs('parent.dashboard');
                        $fallbackBackUrl = route('home');
                        if (Auth::user()->isAdmin()) {
                            $fallbackBackUrl = match (true) {
                                request()->routeIs('admin.attendance.preview') => route('admin.attendance'),
                                request()->routeIs('admin.students.preview', 'admin.student*') => route('admin.students'),
                                request()->routeIs('admin.teacher.*') => route('admin.teachers'),
                                request()->routeIs('admin.admin.*') => route('admin.admins'),
                                request()->routeIs('admin.subjects.*', 'admin.enrollments.*') => route('admin.subjects'),
                                default => route('admin.dashboard'),
                            };
                        } elseif (Auth::user()->isTeacher()) {
                            $fallbackBackUrl = match (true) {
                                request()->routeIs('teacher.attendance.preview') => request('subject')
                                    ? route('teacher.classroom.show', ['subjectCode' => request('subject'), 'tab' => 'records'])
                                    : route('teacher.classroom.index', ['task' => 'records']),
                                request()->routeIs('teacher.students.preview', 'teacher.student') => route('teacher.students'),
                                request()->routeIs('teacher.classroom.assistant-attendance.*', 'teacher.classroom.show') => route('teacher.classroom.index'),
                                request()->routeIs('teacher.subjects.*') => route('teacher.classroom.index'),
                                request()->routeIs('teacher.excuses.*') => route('teacher.excuses'),
                                default => route('teacher.dashboard'),
                            };
                        } elseif (Auth::user()->isParent()) {
                            $fallbackBackUrl = match (true) {
                                request()->routeIs('parent.excuse*') => route('parent.excuses'),
                                default => route('parent.dashboard'),
                            };
                        } else {
                            $fallbackBackUrl = match (true) {
                                request()->routeIs('excuses.*') => route('excuses'),
                                request()->routeIs('settings.biometrics.*') => route('settings') . '#tab-biometrics',
                                request()->routeIs('student-assistant.sessions.*') => route('student-assistant.classes'),
                                default => route('home'),
                            };
                        }
                    @endphp
                    @if(!$isRootPage)
                        <button type="button" class="mobile-back-btn d-md-none" onclick="(function(fallback){var settingsBack=document.querySelector('[data-teacher-settings][data-settings-view^=&quot;change-&quot;] [data-account-back]:not([hidden]), .student-settings[data-student-view^=&quot;change-&quot;] [data-account-back]:not([hidden])');if(settingsBack){settingsBack.click();return;}var sameOrigin=document.referrer&amp;&amp;document.referrer.indexOf(window.location.origin)===0&amp;&amp;document.referrer.split('#')[0]!==window.location.href.split('#')[0];if(sameOrigin&amp;&amp;window.history.length&gt;1){window.history.back();}else{window.location.href=fallback;}})('{{ $fallbackBackUrl }}')" aria-label="Go back" title="Back">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                    @endif
                    @unless(Auth::user()?->isTeacher())
                    <div class="header-title-container">
                        <div class="header-page-title">
                            @if(View::hasSection('page-title'))
                                @yield('page-title')
                            @elseif(Auth::user()?->isAdmin())
                                {{ \App\Support\AdminNavigation::context(Auth::user(), request()->route()?->getName())['title'] }}
                            @elseif(Auth::user()?->isTeacher())
                                {{ \App\Support\TeacherNavigation::context(request()->route()?->getName())['title'] }}
                            @elseif(Auth::user()?->isParent())
                                {{ \App\Support\ParentNavigation::context(request()->route()?->getName())['title'] }}
                            @else
                                @if(request()->routeIs('home') || request()->routeIs('teacher.dashboard') || request()->routeIs('parent.dashboard'))
                                    Dashboard
                                @elseif(request()->routeIs('student.classes')) My Classes
                                @elseif(request()->routeIs('student.schedule')) Weekly Schedule
                                @elseif(request()->routeIs('student.calendar')) Calendar & Records
                                @elseif(request()->routeIs('student.attendance.calendar')) Attendance Calendar
                                @elseif(request()->routeIs('excuses*')) Excuses
                                @elseif(request()->routeIs('notifications*')) Notifications
                                @elseif(request()->routeIs('profile*')) My Profile
                                @elseif(request()->routeIs('settings.biometrics.*')) Biometrics
                                @elseif(request()->routeIs('settings')) Settings
                                @else
                                    Portal
                                @endif
                            @endif
                        </div>
                        @if(View::hasSection('page-sub') || !Auth::user()?->isStudent())
                        <div class="header-page-sub">@if(View::hasSection('page-sub'))@yield('page-sub')@elseif(Auth::user()?->isAdmin()){{ \App\Support\AdminNavigation::context(Auth::user(), request()->route()?->getName())['group'] }}@elseif(Auth::user()?->isTeacher()){{ \App\Support\TeacherNavigation::context(request()->route()?->getName())['group'] }}@elseif(Auth::user()?->isParent()){{ \App\Support\ParentNavigation::context(request()->route()?->getName())['group'] }}@else{{ now()->format('l, F j, Y') }}@endif</div>
                        @endif
                    </div>
                    @endunless
                </div>

                <div class="header-right d-flex align-items-center gap-2 gap-sm-3">
                    <!-- Modern Universal Search Bar with Quick Action Trigger -->
                    <button type="button"
                            id="headerSearchBar"
                            class="header-search-bar"
                            onclick="if(window.openCommandPalette) window.openCommandPalette();"
                            aria-label="Search pages, students, subjects, or actions (Ctrl + K)"
                            title="Search anything (Ctrl + K)">
                        <i class="bi bi-search header-search-icon" aria-hidden="true"></i>
                        <span class="header-search-text">Search anything...</span>
                        <span class="header-search-shortcut" aria-hidden="true">
                            <kbd class="header-search-key" id="headerSearchModKey">Ctrl</kbd>
                            <span class="header-search-plus">+</span>
                            <kbd class="header-search-key">K</kbd>
                        </span>
                    </button>

                    @php
                        // Hydrate student unread count on initial render so the bell badge never pops in after navigation.
                        $unreadCount = (Auth::check() && Auth::user()->isStudent() && !request()->routeIs('mobile.scan'))
                            ? \App\Models\Notification::where('user_id', Auth::id())->active()->where('is_read', false)->count()
                            : 0;
                    @endphp

                    <div class="dropdown top-notif-dropdown">
                        <button type="button" class="notif-btn position-relative" data-bs-toggle="dropdown" aria-label="Notifications{{ $unreadCount > 0 ? ': '.$unreadCount.' unread' : '' }}" aria-expanded="false" id="topNavNotifBtn">
                            <i class="bi bi-bell-fill" style="font-size:0.95rem;" aria-hidden="true"></i>
                            @if($unreadCount > 0)
                            <span id="topNavNotifBadge" style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:#dc2626;color:white;border-radius:50%;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end mt-2 top-notif-dropdown-menu">
                            <div class="top-notif-header">
                                <span class="top-notif-title">Notifications</span>
                                <div id="topNavNotifHeaderActions" style="display:flex;align-items:center;gap:10px;">
                                    @if($unreadCount > 0)
                                    <span id="topNavNotifCountBadge" style="background:#fef2f2;color:#dc2626;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px;">{{ $unreadCount }} new</span>
                                    <button type="button" onclick="markAllRead()" class="mark-all-read-btn">Mark all read</button>
                                    @endif
                                </div>
                            </div>
                            @php
                                $notifications = collect();
                                $todayNotifs = collect();
                                $olderNotifs = collect();
                                $getNotifVisual = function($type) {
                                    if ($type === 'system_update') {
                                        return [
                                            'icon' => 'bi-rocket-takeoff-fill',
                                            'style' => 'background:rgba(16, 185, 129, 0.18);color:#34d399;border:1px solid rgba(16, 185, 129, 0.3);'
                                        ];
                                    } elseif ($type === 'warning_3') {
                                        return [
                                            'icon' => 'bi-exclamation-octagon-fill',
                                            'style' => 'background:rgba(239, 68, 68, 0.15);color:#f87171;'
                                        ];
                                    } elseif ($type === 'warning_2' || $type === 'warning_consecutive_3') {
                                        return [
                                            'icon' => 'bi-exclamation-triangle-fill',
                                            'style' => 'background:rgba(245, 158, 11, 0.15);color:#fbbf24;'
                                        ];
                                    } elseif ($type === 'custom') {
                                        return [
                                            'icon' => 'bi-info-circle-fill',
                                            'style' => 'background:rgba(59, 130, 246, 0.15);color:#60a5fa;'
                                        ];
                                    } else {
                                        return [
                                            'icon' => 'bi-bell-fill',
                                            'style' => 'background:rgba(207, 164, 111, 0.15);color:#f3e7cd;'
                                        ];
                                    }
                                };
                            @endphp
                            <div id="topNavNotifList" class="top-notif-list">
                                @if($notifications->count() > 0)
                                    @if($todayNotifs->count() > 0)
                                    <div class="notif-group-label">Today</div>
                                    @foreach($todayNotifs as $notif)
                                    @php $v = $getNotifVisual($notif->type); @endphp
                                    <div id="notif-{{ $notif->id }}" style="padding:13px 18px;border-bottom:1px solid rgba(255,255,255,0.05);background:{{ $notif->is_read ? 'transparent' : ($notif->type === 'system_update' ? 'rgba(16, 185, 129, 0.08)' : 'rgba(212, 175, 55, 0.08)') }};transition:background-color 160ms ease;">
                                        <div style="display:flex;gap:10px;align-items:flex-start;">
                                            <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;{{ $v['style'] }}">
                                                <i class="bi {{ $v['icon'] }}"></i>
                                            </div>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:.82rem;color:#f3e7cd;line-height:1.4;">{{ $notif->message }}</div>
                                                <div style="font-size:.72rem;color:rgba(255,255,255,0.5);margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                                @if(!$notif->is_read)
                                                <div style="width:8px;height:8px;border-radius:50%;background:{{ $notif->type === 'system_update' ? '#10b981' : '#f59e0b' }};"></div>
                                                @endif
                                                @if(!Auth::user()->isParent())
                                                <button onclick="archiveNotif({{ $notif->id }}, this)" title="Archive" class="notif-archive-btn">
                                                    <i class="bi bi-archive-fill"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    @endif

                                    @if($olderNotifs->count() > 0)
                                    <div class="notif-group-label">Earlier</div>
                                    @foreach($olderNotifs as $notif)
                                    @php $v = $getNotifVisual($notif->type); @endphp
                                    <div id="notif-{{ $notif->id }}" style="padding:13px 18px;border-bottom:1px solid rgba(255,255,255,0.05);background:{{ $notif->is_read ? 'transparent' : ($notif->type === 'system_update' ? 'rgba(16, 185, 129, 0.08)' : 'rgba(212, 175, 55, 0.08)') }};transition:background-color 160ms ease;">
                                        <div style="display:flex;gap:10px;align-items:flex-start;">
                                            <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;{{ $v['style'] }}">
                                                <i class="bi {{ $v['icon'] }}"></i>
                                            </div>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:.82rem;color:#f3e7cd;line-height:1.4;">{{ $notif->message }}</div>
                                                <div style="font-size:.72rem;color:rgba(255,255,255,0.5);margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                                @if(!$notif->is_read)
                                                <div style="width:8px;height:8px;border-radius:50%;background:{{ $notif->type === 'system_update' ? '#10b981' : '#f59e0b' }};"></div>
                                                @endif
                                                @if(!Auth::user()->isParent())
                                                <button onclick="archiveNotif({{ $notif->id }}, this)" title="Archive" class="notif-archive-btn">
                                                    <i class="bi bi-archive-fill"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    @endif
                                @else
                                <div class="top-notif-empty">
                                    <span class="spinner-border spinner-border-sm text-warning" role="status" aria-hidden="true"></span>
                                    <span>Loading notifications…</span>
                                </div>
                                @endif
                            </div>
                            @if(Auth::user()->isTeacher())
                            <a href="{{ route('teacher.notifications') }}" class="notif-view-all">
                                View all notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            @elseif(Auth::user()->isAdmin())
                            <a href="{{ route('admin.notifications') }}" class="notif-view-all">
                                View all notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            @elseif(Auth::user()->isParent())
                            <a href="{{ route('parent.notifications') }}" class="notif-view-all">
                                View all notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            @else
                            <a href="{{ route('notifications') }}" class="notif-view-all">
                                View all notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            @endif
                        </div>
                    </div>

                    <div class="dropdown">
                        @php
                            $profileImageUrl = Auth::user()->profile_photo_url_with_version;
                        @endphp
                        <a href="#" data-bs-toggle="dropdown" class="header-account-trigger text-decoration-none d-flex align-items-center gap-2" aria-label="Open account menu for {{ Auth::user()->name }}" aria-expanded="false">
                            <img src="{{ $profileImageUrl }}" class="header-profile-img user-avatar-img" alt="">
                            <div class="d-none d-md-block text-start" style="line-height:1.2;">
                                <div style="font-size:0.8rem;font-weight:600;color:#ffffff;">{{ Auth::user()->name }}</div>
                            </div>
                            <i class="bi bi-chevron-down d-none d-md-block" style="font-size:0.7rem;color:rgba(255,255,255,0.75);"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end fb-dropdown mt-2 shadow-lg">
                            <!-- Profile summary -->
                            <div class="fb-profile-header" style="cursor:default;">
                                <img src="{{ $profileImageUrl }}" class="user-avatar-img fb-dropdown-avatar" alt="{{ Auth::user()->name }}">
                                <div class="fb-dropdown-user-info overflow-hidden">
                                    <div class="fb-dropdown-name text-truncate" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</div>
                                    <div class="fb-dropdown-sub text-truncate">{{ Auth::user()->student_number ?: (Auth::user()->employee_id ?: Auth::user()->email) }}</div>
                                    <span class="badge fb-dropdown-role-badge mt-1">{{ ucfirst(Auth::user()->role) }}</span>
                                </div>
                            </div>
                            <div class="fb-dropdown-divider my-2"></div>
                            <a class="fb-dropdown-item" href="{{ route(\App\Support\SettingsNavigation::accountRoute(Auth::user())) }}">
                                <div class="fb-icon-circle fb-icon-settings"><i class="bi bi-gear-fill"></i></div>
                                <span>Settings</span>
                            </a>
                            <form id="globalLogoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                            <a href="#" class="fb-dropdown-item fb-dropdown-logout" id="globalLogoutBtn" role="button">
                                <div class="fb-icon-circle fb-icon-logout"><i class="bi bi-box-arrow-right"></i></div>
                                <span>Log Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="guest-header">
                    <div class="guest-header-icon-wrap"><i class="bi bi-laptop"></i></div>
                    <span class="guest-header-text">{{ config('app.name') }}</span>
                </div>
            @endauth
        </header>

        <main class="main-content {{ Auth::check() ? '' : 'guest-mode' }}" id="mainContent">
            <div class="{{ Auth::check() ? 'p-4' : '' }} page-enter">
                @if(Auth::user()?->isAdmin())
                    @include('partials.admin-page-context')
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- System Toast Container (for websocket notifications & flashes) -->
    <div class="toast-container" id="toastContainer" role="status" aria-live="polite" aria-atomic="false"></div>

    @auth
        <x-modal id="logoutConfirmModal" title="Confirm Log Out" icon="bi-box-arrow-right" maxWidth="sm">
            <p style="margin: 0; color: var(--ds-text-secondary, #D1C5B4); line-height: 1.55;">
                Are you sure you want to log out of your Smart Attendance session?
            </p>
            <x-slot:footer>
                <button type="button" class="btn btn-sm" id="cancelLogoutModalBtn"
                        style="background: rgba(255,255,255,0.06); color: var(--ds-text-secondary, #D1C5B4); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; padding: 8px 16px; font-weight: 600;">
                    Cancel
                </button>
                <button type="button" class="btn btn-sm" id="confirmLogoutModalBtn"
                        style="background: linear-gradient(135deg, #b91c1c, #7a1a1a); color: #fff; border: 1px solid rgba(248, 113, 113, 0.35); border-radius: 10px; padding: 8px 18px; font-weight: 700;">
                    Log Out
                </button>
            </x-slot:footer>
        </x-modal>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script @cspNonce>
        // ── UNIFIED TOAST HELPER ──
        function showToast(message, type = 'success', duration = 4500) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const isStudentToast = document.body.classList.contains('student-portal');
            const colors = {
                success:   { border: '#4ade80', icon: 'check-circle-fill', bg: 'rgba(34,197,94,0.12)' },
                error:     { border: '#f87171', icon: 'x-circle-fill', bg: 'rgba(239,68,68,0.12)' },
                warning:   { border: '#fbbf24', icon: 'exclamation-triangle-fill', bg: 'rgba(245,158,11,0.12)' },
                info:      { border: '#60a5fa', icon: 'info-circle-fill', bg: 'rgba(59,130,246,0.12)' },
                warning_3: { border: '#f87171', icon: 'exclamation-octagon-fill', bg: 'rgba(239,68,68,0.15)' },
                warning_2: { border: '#fbbf24', icon: 'exclamation-triangle-fill', bg: 'rgba(245,158,11,0.15)' },
                custom:    isStudentToast
                    ? { border: '#D4AF37', icon: 'bell-fill', bg: 'rgba(212, 175, 55,0.16)' }
                    : { border: '#cfa46f', icon: 'bell-fill', bg: 'rgba(207,164,111,0.15)' }
            };

            const style = colors[type] || colors.success;
            const toast = document.createElement('div');
            toast.className = isStudentToast ? 'modern-toast is-student-sonner is-entering' : 'modern-toast';
            if (!isStudentToast) {
                toast.style.borderLeft = `4px solid ${style.border}`;
            }

            const iconWrap = document.createElement('div');
            iconWrap.style.cssText = `width:32px;height:32px;border-radius:8px;background:${style.bg};display:flex;align-items:center;justify-content:center;color:${style.border};flex-shrink:0;`;
            const iconEl = document.createElement('i');
            iconEl.className = `bi bi-${style.icon}`;
            iconWrap.appendChild(iconEl);

            const contentEl = document.createElement('div');
            contentEl.className = 'modern-toast-content';
            contentEl.textContent = String(message ?? '');

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'modern-toast-close';
            closeBtn.setAttribute('aria-label', 'Close notification');
            const closeIcon = document.createElement('i');
            closeIcon.className = 'bi bi-x';
            closeBtn.appendChild(closeIcon);

            toast.append(iconWrap, contentEl, closeBtn);
            container.appendChild(toast);

            if (isStudentToast && window.StudentMotion && typeof window.StudentMotion.enhanceToast === 'function') {
                window.StudentMotion.enhanceToast(toast, duration);
            } else {
                closeBtn.addEventListener('click', () => toast.remove());
                if (isStudentToast) {
                    requestAnimationFrame(() => toast.classList.remove('is-entering'));
                }
                setTimeout(() => {
                    if (isStudentToast) {
                        toast.classList.add('is-exiting');
                        setTimeout(() => toast.remove(), 220);
                    } else {
                        toast.style.animation = 'toastSlideOut 0.3s ease forwards';
                        setTimeout(() => toast.remove(), 300);
                    }
                }, duration);
            }
        }

        window.showToast = showToast;
        window.showPremiumToast = function(message, type = 'success', duration = 4500) {
            return showToast(message, type, duration);
        };

        // Auto-show Laravel flash messages once
        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success')) showToast(@json(session('success')), 'success'); @endif
            @if(session('error')) showToast(@json(session('error')), 'error'); @endif
            @if(session('warning')) showToast(@json(session('warning')), 'warning'); @endif
            @if(session('info')) showToast(@json(session('info')), 'info'); @endif
            @if(session('status')) showToast(@json(session('status')), 'info'); @endif
        });
    </script>

    @auth
    <script @cspNonce>
        const sidebar     = document.getElementById('sidebar');
        const burgerBtn   = document.getElementById('burgerBtn');
        const topHeader   = document.getElementById('topHeader');
        const mainContent = document.getElementById('mainContent');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const globalLogoutBtn = document.getElementById('globalLogoutBtn');
        const cancelLogoutModalBtn = document.getElementById('cancelLogoutModalBtn');
        const confirmLogoutModalBtn = document.getElementById('confirmLogoutModalBtn');
        if (globalLogoutBtn) {
            globalLogoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (typeof window.openDsModal === 'function') {
                    window.openDsModal('logoutConfirmModal');
                } else {
                    const form = document.getElementById('globalLogoutForm');
                    if (form) form.submit();
                }
            });
        }
        if (cancelLogoutModalBtn) {
            cancelLogoutModalBtn.addEventListener('click', () => window.closeDsModal?.('logoutConfirmModal'));
        }
        if (confirmLogoutModalBtn) {
            confirmLogoutModalBtn.addEventListener('click', () => {
                const form = document.getElementById('globalLogoutForm');
                if (form) form.submit();
            });
        }

        // Online / Offline connection listeners
        window.addEventListener('offline', () => {
            showToast('You are offline. Cached records and offline features remain accessible.', 'warning', 6000);
        });
        window.addEventListener('online', () => {
            showToast('Connection restored.', 'success', 3000);
        });

        const isMobileViewport = () => window.matchMedia('(max-width: 767.98px)').matches;

        // Restore saved state (only for desktop)
        try {
            if (!isMobileViewport() && localStorage.getItem('sidebarMini') === 'true') applyMini(true, false);
        } catch (error) { /* Storage can be unavailable in private browsing. */ }

        function toggleSidebar() {
            if (isMobileViewport()) {
                const isOpen = sidebar.classList.contains('open');
                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                const isMini = sidebar.classList.contains('collapsed');
                applyMini(!isMini, true);
                try { localStorage.setItem('sidebarMini', String(!isMini)); } catch (error) {}
            }
        }

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        window.addEventListener('resize', () => {
            if (!isMobileViewport() && sidebar.classList.contains('open')) closeSidebar();
        }, { passive: true });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
        });

        function applyMini(mini, animate) {
            if (!animate) {
                const noTrans = 'transition:none!important';
                sidebar.style.cssText     += noTrans;
                topHeader.style.cssText   += noTrans;
                mainContent.style.cssText += noTrans;
            }

            if (mini) {
                sidebar.classList.add('collapsed');
                topHeader.classList.add('mini');
                mainContent.classList.add('mini');
                burgerBtn.classList.add('open');
            } else {
                sidebar.classList.remove('collapsed');
                topHeader.classList.remove('mini');
                mainContent.classList.remove('mini');
                burgerBtn.classList.remove('open');
            }
            burgerBtn.setAttribute('aria-expanded', String(!mini));
            burgerBtn.setAttribute('aria-label', mini ? 'Expand sidebar' : 'Collapse sidebar');

            if (!animate) {
                requestAnimationFrame(() => {
                    sidebar.style.cssText     = '';
                    topHeader.style.cssText   = '';
                    mainContent.style.cssText = '';
                });
            }
        }

        function markAllRead() {
            @if(Auth::user()->isTeacher())
            const markReadUrl = '{{ route("teacher.notifications.read") }}';
            fetch(markReadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => location.reload());
            @elseif(Auth::user()->isAdmin())
            const markReadUrl = '{{ route("admin.notifications.markAllRead") }}';
            fetch(markReadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => location.reload());
            @elseif(Auth::user()->isParent())
            const markReadUrl = '{{ route("parent.notifications.read") }}';
            fetch(markReadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => location.reload());
            @else
            const markReadUrl = '{{ route("notifications.read") }}';
            fetch(markReadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => location.reload());
            @endif
        }

        function archiveNotif(id, btn) {
            const row = document.getElementById('notif-' + id);
            // Animate out
            row.style.transition = 'opacity .2s, transform .2s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(10px)';
            setTimeout(() => {
                @if(Auth::user()->isTeacher())
                const archiveUrl = `/teacher/notifications/${id}/archive`;
                @elseif(Auth::user()->isAdmin())
                const archiveUrl = `/admin/notifications/${id}/archive`;
                @else
                const archiveUrl = `/notifications/${id}/archive`;
                @endif
                fetch(archiveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        row.remove();
                        // Update badge count
                        const badge = document.querySelector('.notif-btn span');
                        if (badge) {
                            const current = parseInt(badge.textContent) || 0;
                            if (current <= 1) badge.remove();
                            else badge.textContent = current - 1;
                        }
                    }
                });
            }, 200);
        }

        // ── Real-Time Notification Bell Poller & WebSocket Sync ──
        let notificationBellRefreshInFlight = false;
        window.refreshNotificationBell = async function() {
            if (notificationBellRefreshInFlight) return;
            notificationBellRefreshInFlight = true;
            const showNotificationLoadError = () => {
                const listEl = document.getElementById('topNavNotifList');
                if (listEl) {
                    listEl.innerHTML = '<div style="text-align:center;padding:32px 20px;color:#94a3b8;font-size:.85rem;">Notifications are unavailable right now.</div>';
                }
            };
            try {
                const res = await fetch('{{ route("notifications.poll") }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                });
                if (!res.ok) {
                    showNotificationLoadError();
                    return;
                }
                const data = await res.json();

                document.querySelectorAll('[data-parent-unread-count]').forEach(badge => {
                    const count = Math.max(0, Number(data.unread_count) || 0);
                    const limit = badge.classList.contains('mbn-badge') ? 9 : 99;
                    badge.hidden = count === 0;
                    badge.textContent = count > limit ? `${limit}+` : String(count);
                    badge.setAttribute('aria-label', `${count} unread alerts`);
                });

                // 1. Update bell badge
                const notifBtn = document.getElementById('topNavNotifBtn') || document.querySelector('.notif-btn');
                if (notifBtn) {
                    notifBtn.setAttribute('aria-label', data.unread_count > 0 ? `Notifications: ${data.unread_count} unread` : 'Notifications');
                    let badge = notifBtn.querySelector('span');
                    if (data.unread_count > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.id = 'topNavNotifBadge';
                            badge.style.cssText = 'position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:#dc2626;color:white;border-radius:50%;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;';
                            notifBtn.appendChild(badge);
                        }
                        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    } else if (badge) {
                        badge.remove();
                    }
                }

                // 2. Update dropdown header badge safely
                const headerActions = document.getElementById('topNavNotifHeaderActions');
                if (headerActions) {
                    headerActions.replaceChildren();
                    if (data.unread_count > 0) {
                        const badgeSpan = document.createElement('span');
                        badgeSpan.id = 'topNavNotifCountBadge';
                        badgeSpan.style.cssText = 'background:#fef2f2;color:#dc2626;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px;';
                        badgeSpan.textContent = `${data.unread_count} new`;

                        const markBtn = document.createElement('button');
                        markBtn.type = 'button';
                        markBtn.className = 'mark-all-read-btn';
                        markBtn.textContent = 'Mark all read';
                        markBtn.addEventListener('click', () => window.markAllRead?.());

                        headerActions.append(badgeSpan, markBtn);
                    }
                }

                // 3. Update dropdown notification list safely via DOM APIs
                const listEl = document.getElementById('topNavNotifList');
                if (listEl && Array.isArray(data.notifications) && data.notifications.length === 0) {
                    listEl.replaceChildren();
                    const emptyBox = document.createElement('div');
                    emptyBox.className = 'top-notif-empty';
                    const emptyIcon = document.createElement('i');
                    emptyIcon.className = 'bi bi-bell-slash';
                    emptyIcon.setAttribute('aria-hidden', 'true');
                    const emptyLabel = document.createElement('span');
                    emptyLabel.textContent = 'No notifications yet';
                    emptyBox.append(emptyIcon, emptyLabel);
                    listEl.appendChild(emptyBox);
                } else if (listEl && Array.isArray(data.notifications)) {
                    const iconMap = {
                        system_update: 'bi-rocket-takeoff-fill',
                        warning_3: 'bi-exclamation-octagon-fill',
                        warning_2: 'bi-exclamation-triangle-fill',
                        warning_consecutive_3: 'bi-exclamation-triangle-fill',
                        custom: 'bi-info-circle-fill'
                    };
                    const colorMap = {
                        system_update: 'background:rgba(16, 185, 129, 0.18);color:#34d399;border:1px solid rgba(16, 185, 129, 0.3);',
                        warning_3: 'background:rgba(239, 68, 68, 0.15);color:#f87171;',
                        warning_2: 'background:rgba(245, 158, 11, 0.15);color:#fbbf24;',
                        warning_consecutive_3: 'background:rgba(245, 158, 11, 0.15);color:#fbbf24;',
                        custom: 'background:rgba(59, 130, 246, 0.15);color:#60a5fa;'
                    };

                    const todayList = data.notifications.filter(n => n.is_today);
                    const olderList = data.notifications.filter(n => !n.is_today);
                    const isParent = {{ Auth::user()->isParent() ? 'true' : 'false' }};
                    const frag = document.createDocumentFragment();

                    const isStudentPortal = document.body.classList.contains('student-portal');
                    const buildNotifNode = (n) => {
                        const row = document.createElement('div');
                        row.id = `notif-${n.id}`;
                        const unreadDefaultBg = isStudentPortal ? 'rgba(122, 26, 26, 0.16)' : 'rgba(212, 175, 55, 0.08)';
                        const bgRow = n.is_read ? 'transparent' : (n.type === 'system_update' ? 'rgba(74, 222, 128, 0.08)' : unreadDefaultBg);
                        row.style.cssText = `padding:12px 16px;border-bottom:1px solid rgba(255, 255, 255,0.06);background:${bgRow};transition:background-color .16s ease;`;

                        const inner = document.createElement('div');
                        inner.style.cssText = 'display:flex;gap:10px;align-items:flex-start;';

                        const studentIconColor = n.type === 'system_update' ? '#4ADE80' : (n.type === 'excuse' ? '#FBBF24' : (n.type === 'attendance' ? '#F87171' : '#D4AF37'));
                        const defaultIconStyle = isStudentPortal
                            ? `background:transparent;color:${studentIconColor};`
                            : 'background:rgba(207, 164, 111, 0.15);color:#f3e7cd;';
                        const badgeWrap = document.createElement('div');
                        badgeWrap.style.cssText = isStudentPortal
                            ? `width:22px;height:22px;flex-shrink:0;display:flex;align-items:center;justify-content:center;${defaultIconStyle}`
                            : `width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;${colorMap[n.type] || defaultIconStyle}`;
                        const badgeIcon = document.createElement('i');
                        badgeIcon.className = `bi ${iconMap[n.type] || 'bi-bell-fill'}`;
                        badgeWrap.appendChild(badgeIcon);

                        const bodyCol = document.createElement('div');
                        bodyCol.style.cssText = 'flex:1;min-width:0;';
                        const msgEl = document.createElement('div');
                        msgEl.style.cssText = `font-size:.82rem;color:${isStudentPortal ? '#FAF8F5' : '#f3e7cd'};line-height:1.45;`;
                        msgEl.textContent = String(n.message ?? '');
                        const timeEl = document.createElement('div');
                        timeEl.style.cssText = `font-size:.73rem;color:${isStudentPortal ? '#ACA099' : 'rgba(255,255,255,0.5)'};margin-top:4px;`;
                        timeEl.textContent = String(n.created_at_human ?? '');
                        bodyCol.append(msgEl, timeEl);

                        const rightCol = document.createElement('div');
                        rightCol.style.cssText = 'display:flex;align-items:center;gap:6px;flex-shrink:0;';
                        if (!n.is_read) {
                            const dot = document.createElement('div');
                            const dotColor = n.type === 'system_update' ? '#4ADE80' : (isStudentPortal ? '#D4AF37' : '#f59e0b');
                            dot.style.cssText = `width:7px;height:7px;border-radius:50%;background:${dotColor};`;
                            rightCol.appendChild(dot);
                        }
                        if (!isParent) {
                            const archBtn = document.createElement('button');
                            archBtn.type = 'button';
                            archBtn.title = 'Archive';
                            archBtn.className = 'notif-archive-btn';
                            const archIcon = document.createElement('i');
                            archIcon.className = 'bi bi-archive-fill';
                            archBtn.appendChild(archIcon);
                            archBtn.addEventListener('click', () => window.archiveNotif?.(n.id, archBtn));
                            rightCol.appendChild(archBtn);
                        }

                        inner.append(badgeWrap, bodyCol, rightCol);
                        row.appendChild(inner);
                        return row;
                    };

                    if (todayList.length > 0) {
                        const group = document.createElement('div');
                        group.className = 'notif-group-label';
                        group.textContent = 'Today';
                        frag.appendChild(group);
                        todayList.forEach(n => frag.appendChild(buildNotifNode(n)));
                    }
                    if (olderList.length > 0) {
                        const group = document.createElement('div');
                        group.className = 'notif-group-label';
                        group.textContent = 'Earlier';
                        frag.appendChild(group);
                        olderList.forEach(n => frag.appendChild(buildNotifNode(n)));
                    }

                    listEl.replaceChildren(frag);
                }
            } catch(e) {
                showNotificationLoadError();
            } finally {
                notificationBellRefreshInFlight = false;
            }
        };

        // Load the bell after the first render, and refresh immediately when opened.
        const notificationDropdown = document.getElementById('topNavNotifBtn')?.closest('.dropdown');
        if (notificationDropdown) {
            notificationDropdown.addEventListener('show.bs.dropdown', () => window.refreshNotificationBell());
        }
        const scheduleNotificationRefresh = () => {
            const refresh = () => window.refreshNotificationBell();
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(refresh, { timeout: 1200 });
            } else {
                window.setTimeout(refresh, 500);
            }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', scheduleNotificationRefresh, { once: true });
        } else {
            scheduleNotificationRefresh();
        }

        // Keep the fallback poll light; live updates are also delivered over WebSockets.
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                window.refreshNotificationBell();
            }
        }, 120000);
        window.addEventListener('focus', () => window.refreshNotificationBell());
    </script>
    @endauth

    <!-- Premium Drawer -->
    <div class="premium-drawer-overlay" id="globalDrawerOverlay" onclick="closeDrawer()"></div>
    <div class="premium-drawer" id="globalDrawer">
        <div class="premium-drawer-header">
            <h3 class="premium-drawer-title" id="globalDrawerTitle">Drawer</h3>
            <button class="premium-drawer-close" onclick="closeDrawer()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="premium-drawer-body" id="globalDrawerBody"></div>
        <div class="premium-drawer-footer" id="globalDrawerFooter">
            <button class="adm-btn adm-btn-ghost" onclick="closeDrawer()">Cancel</button>
            <button class="adm-btn adm-btn-primary" id="globalDrawerSaveBtn">Confirm</button>
        </div>
    </div>
    <script @cspNonce>
    function openDrawer(title, contentHtml, saveCallback = null, saveText = 'Confirm') {
        document.getElementById('globalDrawerTitle').innerText = title;
        document.getElementById('globalDrawerBody').innerHTML = contentHtml;
        const saveBtn = document.getElementById('globalDrawerSaveBtn');
        if (saveCallback) {
            saveBtn.style.display = 'inline-flex';
            saveBtn.innerText = saveText;
            saveBtn.onclick = () => { saveCallback(); };
        } else {
            saveBtn.style.display = 'none';
        }
        document.getElementById('globalDrawerOverlay').classList.add('show');
        document.getElementById('globalDrawer').classList.add('open');
    }
    function closeDrawer() {
        document.getElementById('globalDrawerOverlay').classList.remove('show');
        document.getElementById('globalDrawer').classList.remove('open');
    }
    </script>

    @auth
        @if(Auth::user()->isStudent() && !request()->routeIs('mobile.scan'))
            @include('components.mobile.install-prompt')
        @endif
        @unless(request()->routeIs('mobile.scan'))
            @include('components.mobile.role-bottom-nav')
        @endunless

        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher() || Auth::user()->isParent())
        <!-- More Bottom Sheet -->
        <div class="more-sheet-overlay d-md-none" id="moreSheetOverlay" onclick="closeMoreSheet()"></div>
        <div class="more-sheet d-md-none {{ Auth::user()->isAdmin() ? 'admin-navigation-sheet' : '' }}" id="moreSheet" role="dialog" aria-modal="true" aria-labelledby="moreSheetTitle" aria-hidden="true" inert>
            <div class="more-sheet-handle"></div>
            <div class="more-sheet-header">
                <span class="more-sheet-title" id="moreSheetTitle">{{ Auth::user()->isAdmin() ? 'All admin pages' : (Auth::user()->isParent() ? 'Parent pages' : 'Teacher pages') }}</span>
                <button type="button" class="more-sheet-close" id="moreSheetCloseBtn" onclick="closeMoreSheet()" aria-label="Close More Menu">
                    <i class="bi bi-x-lg" style="pointer-events:none;"></i>
                </button>
            </div>
            <div class="more-sheet-grid" id="moreSheetContent">
                @if(Auth::user()->isAdmin())
                    @include('partials.admin-workspace-navigation', ['navigationId' => 'adminMobile', 'mobile' => true])
                @elseif(Auth::user()->isTeacher())
                    @include('partials.teacher-navigation', ['mobile' => true])
                @else
                    @include('partials.parent-navigation', ['mobile' => true])
                @endif
            </div>
        </div>
        <script @cspNonce>
        let moreSheetReturnFocus = null;
        let moreSheetPreviousOverflow = '';
        window.openMoreSheet = function() {
            var overlay = document.getElementById('moreSheetOverlay');
            var sheet = document.getElementById('moreSheet');
            if (!sheet || sheet.classList.contains('open')) return;
            moreSheetReturnFocus = document.activeElement;
            moreSheetPreviousOverflow = document.body.style.overflow;
            sheet.inert = false;
            sheet.setAttribute('aria-hidden', 'false');
            if (sheet) {
                sheet.style.transform = '';
                sheet.style.transition = '';
                sheet.classList.add('open');
            }
            if (overlay) {
                overlay.classList.add('show');
            }
            document.body.style.overflow = 'hidden';

            // Highlight More button with top jewel active style like Home
            document.querySelectorAll('.mobile-bottom-nav .mbn-item').forEach(function(item) {
                if (item.classList.contains('active')) {
                    item.setAttribute('data-was-active', 'true');
                    if (!item.classList.contains('mbn-item-more')) item.classList.remove('active');
                }
            });
            document.querySelectorAll('.mbn-item-more').forEach(function(btn) {
                btn.classList.add('active', 'is-open');
                btn.setAttribute('aria-expanded', 'true');
            });
            document.getElementById('moreSheetCloseBtn')?.focus({ preventScroll: true });
        };

        window.closeMoreSheet = function() {
            var overlay = document.getElementById('moreSheetOverlay');
            var sheet = document.getElementById('moreSheet');
            if (!sheet || !sheet.classList.contains('open')) return;
            if (overlay) {
                overlay.classList.remove('show');
            }
            if (sheet) {
                sheet.classList.remove('open');
                sheet.style.transform = '';
                if (sheet.contains(document.activeElement)) moreSheetReturnFocus?.focus({ preventScroll: true });
                sheet.inert = true;
                sheet.setAttribute('aria-hidden', 'true');
            }
            document.body.style.overflow = moreSheetPreviousOverflow;

            // Restore previous active tab
            document.querySelectorAll('.mbn-item-more').forEach(function(btn) {
                btn.classList.remove('active', 'is-open');
                btn.setAttribute('aria-expanded', 'false');
            });
            document.querySelectorAll('.mobile-bottom-nav .mbn-item[data-was-active="true"]').forEach(function(item) {
                item.classList.add('active');
                item.removeAttribute('data-was-active');
            });
        };

        document.addEventListener('keydown', function(event) {
            const sheet = document.getElementById('moreSheet');
            if (!sheet?.classList.contains('open')) return;
            if (event.key === 'Escape') { event.preventDefault(); window.closeMoreSheet(); return; }
            if (event.key !== 'Tab') return;
            const items = Array.from(sheet.querySelectorAll('a[href], button:not([disabled]), [tabindex="0"]')).filter(item => item.getClientRects().length);
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        });

        // Fallback listener for any more buttons and close buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.mbn-item-more, [data-action="open-more-sheet"]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.openMoreSheet();
                });
            });

            document.querySelectorAll('.more-sheet-close, #moreSheetCloseBtn, [data-action="close-more-sheet"]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.closeMoreSheet();
                });
                btn.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.closeMoreSheet();
                });
            });

            var overlay = document.getElementById('moreSheetOverlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.closeMoreSheet();
                });
                overlay.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    window.closeMoreSheet();
                });
            }

            // Swipe-down to dismiss
            var sheet = document.getElementById('moreSheet');
            if (!sheet) return;
            var startY = 0, currentY = 0, isDragging = false;
            sheet.addEventListener('touchstart', function(e) {
                if (e.target.closest('.more-sheet-close, #moreSheetCloseBtn, button, a')) return;
                if (sheet.scrollTop > 0) return;
                startY = e.touches[0].clientY;
                currentY = startY;
                isDragging = true;
            }, { passive: true });

            sheet.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                currentY = e.touches[0].clientY;
                var diff = currentY - startY;
                if (diff > 0) {
                    sheet.style.transform = 'translateY(' + diff + 'px)';
                    sheet.style.transition = 'none';
                }
            }, { passive: true });

            sheet.addEventListener('touchend', function(e) {
                if (!isDragging) return;
                isDragging = false;
                var diff = currentY - startY;
                sheet.style.transition = '';
                if (diff > 80) {
                    window.closeMoreSheet();
                } else {
                    sheet.style.transform = '';
                }
            });

            // Ensure body scrolling is never stuck
            const anyOpenModal = document.querySelector('.modal.show, .premium-drawer.open, .more-sheet.open, .sidebar.open');
            if (!anyOpenModal) {
                document.body.style.overflow = '';
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                if (typeof window.closeMoreSheet === 'function') {
                    window.closeMoreSheet();
                }
                const anyModal = document.querySelector('.modal.show, .premium-drawer.open');
                if (!anyModal) {
                    document.body.style.overflow = '';
                }
            }
        });
        </script>
        @else
            @unless(request()->routeIs('mobile.scan'))
                @include('partials.student-scanner-launcher')
            @endunless
            @include('partials.student-presence-guardian')
        @endif
    @endauth
    @auth
        <x-command-palette :parent-unread-count="$parentUnreadCount" />
    @endauth

    @yield('scripts')

    <!-- Global Loading Overlay -->
    <div id="global-loader" style="display: none; position: fixed; inset: 0; background: rgba(20, 14, 14, 0.88); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; flex-direction: column;">
        <div style="width: 56px; height: 56px; border: 3px solid rgba(212,175,55,0.15); border-top-color: #cfa46f; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
        <p style="color: #cfa46f; margin-top: 16px; font-weight: 600; font-size: 0.9rem; letter-spacing: 0.02em;">Processing...</p>
    </div>

    <script @cspNonce>
        // ── Native Micro-Haptics Engine ──
        window.triggerHaptic = function(type = 'light') {
            if (!('vibrate' in navigator) || !navigator.vibrate) return;
            try {
                if (type === 'light') navigator.vibrate(8);
                else if (type === 'medium') navigator.vibrate(18);
                else if (type === 'heavy') navigator.vibrate(30);
                else if (type === 'success') navigator.vibrate([12, 30, 20]);
                else if (type === 'error') navigator.vibrate([25, 40, 25]);
            } catch(e) {}
        };


        // Auto-bind micro-haptics on taps
        document.addEventListener('click', function(e) {
            const el = e.target.closest('button, .btn, .mbn-item, .scal-tile, .quick-action-btn, .nav-link, .pwa-btn-install, .pwa-btn-update-apply, .stab, .dropdown-item, .more-sheet-item');
            if (el) {
                window.triggerHaptic('light');
            }
        }, { passive: true });

        // ── Seamless Page Transitions ──
        // Keep the current document visible until navigation actually replaces it.
        // Slow, cancelled, and background-tab navigation must never hide content.
        window.addEventListener('pageshow', function() {
            const pageEnterEl = document.querySelector('.page-enter');
            if (pageEnterEl) {
                pageEnterEl.classList.remove('page-exit');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form:not([data-no-loader])').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (this.checkValidity() && !this.getAttribute('target') && this.method.toUpperCase() === 'POST') {
                        if (this.dataset.submitting === 'true') {
                            e.preventDefault();
                            return false;
                        }
                        this.dataset.submitting = 'true';
                        const submitBtn = this.querySelector('button[type="submit"]:not([disabled])');
                        if (submitBtn) {
                            submitBtn.style.opacity = '0.7';
                            submitBtn.style.pointerEvents = 'none';
                        }
                        const loader = document.getElementById('global-loader');
                        if (loader) loader.style.display = 'flex';
                    }
                });
            });

            // ── Modern Header Search Bar Keyboard Shortcuts & UX ──
            const searchBar = document.getElementById('headerSearchBar');
            const modKey = document.getElementById('headerSearchModKey');
            const isAppleDevice = /(Mac|iPhone|iPod|iPad)/i.test(navigator.platform || navigator.userAgent);

            if (modKey && isAppleDevice) {
                modKey.textContent = '⌘';
            }

            if (searchBar) {
                searchBar.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (window.openCommandPalette) window.openCommandPalette();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        searchBar.blur();
                        if (window.closeCommandPalette) window.closeCommandPalette();
                    }
                });
            }

            // Global Ctrl+K / Cmd+K visual highlight on header search bar
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                    if (searchBar) {
                        searchBar.classList.add('active');
                        setTimeout(function() { searchBar.classList.remove('active'); }, 500);
                    }
                }
            });

            // ── Viewport Height Lock – prevents status bar / notification shade from shifting layout ──
            (function() {
                var docEl = document.documentElement;
                var lockedHeight = window.innerHeight || docEl.clientHeight;
                var lockedWidth  = window.innerWidth  || docEl.clientWidth;
                var lastOrientation = (window.screen && window.screen.orientation && window.screen.orientation.type) ?
                    (window.screen.orientation.type.includes('landscape') ? 'landscape' : 'portrait') :
                    (window.innerWidth > window.innerHeight ? 'landscape' : 'portrait');

                function lockSafeAreas() {
                    try {
                        var probe = document.createElement('div');
                        probe.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px);padding-left:env(safe-area-inset-left,0px);padding-right:env(safe-area-inset-right,0px);visibility:hidden;pointer-events:none;z-index:-1;';
                        docEl.appendChild(probe);
                        var cs = window.getComputedStyle(probe);
                        var top = parseFloat(cs.height) || 0;
                        var bottom = parseFloat(cs.paddingBottom) || 0;
                        var left = parseFloat(cs.paddingLeft) || 0;
                        var right = parseFloat(cs.paddingRight) || 0;
                        docEl.removeChild(probe);
                        if (top > 0) docEl.style.setProperty('--sat', top + 'px');
                        if (bottom > 0) docEl.style.setProperty('--sab', bottom + 'px');
                        if (left > 0) docEl.style.setProperty('--sal', left + 'px');
                        if (right > 0) docEl.style.setProperty('--sar', right + 'px');
                    } catch (e) {}
                }

                function applyLock() {
                    docEl.style.setProperty('--app-height', lockedHeight + 'px');
                    docEl.style.setProperty('--app-width',  lockedWidth  + 'px');
                    lockSafeAreas();
                }

                function onOrientationChange() {
                    setTimeout(function() {
                        var newOrientation = (window.screen && window.screen.orientation && window.screen.orientation.type) ?
                            (window.screen.orientation.type.includes('landscape') ? 'landscape' : 'portrait') :
                            (window.innerWidth > window.innerHeight ? 'landscape' : 'portrait');
                        if (newOrientation !== lastOrientation) {
                            lastOrientation = newOrientation;
                            lockedHeight = window.innerHeight || docEl.clientHeight;
                            lockedWidth  = window.innerWidth  || docEl.clientWidth;
                            applyLock();
                        }
                    }, 250);
                }

                function isTextEntryFocused() {
                    var active = document.activeElement;
                    if (!active) return false;
                    return active.matches('textarea, select, [contenteditable="true"], input:not([type="button"]):not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="reset"])');
                }

                function onResize() {
                    var vv = window.visualViewport;
                    var rawHeight = vv ? vv.height : window.innerHeight;
                    var isKeyboard = isTextEntryFocused() && rawHeight < (lockedHeight - 150);
                    document.body.classList.toggle('keyboard-open', isKeyboard);
                }

                var savedScrollY = 0;
                function trackScroll() {
                    savedScrollY = window.scrollY || docEl.scrollTop || 0;
                }
                window.addEventListener('scroll', trackScroll, { passive: true });

                window.addEventListener('orientationchange', onOrientationChange, { passive: true });
                if (window.visualViewport) {
                    window.visualViewport.addEventListener('resize', onResize, { passive: true });
                }
                window.addEventListener('resize', onResize, { passive: true });

                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        if (savedScrollY > 0 && Math.abs((window.scrollY || docEl.scrollTop || 0) - savedScrollY) > 2) {
                            window.scrollTo({ top: savedScrollY, behavior: 'instant' });
                        }
                        onResize();
                    } else {
                        trackScroll();
                    }
                }, { passive: true });

                window.addEventListener('focus', function() {
                    if (savedScrollY > 0 && Math.abs((window.scrollY || docEl.scrollTop || 0) - savedScrollY) > 2) {
                        window.scrollTo({ top: savedScrollY, behavior: 'instant' });
                    }
                }, { passive: true });

                applyLock();
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', applyLock);
                }
            })();

        });
    </script>


    @if(Auth::check() && Auth::user()->isTeacher())
    <script src="/js/offline-attendance.js?v={{ filemtime(public_path('js/offline-attendance.js')) }}"></script>
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.OfflineAttendance) {
                OfflineAttendance.init({
                    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
                    teacherId: {{ Auth::id() }},
                });
            }
            // Listen for Background Sync messages from the Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.addEventListener('message', function(event) {
                    if (event.data && event.data.type === 'OFFLINE_ATTENDANCE_SYNC') {
                        if (window.OfflineAttendance) {
                            OfflineAttendance.syncNow();
                        }
                    }
                });
            }
        });
    </script>
    @endif

    @stack('scripts')
    @if(Auth::user()?->isParent() && !request()->routeIs('parent.*.report'))
        <script src="{{ asset('js/parent-workspace.js') }}?v={{ filemtime(public_path('js/parent-workspace.js')) }}" defer></script>
    @endif
    @if(Auth::user()?->isAdmin() && !request()->routeIs('admin.*.pdf'))
        <script src="{{ asset('js/admin-workspace.js') }}?v={{ filemtime(public_path('js/admin-workspace.js')) }}" defer></script>
    @endif
    @if(Auth::user()?->isStudent())
        <script @cspNonce src="{{ asset('js/student-motion-craft.js') }}?v={{ filemtime(public_path('js/student-motion-craft.js')) }}" defer></script>
    @endif
    @include('partials.pwa-body')
</body>
</html>
@endif
