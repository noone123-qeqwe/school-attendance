<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} | @yield('portal-title', 'Student Portal')</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}?v={{ filemtime(public_path('css/design-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/premium.css') }}?v={{ filemtime(public_path('css/premium.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-enterprise.css') }}?v={{ filemtime(public_path('css/dashboard-enterprise.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-enterprise.css') }}?v={{ filemtime(public_path('css/mobile-enterprise.css')) }}">
    @auth
        @if(Auth::user()->isAdmin())
            <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ filemtime(public_path('css/admin-theme.css')) }}">
        @endif
    @endauth
    <link rel="manifest" href="/manifest.json">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body>

    <!-- Skip to main content link for keyboard/screen readers -->
    <a href="#mainContent" class="visually-hidden-focusable" style="position: absolute; top: 8px; left: 8px; background: #cfa46f; color: #110A0A; padding: 8px 16px; border-radius: 8px; font-weight: 700; z-index: 10000; text-decoration: none;">
        Skip to main content
    </a>

    @auth
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <aside class="sidebar" id="sidebar">
        <div class="d-md-none" style="padding: 16px 16px 0 16px; display: flex; align-items: center;">
            <button onclick="closeSidebar()" class="btn btn-sm" style="color: #cfa46f; background: rgba(207,164,111,0.1); border: 1px solid rgba(207,164,111,0.2); border-radius: 8px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-chevron-left"></i> Back
            </button>
        </div>
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
    </aside>
    @endauth

    <div class="main-wrapper">
        <header class="top-header {{ Auth::check() ? '' : 'guest' }}" id="topHeader">
            @auth
                <div class="header-left">
                    <!-- Burger -->
                    <button class="burger-btn d-none d-md-flex" id="burgerBtn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                        <i class="bi bi-layout-sidebar-inset"></i>
                    </button>
                    <div>
                        <div class="header-page-title">
                            @hasSection('page-title')
                                @yield('page-title')
                            @else
                                @if(request()->routeIs('home') || request()->routeIs('admin.dashboard') || request()->routeIs('teacher.dashboard') || request()->routeIs('parent.dashboard'))
                                    Dashboard
                                @elseif(request()->routeIs('student.classes')) My Classes
                                @elseif(request()->routeIs('settings')) Settings
                                @else
                                    Portal
                                @endif
                            @endif
                        </div>
                        <div class="header-page-sub">@hasSection('page-sub')@yield('page-sub')@else{{ now()->format('l, F j, Y') }}@endif</div>
                    </div>
                </div>

                <div class="header-right">
                    @php
                        if (Auth::user()->isParent()) {
                            $childIds = Auth::user()->children()->pluck('users.id');
                            $unreadCount = \App\Models\Notification::whereIn('user_id', $childIds)->where('is_read', false)->count();
                        } else {
                            $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count();
                        }
                    @endphp

                    <div class="dropdown">
                        <div class="notif-btn position-relative" data-bs-toggle="dropdown" style="cursor:pointer;">
                            <i class="bi bi-bell-fill" style="font-size:0.95rem;"></i>
                            @if($unreadCount > 0)
                            <span style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:#dc2626;color:white;border-radius:50%;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                            @endif
                        </div>
                        <div class="dropdown-menu dropdown-menu-end mt-2" style="width:340px;max-width:calc(100vw - 32px);border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:rgba(25,15,15,0.95);backdrop-filter:blur(16px);box-shadow:0 20px 60px rgba(0,0,0,0.5);padding:0;overflow:hidden;">
                            <div style="padding:14px 18px;border-bottom:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:space-between;">
                                <span style="font-size:.9rem;font-weight:700;color:#f3e7cd;">Notifications</span>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    @if($unreadCount > 0)
                                    <span style="background:#fef2f2;color:#dc2626;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px;">{{ $unreadCount }} new</span>
                                    <button onclick="markAllRead()" class="mark-all-read-btn">Mark all read</button>
                                    @endif
                                </div>
                            </div>
                            @php
                                if (Auth::user()->isParent()) {
                                    $notifications = \App\Models\Notification::with(['user', 'sender', 'subject'])
                                        ->whereIn('user_id', $childIds)
                                        ->active()
                                        ->orderBy('created_at','desc')->take(8)->get();
                                } else {
                                    $notifications = \App\Models\Notification::where('user_id', Auth::id())
                                        ->active()
                                        ->orderBy('created_at','desc')->take(8)->get();
                                }
                                $todayNotifs = $notifications->filter(fn($n) => $n->created_at->isToday());
                                $olderNotifs = $notifications->filter(fn($n) => !$n->created_at->isToday());
                            @endphp
                            <div style="max-height:360px;overflow-y:auto;">
                                @if($notifications->count() > 0)
                                    @if($todayNotifs->count() > 0)
                                    <div class="notif-group-label">Today</div>
                                    @foreach($todayNotifs as $notif)
                                    <div id="notif-{{ $notif->id }}" style="padding:13px 18px;border-bottom:1px solid rgba(255,255,255,0.05);background:{{ $notif->is_read ? 'transparent' : 'rgba(212, 175, 55, 0.08)' }};transition:all .2s;">
                                        <div style="display:flex;gap:10px;align-items:flex-start;">
                                            <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
                                                {{ $notif->type === 'warning_3' ? 'background:rgba(239, 68, 68, 0.15);color:#f87171;' : ($notif->type === 'custom' ? 'background:rgba(59, 130, 246, 0.15);color:#60a5fa;' : 'background:rgba(245, 158, 11, 0.15);color:#fbbf24;') }}">
                                                <i class="bi {{ $notif->type === 'warning_3' ? 'bi-exclamation-octagon-fill' : ($notif->type === 'custom' ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill') }}"></i>
                                            </div>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:.82rem;color:#f3e7cd;line-height:1.4;">{{ $notif->message }}</div>
                                                <div style="font-size:.72rem;color:rgba(255,255,255,0.5);margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                                @if(!$notif->is_read)
                                                <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;"></div>
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
                                    <div id="notif-{{ $notif->id }}" style="padding:13px 18px;border-bottom:1px solid rgba(255,255,255,0.05);background:{{ $notif->is_read ? 'transparent' : 'rgba(212, 175, 55, 0.08)' }};transition:all .2s;">
                                        <div style="display:flex;gap:10px;align-items:flex-start;">
                                            <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
                                                {{ $notif->type === 'warning_3' ? 'background:rgba(239, 68, 68, 0.15);color:#f87171;' : ($notif->type === 'custom' ? 'background:rgba(59, 130, 246, 0.15);color:#60a5fa;' : 'background:rgba(245, 158, 11, 0.15);color:#fbbf24;') }}">
                                                <i class="bi {{ $notif->type === 'warning_3' ? 'bi-exclamation-octagon-fill' : ($notif->type === 'custom' ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill') }}"></i>
                                            </div>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:.82rem;color:#f3e7cd;line-height:1.4;">{{ $notif->message }}</div>
                                                <div style="font-size:.72rem;color:rgba(255,255,255,0.5);margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                                @if(!$notif->is_read)
                                                <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;"></div>
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
                                <div style="text-align:center;padding:32px 20px;color:#94a3b8;">
                                    <i class="bi bi-bell-slash" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.3;"></i>
                                    <span style="font-size:.85rem;">No notifications yet</span>
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
                            $profileImageUrl = Auth::user()->profile_image ? (str_starts_with(Auth::user()->profile_image, 'http') ? Auth::user()->profile_image : asset('storage/'.Auth::user()->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name).'&background=800000&color=fff&size=200';
                        @endphp
                        <a href="#" data-bs-toggle="dropdown" class="text-decoration-none d-flex align-items-center gap-2">
                            <img src="{{ $profileImageUrl }}" class="header-profile-img">
                            <div class="d-none d-md-block text-start" style="line-height:1.2;">
                                <div style="font-size:0.8rem;font-weight:600;color:#ffffff;">{{ Auth::user()->name }}</div>
                            </div>
                            <i class="bi bi-chevron-down d-none d-md-block" style="font-size:0.7rem;color:rgba(255,255,255,0.75);"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end fb-dropdown mt-2">
                            <!-- Profile summary (non-clickable, just info) -->
                            <div class="fb-profile-header" style="cursor:default;">
                                <img src="{{ $profileImageUrl }}" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;">
                                <div>
                                    <div class="fw-bold" style="font-size:0.9rem;">{{ Auth::user()->name }}</div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">{{ Auth::user()->student_number }}</div>
                                </div>
                            </div>
                            <hr class="my-2" style="border-color:#f1f5f9;">
                            @if(Auth::user()->isTeacher())
                            <a class="fb-dropdown-item" href="{{ route('teacher.profile') }}">
                                <div class="fb-icon-circle"><i class="bi bi-gear-fill"></i></div>
                                <span>Settings</span>
                            </a>
                            @elseif(Auth::user()->isAdmin())
                            <a class="fb-dropdown-item" href="{{ route('admin.profile') }}">
                                <div class="fb-icon-circle"><i class="bi bi-gear-fill"></i></div>
                                <span>Settings</span>
                            </a>
                            @elseif(Auth::user()->isParent())
                            <a class="fb-dropdown-item" href="{{ route('parent.profile') }}">
                                <div class="fb-icon-circle"><i class="bi bi-gear-fill"></i></div>
                                <span>Settings</span>
                            </a>
                            @elseif(Auth::user()->isStudent())
                            <a class="fb-dropdown-item" href="{{ route('settings') }}">
                                <div class="fb-icon-circle"><i class="bi bi-gear-fill"></i></div>
                                <span>Settings</span>
                            </a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to log out?');">
                                @csrf
                                <button type="submit" class="fb-dropdown-item" style="color:#dc2626 !important;">
                                    <div class="fb-icon-circle" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-box-arrow-right"></i></div>
                                    <span>Log Out</span>
                                </button>
                            </form>
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

                @yield('content')
            </div>
        </main>
    </div>

    <!-- System Toast Container (for websocket notifications) -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @auth
    <script>
        const sidebar     = document.getElementById('sidebar');
        const burgerBtn   = document.getElementById('burgerBtn');
        const topHeader   = document.getElementById('topHeader');
        const mainContent = document.getElementById('mainContent');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // ── TOAST HELPER ──
        function showToast(message, type = 'success', duration = 4500) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            
            const colors = {
                success:   { border: '#4ade80', icon: 'check-circle-fill', bg: 'rgba(34,197,94,0.12)' },
                error:     { border: '#f87171', icon: 'x-circle-fill', bg: 'rgba(239,68,68,0.12)' },
                warning:   { border: '#fbbf24', icon: 'exclamation-triangle-fill', bg: 'rgba(245,158,11,0.12)' },
                info:      { border: '#60a5fa', icon: 'info-circle-fill', bg: 'rgba(59,130,246,0.12)' },
                warning_3: { border: '#f87171', icon: 'exclamation-octagon-fill', bg: 'rgba(239,68,68,0.15)' },
                warning_2: { border: '#fbbf24', icon: 'exclamation-triangle-fill', bg: 'rgba(245,158,11,0.15)' },
                custom:    { border: '#cfa46f', icon: 'bell-fill', bg: 'rgba(207,164,111,0.15)' }
            };
            
            const style = colors[type] || colors.success;
            const toast = document.createElement('div');
            toast.className = 'modern-toast';
            toast.style.borderLeft = `4px solid ${style.border}`;
            toast.innerHTML = `
                <div style="width: 32px; height: 32px; border-radius: 8px; background: ${style.bg}; display: flex; align-items: center; justify-content: center; color: ${style.border}; flex-shrink: 0;">
                    <i class="bi bi-${style.icon}"></i>
                </div>
                <div class="modern-toast-content">${message}</div>
                <button class="modern-toast-close" onclick="this.parentElement.remove()" aria-label="Close notification">
                    <i class="bi bi-x"></i>
                </button>
            `;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'toastSlideOut 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Auto-show Laravel flash messages
        @if(session('success')) showToast(@json(session('success')), 'success'); @endif
        @if(session('error')) showToast(@json(session('error')), 'error'); @endif
        @if(session('warning')) showToast(@json(session('warning')), 'warning'); @endif
        @if(session('info')) showToast(@json(session('info')), 'info'); @endif
        @if(session('status')) showToast(@json(session('status')), 'info'); @endif

        // Check if mobile
        const isMobile = window.innerWidth <= 768;

        // Restore saved state (only for desktop)
        if (!isMobile && localStorage.getItem('sidebarMini') === 'true') applyMini(true, false);

        function toggleSidebar() {
            if (isMobile) {
                const isOpen = sidebar.classList.contains('open');
                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                const isMini = sidebar.classList.contains('collapsed');
                applyMini(!isMini, true);
                localStorage.setItem('sidebarMini', !isMini);
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

        // ── WEBSOCKET: Real-time notifications via Laravel Echo + Reverb ──
        (function() {
            // Dynamically load Laravel Echo + Pusher-js (Reverb uses Pusher protocol)
            const script1 = document.createElement('script');
            script1.src = 'https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js';
            script1.onload = function() {
                const script2 = document.createElement('script');
                script2.src = 'https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js';
                script2.onload = function() {
                    window.adminEcho = new Echo({
                        broadcaster: 'reverb',
                        key: '{{ env("REVERB_APP_KEY") }}',
                        wsHost: '{{ env("REVERB_HOST", "localhost") }}',
                        wsPort: {{ env("REVERB_PORT", 8080) }},
                        wssPort: {{ env("REVERB_PORT", 8080) }},
                        forceTLS: false,
                        enabledTransports: ['ws'],
                    });

                    // Listen on private channel
                    window.adminEcho.private('notifications.{{ Auth::id() }}')
                        .listen('.notification.sent', (e) => {
                            // Bump badge count
                            const notifBtn = document.querySelector('.notif-btn');
                            let badge = notifBtn.querySelector('span');
                            if (badge) {
                                const count = parseInt(badge.textContent) || 0;
                                badge.textContent = count + 1 > 9 ? '9+' : count + 1;
                            } else {
                                badge = document.createElement('span');
                                badge.style.cssText = 'position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:#dc2626;color:white;border-radius:50%;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;';
                                badge.textContent = '1';
                                notifBtn.appendChild(badge);
                            }

                            // Add notification to dropdown list
                            const list = document.querySelector('.dropdown-menu .overflow-auto, .dropdown-menu [style*="max-height"]');
                            if (list) {
                                const iconMap = { warning_3: 'bi-exclamation-octagon-fill', warning_2: 'bi-exclamation-triangle-fill', custom: 'bi-info-circle-fill' };
                                const colorMap = { warning_3: 'background:#fef2f2;color:#dc2626;', warning_2: 'background:#fffbeb;color:#d97706;', custom: 'background:#eff6ff;color:#2563eb;' };
                                const id = Date.now();
                                const div = document.createElement('div');
                                div.id = 'notif-' + id;
                                div.style.cssText = 'padding:13px 18px;border-bottom:1px solid #f8fafc;background:#fffbeb;';
                                div.innerHTML = `
                                    <div style="display:flex;gap:10px;align-items:flex-start;">
                                        <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;${colorMap[e.type]||colorMap.custom}">
                                            <i class="bi ${iconMap[e.type]||'bi-bell-fill'}"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:.82rem;color:#1e293b;line-height:1.4;">${e.message}</div>
                                            <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;">Just now</div>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                            <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;"></div>
                                        </div>
                                    </div>`;
                                list.prepend(div);
                            }

                            // Show toast notification
                            if (typeof showToast === 'function') {
                                showToast(e.message, e.type || 'custom');
                            }
                        });
                };
                document.head.appendChild(script2);
            };
            document.head.appendChild(script1);
        })();
    </script>
    @endauth
    
    <!-- Premium Toast Container (for session messages) -->
    <div class="premium-toast-container" id="premiumToastContainer"></div>
    <script>
    function showPremiumToast(message, type = 'success') {
        const container = document.getElementById('premiumToastContainer');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'premium-toast';
        
        let iconClass = 'bi-check-circle-fill';
        let iconColor = 'var(--gold)';
        
        if (type === 'error') {
            iconClass = 'bi-x-circle-fill';
            iconColor = '#f87171';
            toast.style.borderLeftColor = '#f87171';
        } else if (type === 'warning') {
            iconClass = 'bi-exclamation-triangle-fill';
            iconColor = '#fbbf24';
            toast.style.borderLeftColor = '#fbbf24';
        }

        toast.innerHTML = `
            <i class="bi ${iconClass} premium-toast-icon" style="color: ${iconColor};"></i>
            <div class="premium-toast-content">${message}</div>
        `;
        
        container.appendChild(toast);
        
        requestAnimationFrame(() => {
            setTimeout(() => toast.classList.add('show'), 50);
        });
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 2000);
    }
    </script>

    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showPremiumToast("{{ session('success') }}", 'success');
        });
    </script>
    @endif
    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showPremiumToast("{{ session('error') }}", 'error');
        });
    </script>
    @endif

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
    <script>
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
        @if(Auth::user()->isAdmin())
            @include('layouts.mbn.admin')
        @elseif(Auth::user()->isTeacher())
            @include('layouts.mbn.teacher')
        @elseif(Auth::user()->isParent())
            @include('layouts.mbn.parent')
        @else
            @include('layouts.mbn.student')
        @endif

        <!-- More Bottom Sheet -->
        <div class="more-sheet-overlay" id="moreSheetOverlay" onclick="closeMoreSheet()"></div>
        <div class="more-sheet" id="moreSheet">
            <div class="more-sheet-handle"></div>
            <div class="more-sheet-header">
                <span class="more-sheet-title">More</span>
                <button class="more-sheet-close" onclick="closeMoreSheet()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="more-sheet-grid" id="moreSheetContent">
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.teachers') }}" class="more-sheet-item" data-color="purple" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-person-workspace"></i></div>
                        <span class="more-sheet-item-label">Teachers</span>
                    </a>
                    <a href="{{ route('admin.calendar') }}" class="more-sheet-item" data-color="gold" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-calendar-event"></i></div>
                        <span class="more-sheet-item-label">Calendar</span>
                    </a>
                    <a href="{{ route('admin.departments.index') }}" class="more-sheet-item" data-color="green" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-building"></i></div>
                        <span class="more-sheet-item-label">Departments</span>
                    </a>
                    <a href="{{ route('admin.sections.index') }}" class="more-sheet-item" data-color="amber" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                        <span class="more-sheet-item-label">Sections</span>
                    </a>
                    <a href="{{ route('admin.subjects') }}" class="more-sheet-item" data-color="blue" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-book-half"></i></div>
                        <span class="more-sheet-item-label">Subjects</span>
                    </a>
                    <a href="{{ route('admin.excuses') }}" class="more-sheet-item" data-color="red" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <span class="more-sheet-item-label">Excuses</span>
                    </a>
                    <a href="{{ route('admin.profile') }}" class="more-sheet-item" data-color="gold" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-person-circle"></i></div>
                        <span class="more-sheet-item-label">Profile</span>
                    </a>
                    <a href="{{ route('admin.notifications') }}" class="more-sheet-item" data-color="amber" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-bell-fill"></i></div>
                        <span class="more-sheet-item-label">Notifications</span>
                    </a>
                @elseif(Auth::user()->isTeacher())
                    <a href="{{ route('teacher.calendar') }}" class="more-sheet-item" data-color="gold" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-calendar-event"></i></div>
                        <span class="more-sheet-item-label">Calendar</span>
                    </a>
                    <a href="{{ route('teacher.subjects') }}" class="more-sheet-item" data-color="blue" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-qr-code-scan"></i></div>
                        <span class="more-sheet-item-label">QR / Subjects</span>
                    </a>
                    <a href="{{ route('teacher.reports') }}" class="more-sheet-item" data-color="green" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <span class="more-sheet-item-label">Reports</span>
                    </a>
                    <a href="{{ route('teacher.notifications') }}" class="more-sheet-item" data-color="amber" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-bell-fill"></i></div>
                        <span class="more-sheet-item-label">Notifications</span>
                    </a>
                    <a href="{{ route('teacher.students') }}" class="more-sheet-item" data-color="purple" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-people-fill"></i></div>
                        <span class="more-sheet-item-label">Students</span>
                    </a>
                    <a href="{{ route('teacher.attendance') }}" class="more-sheet-item" data-color="red" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-clipboard-check-fill"></i></div>
                        <span class="more-sheet-item-label">Attendance</span>
                    </a>
                @elseif(Auth::user()->isParent())
                    <a href="{{ route('parent.link.form') }}" class="more-sheet-item" data-color="green" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-link-45deg"></i></div>
                        <span class="more-sheet-item-label">Link Child</span>
                    </a>
                    <a href="{{ route('parent.excuses') }}" class="more-sheet-item" data-color="red" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <span class="more-sheet-item-label">Excuses</span>
                    </a>
                    <a href="{{ route('parent.notifications') }}" class="more-sheet-item" data-color="amber" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-bell-fill"></i></div>
                        <span class="more-sheet-item-label">Notifications</span>
                    </a>
                    <a href="{{ route('parent.profile') }}" class="more-sheet-item" data-color="blue" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-gear-fill"></i></div>
                        <span class="more-sheet-item-label">Settings</span>
                    </a>
                @else
                    <a href="{{ route('student.calendar') }}" class="more-sheet-item" data-color="gold" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-calendar-event-fill"></i></div>
                        <span class="more-sheet-item-label">Calendar</span>
                    </a>
                    <a href="{{ route('notifications') }}" class="more-sheet-item" data-color="amber" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-bell-fill"></i></div>
                        <span class="more-sheet-item-label">Notifications</span>
                    </a>
                    <a href="{{ route('attendance.records') }}" class="more-sheet-item" data-color="green" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-clipboard-data-fill"></i></div>
                        <span class="more-sheet-item-label">Records</span>
                    </a>
                    <a href="{{ route('settings') }}" class="more-sheet-item" data-color="blue" onclick="closeMoreSheet()">
                        <div class="more-sheet-item-icon"><i class="bi bi-gear-fill"></i></div>
                        <span class="more-sheet-item-label">Settings</span>
                    </a>
                @endif
            </div>
        </div>
        <script>
        function openMoreSheet() {
            document.getElementById('moreSheetOverlay').classList.add('show');
            document.getElementById('moreSheet').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeMoreSheet() {
            document.getElementById('moreSheetOverlay').classList.remove('show');
            document.getElementById('moreSheet').classList.remove('open');
            document.body.style.overflow = '';
        }
        // Swipe-down to dismiss
        (function() {
            const sheet = document.getElementById('moreSheet');
            if (!sheet) return;
            let startY = 0, currentY = 0, isDragging = false;
            sheet.addEventListener('touchstart', function(e) {
                if (sheet.scrollTop > 0) return;
                startY = e.touches[0].clientY;
                isDragging = true;
            }, { passive: true });
            sheet.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                currentY = e.touches[0].clientY;
                const diff = currentY - startY;
                if (diff > 0) {
                    sheet.style.transform = 'translateY(' + diff + 'px)';
                    sheet.style.transition = 'none';
                }
            }, { passive: true });
            sheet.addEventListener('touchend', function() {
                if (!isDragging) return;
                isDragging = false;
                const diff = currentY - startY;
                sheet.style.transition = '';
                if (diff > 80) {
                    closeMoreSheet();
                } else {
                    sheet.style.transform = '';
                }
                sheet.style.transform = '';
            });
        })();
        </script>
    @endauth
    @auth
        <x-command-palette />
        <script src="{{ asset('js/pull-refresh.js') }}?v={{ filemtime(public_path('js/pull-refresh.js')) }}"></script>
    @endauth

    @yield('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inlineFlashes = document.querySelectorAll('.flash-ok, .flash-error');
            if (inlineFlashes.length > 0) {
                setTimeout(() => {
                    inlineFlashes.forEach(el => {
                        el.style.transition = 'opacity 0.5s ease, height 0.5s ease, padding 0.5s ease, margin 0.5s ease';
                        el.style.opacity = '0';
                        el.style.height = '0';
                        el.style.padding = '0';
                        el.style.margin = '0';
                        el.style.overflow = 'hidden';
                        setTimeout(() => el.remove(), 500);
                    });
                }, 1000);
            }
        });
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
    @auth
    <script>
        // Idle Timeout (15 minutes = 900,000 ms)
        let idleTime = 0;
        const IDLE_TIMEOUT_MS = 15 * 60 * 1000;
        
        function resetIdleTimer() {
            idleTime = 0;
        }

        // Increment idle time every second
        // const idleInterval = setInterval(() => {
        //     idleTime += 1000;
        //     if (idleTime >= IDLE_TIMEOUT_MS) {
        //         clearInterval(idleInterval);
        //         // Trigger logout
        //         const logoutForm = document.createElement('form');
        //         logoutForm.method = 'POST';
        //         logoutForm.action = '{{ route("logout") }}';
        //         
        //         const csrfInput = document.createElement('input');
        //         csrfInput.type = 'hidden';
        //         csrfInput.name = '_token';
        //         csrfInput.value = '{{ csrf_token() }}';
        //         
        //         logoutForm.appendChild(csrfInput);
        //         document.body.appendChild(logoutForm);
        //         logoutForm.submit();
        //     }
        // }, 1000);

        // Reset timer on any interaction
        window.onload = resetIdleTimer;
        window.onmousemove = resetIdleTimer;
        window.onmousedown = resetIdleTimer;
        window.ontouchstart = resetIdleTimer;
        window.onclick = resetIdleTimer;
        window.onkeydown = resetIdleTimer;
    </script>
    @endauth
    
    <!-- Global Loading Overlay -->
    <div id="global-loader" style="display: none; position: fixed; inset: 0; background: rgba(17,10,10,0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; flex-direction: column;">
        <div style="width: 56px; height: 56px; border: 3px solid rgba(212,175,55,0.15); border-top-color: #cfa46f; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
        <p style="color: #cfa46f; margin-top: 16px; font-weight: 600; font-size: 0.9rem; letter-spacing: 0.02em;">Processing...</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form:not([data-no-loader])').forEach(form => {
                form.addEventListener('submit', function() {
                    if (this.checkValidity() && !this.getAttribute('target') && this.method.toUpperCase() === 'POST') {
                        const loader = document.getElementById('global-loader');
                        if (loader) loader.style.display = 'flex';
                    }
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
