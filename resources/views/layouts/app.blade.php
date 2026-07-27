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

    <link rel="stylesheet" href="{{ asset('css/premium.css') }}">
</head>

<body>

    @auth
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <aside class="sidebar" id="sidebar">
        @section('role-sidebar')
        <!-- Default (Student) sidebar -->
        <!-- Logo area -->
        <div class="sidebar-head">
            <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
            <div class="sidebar-text">
                <div class="sidebar-brand">{{ config('app.name') }}</div>
                <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        <!-- Nav -->
        <div class="sidebar-nav">
            @if(Auth::check() && Auth::user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link"
                   data-title="Admin Dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-link-text">Admin Dashboard</span>
                </a>
                <a href="{{ route('admin.teachers') }}"
                   class="nav-link"
                   data-title="Teachers">
                    <i class="bi bi-person-workspace"></i>
                    <span class="nav-link-text">Teachers</span>
                </a>
                <a href="{{ route('admin.excuses') }}"
                   class="nav-link"
                   data-title="Excuse Reviews">
                    <i class="bi bi-file-earmark-check"></i>
                    <span class="nav-link-text">Excuse Reviews</span>
                </a>
            @else
                <a href="{{ route('home') }}"
                   class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                   data-title="Dashboard">
                    <i class="bi bi-grid-fill"></i>
                    <span class="nav-link-text">Dashboard</span>
                </a>
                <a href="{{ route('student.classes') }}"
                   class="nav-link {{ request()->routeIs('student.classes') ? 'active' : '' }}"
                   data-title="My Classes">
                    <i class="bi bi-folder-fill"></i>
                    <span class="nav-link-text">My Classes</span>
                </a>
                <a href="{{ route('notifications') }}"
                   class="nav-link {{ request()->routeIs('notifications') ? 'active' : '' }}"
                   data-title="Notifications">
                    <i class="bi bi-bell-fill"></i>
                    <span class="nav-link-text">Notifications</span>
                </a>
                <a href="{{ route('excuses') }}"
                   class="nav-link {{ request()->routeIs('excuses*') ? 'active' : '' }}"
                   data-title="Excuse Submissions">
                    <i class="bi bi-file-text-fill"></i>
                    <span class="nav-link-text">Excuse Submissions</span>
                </a>
            @endif
        </div>

        <div class="sidebar-footer text-center">
            <small>© {{ date('Y') }} {{ config('app.name') }}</small>
        </div>
        @show
    </aside>
    @endauth

    <div class="main-wrapper">
        <header class="top-header {{ Auth::check() ? '' : 'guest' }}" id="topHeader">
            @auth
                <div class="header-left">
                    <!-- Burger -->
                    <button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                        <i class="bi bi-layout-sidebar-inset"></i>
                    </button>
                    <div>
                        <div class="header-page-title">
                            @if(request()->routeIs('home')) Dashboard
                            @elseif(request()->routeIs('student.classes')) My Classes
                            @elseif(request()->routeIs('settings')) Settings
                            @else Portal
                            @endif
                        </div>
                        <div class="header-page-sub">{{ now()->format('l, F j, Y') }}</div>
                    </div>
                </div>

                <div class="header-right">
                    @php $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count(); @endphp

                    <div class="dropdown">
                        <div class="notif-btn position-relative" data-bs-toggle="dropdown" style="cursor:pointer;">
                            <i class="bi bi-bell-fill" style="font-size:0.95rem;"></i>
                            @if($unreadCount > 0)
                            <span style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:#dc2626;color:white;border-radius:50%;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                            @endif
                        </div>
                        <div class="dropdown-menu dropdown-menu-end mt-2" style="width:340px;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 20px 60px rgba(0,0,0,0.12);padding:0;overflow:hidden;">
                            <div style="padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                                <span style="font-size:.9rem;font-weight:700;color:#1e293b;">Notifications</span>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    @if($unreadCount > 0)
                                    <span style="background:#fef2f2;color:#dc2626;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px;">{{ $unreadCount }} new</span>
                                    <button onclick="markAllRead()" class="mark-all-read-btn">Mark all read</button>
                                    @endif
                                </div>
                            </div>
                            @php
                                $notifications = \App\Models\Notification::where('user_id', Auth::id())
                                    ->active()
                                    ->orderBy('created_at','desc')->take(8)->get();
                                $todayNotifs = $notifications->filter(fn($n) => $n->created_at->isToday());
                                $olderNotifs = $notifications->filter(fn($n) => !$n->created_at->isToday());
                            @endphp
                            <div style="max-height:360px;overflow-y:auto;">
                                @if($notifications->count() > 0)
                                    @if($todayNotifs->count() > 0)
                                    <div class="notif-group-label">Today</div>
                                    @foreach($todayNotifs as $notif)
                                    <div id="notif-{{ $notif->id }}" style="padding:13px 18px;border-bottom:1px solid #f8fafc;background:{{ $notif->is_read ? 'white' : '#fffbeb' }};transition:all .2s;">
                                        <div style="display:flex;gap:10px;align-items:flex-start;">
                                            <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
                                                {{ $notif->type === 'warning_3' ? 'background:#fef2f2;color:#dc2626;' : ($notif->type === 'custom' ? 'background:#eff6ff;color:#2563eb;' : 'background:#fffbeb;color:#d97706;') }}">
                                                <i class="bi {{ $notif->type === 'warning_3' ? 'bi-exclamation-octagon-fill' : ($notif->type === 'custom' ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill') }}"></i>
                                            </div>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:.82rem;color:#1e293b;line-height:1.4;">{{ $notif->message }}</div>
                                                <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                                @if(!$notif->is_read)
                                                <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;"></div>
                                                @endif
                                                <button onclick="archiveNotif({{ $notif->id }}, this)" title="Archive" class="notif-archive-btn">
                                                    <i class="bi bi-archive-fill"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    @endif

                                    @if($olderNotifs->count() > 0)
                                    <div class="notif-group-label">Earlier</div>
                                    @foreach($olderNotifs as $notif)
                                    <div id="notif-{{ $notif->id }}" style="padding:13px 18px;border-bottom:1px solid #f8fafc;background:{{ $notif->is_read ? 'white' : '#fffbeb' }};transition:all .2s;">
                                        <div style="display:flex;gap:10px;align-items:flex-start;">
                                            <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
                                                {{ $notif->type === 'warning_3' ? 'background:#fef2f2;color:#dc2626;' : ($notif->type === 'custom' ? 'background:#eff6ff;color:#2563eb;' : 'background:#fffbeb;color:#d97706;') }}">
                                                <i class="bi {{ $notif->type === 'warning_3' ? 'bi-exclamation-octagon-fill' : ($notif->type === 'custom' ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill') }}"></i>
                                            </div>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:.82rem;color:#1e293b;line-height:1.4;">{{ $notif->message }}</div>
                                                <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                                @if(!$notif->is_read)
                                                <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;"></div>
                                                @endif
                                                <button onclick="archiveNotif({{ $notif->id }}, this)" title="Archive" class="notif-archive-btn">
                                                    <i class="bi bi-archive-fill"></i>
                                                </button>
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
                            <a href="{{ route('notifications') }}" class="notif-view-all">
                                View all notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                    @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="role-link role-admin" title="Admin Panel">
                        <i class="bi bi-shield-fill"></i>
                    </a>
                    @elseif(Auth::user()->isTeacher())
                    <a href="{{ route('teacher.dashboard') }}" class="role-link role-teacher" title="Teacher Panel">
                        <i class="bi bi-person-workspace"></i>
                    </a>
                    @endif
                    <div class="dropdown">
                        <a href="#" data-bs-toggle="dropdown" class="text-decoration-none d-flex align-items-center gap-2">
                            <img src="{{ Auth::user()->profile_image ? asset('storage/'.Auth::user()->profile_image) : 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name).'&background=800000&color=fff&size=200' }}" class="header-profile-img">
                            <div class="d-none d-md-block text-start" style="line-height:1.2;">
                                <div style="font-size:0.8rem;font-weight:600;color:#ffffff;">{{ Auth::user()->name }}</div>
                            </div>
                            <i class="bi bi-chevron-down d-none d-md-block" style="font-size:0.7rem;color:rgba(255,255,255,0.75);"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end fb-dropdown mt-2">
                            <!-- Profile summary (non-clickable, just info) -->
                            <div class="fb-profile-header" style="cursor:default;">
                                <img src="{{ Auth::user()->profile_image ? asset('storage/'.Auth::user()->profile_image) : asset('images/default-avatar.png') }}" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;">
                                <div>
                                    <div class="fw-bold" style="font-size:0.9rem;">{{ Auth::user()->name }}</div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">{{ Auth::user()->student_number }}</div>
                                </div>
                            </div>
                            <hr class="my-2" style="border-color:#f1f5f9;">
                            <a class="fb-dropdown-item" href="{{ route('settings') }}">
                                <div class="fb-icon-circle"><i class="bi bi-gear-fill"></i></div>
                                <span>Settings</span>
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
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
        function showToast(message, type) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const iconMap = { warning_3: 'bi-exclamation-octagon-fill', warning_2: 'bi-exclamation-triangle-fill', custom: 'bi-info-circle-fill', success: 'bi-check-circle-fill' };
            const colorMap = { warning_3: 'background:#fef2f2;color:#dc2626;', warning_2: 'background:#fffbeb;color:#d97706;', custom: 'background:#eff6ff;color:#2563eb;', success: 'background:rgba(34,197,94,0.16);color:#4ade80;' };
            const toast = document.createElement('div');
            toast.className = 'toast-item';
            toast.innerHTML = '<div class="toast-icon" style="' + (colorMap[type] || colorMap.custom) + '"><i class="bi ' + (iconMap[type] || 'bi-bell-fill') + '"></i></div><div style="flex:1;min-width:0;">' + message + '</div>';
            container.appendChild(toast);
            setTimeout(function() {
                toast.classList.add('toast-out');
                setTimeout(function() { toast.remove(); }, 300);
            }, 4500);
        }

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
            fetch('{{ route("notifications.read") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => location.reload());
        }

        function archiveNotif(id, btn) {
            const row = document.getElementById('notif-' + id);
            // Animate out
            row.style.transition = 'opacity .2s, transform .2s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(10px)';
            setTimeout(() => {
                fetch(`/notifications/${id}/archive`, {
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
        }, 4000);
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

    @yield('scripts')
</body>
</html>
