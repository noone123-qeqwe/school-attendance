<header class="mobile-header" id="mobileHeader">
    <button type="button" class="header-btn-left" id="headerBtnLeft" aria-label="{{ !empty($showBack) ? 'Go back' : 'Open navigation menu' }}" @if(empty($showBack)) aria-controls="mobileNavigationMenu" aria-expanded="false" @endif>
        @if(isset($showBack) && $showBack)
            <i class="bi bi-arrow-left"></i>
        @else
            <i class="bi bi-list"></i>
        @endif
    </button>
    
    <h1 class="header-title">{{ $title ?? config('app.name', 'Smart Attendance') }}</h1>
    
    <div class="header-actions">
        @if(isset($showNotifications) && $showNotifications)
            <button type="button" class="header-btn-right" id="notificationBtn" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                @if(isset($notificationCount) && $notificationCount > 0)
                    <span class="notification-badge">{{ $notificationCount }}</span>
                @endif
            </button>
        @endif
        
        @if(isset($showSearch) && $showSearch)
            <button type="button" class="header-btn-right" id="searchBtn" aria-label="Search">
                <i class="bi bi-search"></i>
            </button>
        @endif
        
        @if(isset($showMore) && $showMore)
            <button type="button" class="header-btn-right" id="moreBtn" aria-label="More options">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
        @endif
    </div>
</header>

@if(empty($showBack))
<div class="mobile-menu-backdrop" id="mobileMenuBackdrop" hidden></div>
<nav class="mobile-navigation-menu" id="mobileNavigationMenu" aria-label="Mobile navigation" hidden>
    <div class="mobile-menu-heading">
        <span>Navigate</span>
        <button type="button" class="mobile-menu-close" id="mobileMenuClose" aria-label="Close navigation menu"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
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
        background: var(--bg-dark);
        border-bottom: 1px solid rgba(207, 164, 111, 0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-left: max(12px, var(--safe-left));
        padding-right: max(12px, var(--safe-right));
        z-index: 999;
        transition: transform 0.3s ease;
    }

    .mobile-header.hidden {
        transform: translateY(-100%);
    }

    .header-btn-left,
    .header-btn-right {
        width: 40px;
        height: 40px;
        border: none;
        background: transparent;
        color: var(--text-primary);
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        cursor: pointer;
        position: relative;
        transition: background 0.2s ease;
    }

    .header-btn-left:active,
    .header-btn-right:active {
        background: var(--bg-card);
    }

    .header-title {
        flex: 1;
        text-align: center;
        font-size: 18px;
        font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 0 8px;
    }

    .header-actions {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .notification-badge {
        position: absolute;
        top: 8px;
        right: 8px;
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
    }
</style>

<script @cspNonce>
    // Auto-hide header on scroll down, show on scroll up
    (function() {
        let lastScroll = 0;
        const header = document.getElementById('mobileHeader');
        
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll <= 0) {
                header.classList.remove('hidden');
                return;
            }
            
            if (currentScroll > lastScroll && currentScroll > 100) {
                // Scrolling down
                header.classList.add('hidden');
            } else {
                // Scrolling up
                header.classList.remove('hidden');
            }
            
            lastScroll = currentScroll;
        });

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
                const link = item.cloneNode(true);
                link.removeAttribute('id');
                link.classList.remove('nav-item-primary');
                link.classList.add('mobile-menu-link');
                menuLinks.appendChild(link);
            });
            const closeMenu = function() {
                menu.hidden = true;
                backdrop.hidden = true;
                backBtn.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('mobile-menu-open');
                backBtn.focus();
            };
            backBtn.addEventListener('click', function() {
                menu.hidden = false;
                backdrop.hidden = false;
                backBtn.setAttribute('aria-expanded', 'true');
                document.body.classList.add('mobile-menu-open');
                closeButton.focus();
            });
            closeButton.addEventListener('click', closeMenu);
            backdrop.addEventListener('click', closeMenu);
            menuLinks.addEventListener('click', function(event) {
                if (event.target.closest('button')) closeMenu();
            });
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
