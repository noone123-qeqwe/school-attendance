<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#110A0A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>{{ config('app.name') }}</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}?v={{ filemtime(public_path('css/design-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-native.css') }}?v={{ filemtime(public_path('css/mobile-native.css')) }}">
    
    @include('partials.pwa-tags')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="mobile-native-app">
    
    <!-- Mobile App Header -->
    <header class="mobile-app-header" id="mobileHeader">
        <div class="mobile-header-content">
            @hasSection('header-left')
                @yield('header-left')
            @else
                <button class="mobile-header-btn" onclick="openMobileMenu()" aria-label="Menu">
                    <i class="bi bi-list"></i>
                </button>
            @endif
            
            <div class="mobile-header-title">
                @hasSection('page-title')
                    @yield('page-title')
                @else
                    {{ config('app.name') }}
                @endif
            </div>
            
            @hasSection('header-right')
                @yield('header-right')
            @else
                <button class="mobile-header-btn" onclick="openNotifications()" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    @if(isset($unreadNotifications) && $unreadNotifications > 0)
                        <span class="mobile-badge">{{ $unreadNotifications }}</span>
                    @endif
                </button>
            @endif
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="mobile-app-content" id="mobileContent">
        @if(session('success'))
        <div class="mobile-toast mobile-toast-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="mobile-toast mobile-toast-error">
            <i class="bi bi-x-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        @yield('content')
    </main>

    <!-- Bottom Navigation -->
    @auth
    <nav class="mobile-bottom-nav" id="mobileBottomNav">
        @if(Auth::user()->isStudent())
            <a href="{{ route('home') }}" class="mobile-nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="bi bi-house-fill"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('attendance.records') }}" class="mobile-nav-item {{ request()->routeIs('attendance.records') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Attendance</span>
            </a>
            <a href="{{ route('qr.scan') }}" class="mobile-nav-item mobile-nav-center {{ request()->routeIs('qr.scan') ? 'active' : '' }}">
                <i class="bi bi-qr-code-scan"></i>
                <span>Scan</span>
            </a>
            <a href="{{ route('student.classes') }}" class="mobile-nav-item {{ request()->routeIs('student.classes') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span>Classes</span>
            </a>
            <a href="{{ route('settings') }}" class="mobile-nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
                <i class="bi bi-person-fill"></i>
                <span>Profile</span>
            </a>
        @elseif(Auth::user()->isTeacher())
            <a href="{{ route('teacher.dashboard') }}" class="mobile-nav-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-fill"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('teacher.classes') }}" class="mobile-nav-item {{ request()->routeIs('teacher.classes*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span>Classes</span>
            </a>
            <a href="{{ route('teacher.attendance.session') }}" class="mobile-nav-item mobile-nav-center {{ request()->routeIs('teacher.attendance.session') ? 'active' : '' }}">
                <i class="bi bi-qr-code"></i>
                <span>Session</span>
            </a>
            <a href="{{ route('teacher.students') }}" class="mobile-nav-item {{ request()->routeIs('teacher.students*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
            </a>
            <a href="{{ route('teacher.profile') }}" class="mobile-nav-item {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
                <i class="bi bi-person-fill"></i>
                <span>Profile</span>
            </a>
        @elseif(Auth::user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="mobile-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.students.index') }}" class="mobile-nav-item {{ request()->routeIs('admin.students*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
            </a>
            <a href="{{ route('admin.subjects.index') }}" class="mobile-nav-item mobile-nav-center {{ request()->routeIs('admin.subjects*') ? 'active' : '' }}">
                <i class="bi bi-journal-plus"></i>
                <span>Subjects</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="mobile-nav-item {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i>
                <span>Reports</span>
            </a>
            <a href="{{ route('admin.profile') }}" class="mobile-nav-item {{ request()->routeIs('admin.profile') || request()->routeIs('admin.settings*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        @elseif(Auth::user()->isParent())
            <a href="{{ route('parent.dashboard') }}" class="mobile-nav-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-fill"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('parent.children') }}" class="mobile-nav-item {{ request()->routeIs('parent.children') || request()->routeIs('parent.child') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i>
                <span>Children</span>
            </a>
            <a href="{{ route('parent.link.form') }}" class="mobile-nav-item mobile-nav-center">
                <i class="bi bi-person-plus-fill"></i>
                <span>Link</span>
            </a>
            <a href="{{ route('parent.notifications') }}" class="mobile-nav-item {{ request()->routeIs('parent.notifications') ? 'active' : '' }}">
                <i class="bi bi-bell-fill"></i>
                <span>Alerts</span>
            </a>
            <a href="{{ route('parent.profile') }}" class="mobile-nav-item {{ request()->routeIs('parent.profile') ? 'active' : '' }}">
                <i class="bi bi-person-fill"></i>
                <span>Profile</span>
            </a>
        @endif
    </nav>
    @endauth

    <!-- Mobile Menu Drawer -->
    @auth
    <div class="mobile-drawer" id="mobileMenuDrawer">
        <div class="mobile-drawer-overlay" onclick="closeMobileMenu()"></div>
        <div class="mobile-drawer-content">
            <div class="mobile-drawer-header">
                <div class="mobile-drawer-user">
                    <div class="mobile-user-avatar">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="mobile-user-info">
                        <div class="mobile-user-name">{{ Auth::user()->name }}</div>
                        <div class="mobile-user-role">
                            @if(Auth::user()->isStudent())
                                {{ Auth::user()->course }} · Year {{ Auth::user()->year_level }}
                            @elseif(Auth::user()->isTeacher())
                                Teacher · {{ Auth::user()->employee_id }}
                            @elseif(Auth::user()->isAdmin())
                                Administrator
                            @elseif(Auth::user()->isParent())
                                Parent/Guardian
                            @endif
                        </div>
                    </div>
                </div>
                <button class="mobile-drawer-close" onclick="closeMobileMenu()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="mobile-drawer-menu">
                @if(Auth::user()->isStudent())
                    <a href="{{ route('home') }}" class="mobile-menu-item {{ request()->routeIs('home') ? 'active' : '' }}">
                        <i class="bi bi-house-fill"></i>
                        <span>Home</span>
                    </a>
                    <a href="{{ route('attendance.records') }}" class="mobile-menu-item {{ request()->routeIs('attendance.records') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check-fill"></i>
                        <span>My Attendance</span>
                    </a>
                    <a href="{{ route('qr.scan') }}" class="mobile-menu-item {{ request()->routeIs('qr.scan') ? 'active' : '' }}">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>Scan QR Code</span>
                    </a>
                    <a href="{{ route('student.classes') }}" class="mobile-menu-item {{ request()->routeIs('student.classes') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>
                        <span>My Classes</span>
                    </a>
                    <a href="{{ route('excuses') }}" class="mobile-menu-item {{ request()->routeIs('excuses') ? 'active' : '' }}">
                        <i class="bi bi-file-text-fill"></i>
                        <span>Excuses & Absences</span>
                    </a>
                    <a href="{{ route('notifications') }}" class="mobile-menu-item {{ request()->routeIs('notifications') ? 'active' : '' }}">
                        <i class="bi bi-bell-fill"></i>
                        <span>Notifications</span>
                    </a>
                    <a href="{{ route('settings') }}" class="mobile-menu-item {{ request()->routeIs('settings') ? 'active' : '' }}">
                        <i class="bi bi-gear-fill"></i>
                        <span>Settings</span>
                    </a>
                @endif

                <div class="mobile-menu-divider"></div>

                <form action="{{ route('logout') }}" method="POST" class="mobile-menu-item-form">
                    @csrf
                    <button type="submit" class="mobile-menu-item mobile-menu-item-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>

            <div class="mobile-drawer-footer">
                <div class="mobile-app-version">
                    {{ config('app.name') }} · v1.0.0
                </div>
            </div>
        </div>
    </div>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mobile Menu Functions
        function openMobileMenu() {
            document.getElementById('mobileMenuDrawer').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            document.getElementById('mobileMenuDrawer').classList.remove('active');
            document.body.style.overflow = '';
        }

        function openNotifications() {
            @auth
            window.location.href = '{{ route("notifications") }}';
            @endauth
        }

        // Auto-hide toasts
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.mobile-toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.style.animation = 'slideOut 0.3s ease forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            });

            // Handle pull-to-refresh indicator
            let startY = 0;
            let currentY = 0;
            const content = document.getElementById('mobileContent');
            
            content.addEventListener('touchstart', (e) => {
                if (content.scrollTop === 0) {
                    startY = e.touches[0].pageY;
                }
            });

            content.addEventListener('touchmove', (e) => {
                if (startY > 0) {
                    currentY = e.touches[0].pageY;
                    const diff = currentY - startY;
                    if (diff > 0 && diff < 100) {
                        e.preventDefault();
                    }
                }
            });

            content.addEventListener('touchend', () => {
                const diff = currentY - startY;
                if (diff > 80) {
                    location.reload();
                }
                startY = 0;
                currentY = 0;
            });
        });

        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>

    @stack('scripts')
</body>
</html>
