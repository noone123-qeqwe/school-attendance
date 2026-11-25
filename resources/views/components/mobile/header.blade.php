@php
    $headerUser = auth()->user();
    $resolvedTitle = $title ?? match (true) {
        request()->routeIs('mobile.home', 'home') => 'Home',
        request()->routeIs('mobile.scan') => 'Scan Attendance QR',
        request()->routeIs('mobile.history') => 'Attendance History',
        request()->routeIs('mobile.profile', 'profile') => 'My Profile',
        request()->routeIs('peer-snap.index') => 'Peer Check-in',
        default => 'Smart Attendance',
    };
    $showStudentQuickShortcuts = empty($showBack)
        && $headerUser?->isStudent()
        && !isset($showNotifications)
        && !isset($showSearch)
        && !isset($showMore);
@endphp
<header class="mobile-header" id="mobileHeader">
    <button type="button" class="header-btn-left" id="headerBtnLeft" aria-label="{{ !empty($showBack) ? 'Go back' : 'Open navigation menu' }}" @if(empty($showBack)) aria-controls="mobileNavigationMenu" aria-expanded="false" @endif>
        @if(isset($showBack) && $showBack)
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        @else
            <i class="bi bi-list" aria-hidden="true"></i>
        @endif
    </button>

    <h1 class="header-title">{{ $resolvedTitle }}</h1>

    <div class="header-actions">
        @if($showStudentQuickShortcuts)
            <a href="{{ route('notifications') }}" class="header-btn-right header-action-link" aria-label="Notifications" title="Notifications">
                <i class="bi bi-bell" aria-hidden="true"></i>
                @if(isset($notificationCount) && $notificationCount > 0)
                    <span class="notification-badge">{{ $notificationCount }}</span>
                @endif
            </a>
            <a href="{{ route('profile') }}" class="header-btn-right header-action-link header-avatar-pill" aria-label="My Profile" title="My Profile">
                @if(!empty($headerUser->profile_image))
                    <img src="{{ $headerUser->profile_photo_url_with_version }}" alt="{{ $headerUser->name }}" class="header-avatar-img">
                @else
                    <span class="header-avatar-initials">{{ strtoupper(substr($headerUser->name ?? 'S', 0, 1)) }}</span>
                @endif
            </a>
        @endif

        @if(isset($showNotifications) && $showNotifications)
            <button type="button" class="header-btn-right" id="notificationBtn" aria-label="Notifications">
                <i class="bi bi-bell" aria-hidden="true"></i>
                @if(isset($notificationCount) && $notificationCount > 0)
                    <span class="notification-badge">{{ $notificationCount }}</span>
                @endif
            </button>
        @endif

        @if(isset($showSearch) && $showSearch)
            <button type="button" class="header-btn-right" id="searchBtn" aria-label="Search">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>
        @endif

        @if(isset($showMore) && $showMore)
            <button type="button" class="header-btn-right" id="moreBtn" aria-label="More options">
                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
            </button>
        @endif
    </div>
</header>

@if(empty($showBack))
<div class="mobile-menu-backdrop" id="mobileMenuBackdrop" hidden></div>
<nav class="mobile-navigation-menu" id="mobileNavigationMenu" aria-label="Mobile navigation" hidden>
    <div class="mobile-menu-heading">
        <div class="mobile-menu-brand">
            <span class="mobile-menu-brand-dot" aria-hidden="true"></span>
            <span>Smart Attendance</span>
        </div>
        <button type="button" class="mobile-menu-close" id="mobileMenuClose" aria-label="Close navigation menu"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    @if($headerUser?->isStudent())
        <a href="{{ route('profile') }}" class="mobile-menu-student-card">
            <div class="mobile-menu-avatar">
                @if(!empty($headerUser->profile_image))
                    <img src="{{ $headerUser->profile_photo_url_with_version }}" alt="{{ $headerUser->name }}">
                @else
                    <span>{{ strtoupper(substr($headerUser->name ?? 'S', 0, 2)) }}</span>
                @endif
            </div>
            <div class="mobile-menu-identity">
                <span class="mobile-menu-name">{{ $headerUser->name }}</span>
                <span class="mobile-menu-meta">
                    {{ $headerUser->student_id ? $headerUser->student_id : 'Student Portal' }}
                    @if($headerUser->course) · {{ $headerUser->course }} @endif
                </span>
            </div>
            <i class="bi bi-chevron-right mobile-menu-chevron" aria-hidden="true"></i>
        </a>
    @endif
    <div id="mobileMenuLinks" class="mobile-menu-links"></div>
</nav>
@endif

<style>
    .mobile-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: calc(var(--header-height) + var(--safe-top));
        padding-top: var(--safe-top);
        background: rgba(17, 10, 10, 0.86);
        backdrop-filter: blur(22px) saturate(160%);
        -webkit-backdrop-filter: blur(22px) saturate(160%);
        border-bottom: 1px solid rgba(207, 164, 111, 0.16);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-left: max(12px, var(--safe-left));
        padding-right: max(12px, var(--safe-right));
        gap: 8px;
        z-index: 999;
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .mobile-header.hidden {
        transform: translateY(-100%);
    }

    .header-btn-left,
    .header-btn-right {
        width: 44px;
        height: 44px;
        min-width: 44px;
        min-height: 44px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.04);
        color: var(--text-primary);
        font-size: 19px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        cursor: pointer;
        position: relative;
        text-decoration: none;
        transition: transform 0.16s ease, background 0.18s ease, border-color 0.18s ease;
    }

    .header-btn-left:active,
    .header-btn-right:active {
        transform: scale(0.92);
        background: var(--bg-card-hover, rgba(207, 164, 111, 0.16));
        border-color: rgba(207, 164, 111, 0.3);
    }

    .header-avatar-pill {
        padding: 2px;
        border-color: rgba(212, 175, 55, 0.32);
        background: rgba(212, 175, 55, 0.10);
        overflow: hidden;
    }

    .header-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        display: block;
    }

    .header-avatar-initials {
        font-size: 0.84rem;
        font-weight: 800;
        color: var(--gold-light, #F3E7CD);
    }

    .header-title {
        flex: 1;
        min-width: 0;
        text-align: center;
        font-size: 1.02rem;
        font-weight: 700;
        letter-spacing: -0.015em;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 0 4px;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-shrink: 0;
    }

    .notification-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background: var(--error);
        color: white;
        font-size: 10px;
        font-weight: 700;
        min-width: 16px;
        height: 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        border: 1.5px solid var(--bg-dark);
    }

    .mobile-menu-brand {
        display: inline-flex;
        align-items: center;
        gap: 9px;
    }

    .mobile-menu-brand-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--gold-primary, #D4AF37);
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.7);
    }

    .mobile-menu-student-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        margin-bottom: 14px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(122, 26, 26, 0.28), rgba(212, 175, 55, 0.1));
        border: 1px solid rgba(207, 164, 111, 0.24);
        text-decoration: none;
        color: var(--text-primary);
    }

    .mobile-menu-avatar {
        width: 44px;
        height: 44px;
        border-radius: 13px;
        background: rgba(212, 175, 55, 0.16);
        border: 1px solid rgba(212, 175, 55, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.92rem;
        color: var(--gold-light, #F3E7CD);
        overflow: hidden;
        flex-shrink: 0;
    }

    .mobile-menu-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .mobile-menu-identity {
        display: flex;
        flex-direction: column;
        min-width: 0;
        flex: 1;
    }

    .mobile-menu-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mobile-menu-meta {
        font-size: 0.76rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mobile-menu-chevron {
        color: var(--gold-primary, #D4AF37);
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .mobile-navigation-menu .mobile-menu-link .nav-scan-jewel {
        width: 24px;
        height: 24px;
        position: static;
        transform: none;
        background: transparent;
        box-shadow: none;
        border: none;
        color: var(--gold-primary, #cfa46f);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .mobile-navigation-menu .mobile-menu-link .nav-scan-jewel svg {
        width: 20px;
        height: 20px;
    }
</style>

<script @cspNonce>
    // Auto-hide header on scroll down, show on scroll up
    (function() {
        let lastScroll = 0;
        let scrollQueued = false;
        const header = document.getElementById('mobileHeader');
        
        function updateHeader() {
            scrollQueued = false;
            const currentScroll = Math.max(0, window.pageYOffset);
            if (currentScroll <= 100 || document.body.classList.contains('mobile-menu-open') || header.contains(document.activeElement)) {
                header.classList.remove('hidden');
                lastScroll = currentScroll;
            } else if (currentScroll > lastScroll + 6) {
                header.classList.add('hidden');
                lastScroll = currentScroll;
            } else if (currentScroll < lastScroll - 6) {
                header.classList.remove('hidden');
                lastScroll = currentScroll;
            }
        }

        window.addEventListener('scroll', function() {
            if (scrollQueued) return;
            scrollQueued = true;
            requestAnimationFrame(updateHeader);
        }, { passive: true });

        // Back button handler
        const backBtn = document.getElementById('headerBtnLeft');
        if (backBtn && backBtn.querySelector('.bi-arrow-left')) {
            backBtn.addEventListener('click', function() {
                window.history.back();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
        const menu = document.getElementById('mobileNavigationMenu');
        const backdrop = document.getElementById('mobileMenuBackdrop');
        const closeButton = document.getElementById('mobileMenuClose');
        const menuLinks = document.getElementById('mobileMenuLinks');
        if (backBtn && menu && backdrop && menuLinks) {
            const bottomNav = document.getElementById('mobileBottomNav');
            bottomNav?.querySelectorAll('.nav-item').forEach(function(item) {
                const link = item.tagName === 'BUTTON' ? document.createElement('a') : item.cloneNode(true);
                if (item.tagName === 'BUTTON') {
                    link.href = '{{ route('mobile.scan') }}?open_scanner=1';
                    link.innerHTML = item.innerHTML;
                    link.className = item.className;
                    link.setAttribute('aria-label', item.getAttribute('aria-label') || 'Scan QR code');
                }
                link.removeAttribute('id');
                link.classList.remove('nav-item-primary');
                link.classList.add('mobile-menu-link');
                menuLinks.appendChild(link);
            });
            @if(auth()->user()?->isStudent())
            [
                ['Weekly Schedule', @json(route('student.schedule')), 'clock-history'],
                ['Attendance Records', @json(route('student.calendar') . '#attendance-records'), 'clipboard-check'],
                ['Excuses', @json(route('excuses')), 'file-text'],
                ['Notifications', @json(route('notifications')), 'bell'],
                ['Settings & Biometrics', @json(route('settings')), 'gear'],
            ].forEach(([label, url, icon]) => {
                const link = document.createElement('a');
                link.href = url;
                link.className = 'nav-item mobile-menu-link';
                const glyph = document.createElement('i');
                glyph.className = 'bi bi-' + icon;
                glyph.setAttribute('aria-hidden', 'true');
                const text = document.createElement('span');
                text.textContent = label;
                link.append(glyph, text);
                menuLinks.appendChild(link);
            });
            @endif
            const closeMenu = function() {
                menu.hidden = true;
                backdrop.hidden = true;
                backBtn.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('mobile-menu-open');
                backBtn.focus();
            };
            backBtn.addEventListener('click', function() {
                header.classList.remove('hidden');
                menu.hidden = false;
                backdrop.hidden = false;
                backBtn.setAttribute('aria-expanded', 'true');
                document.body.classList.add('mobile-menu-open');
                closeButton.focus();
            });
            closeButton.addEventListener('click', closeMenu);
            backdrop.addEventListener('click', closeMenu);
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !menu.hidden) closeMenu();
                if (event.key === 'Tab' && !menu.hidden) {
                    const focusable = Array.from(menu.querySelectorAll('a, button')).filter(function(item) { return !item.disabled; });
                    if (!focusable.length) return;
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });
        }
        });
    })();
</script>
