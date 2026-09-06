<header class="mobile-header" id="mobileHeader">
    <button class="header-btn-left" id="headerBtnLeft">
        @if(isset($showBack) && $showBack)
            <i class="bi bi-arrow-left"></i>
        @else
            <i class="bi bi-list"></i>
        @endif
    </button>
    
    <h1 class="header-title">{{ $title ?? config('app.name', 'Smart Attendance') }}</h1>
    
    <div class="header-actions">
        @if(isset($showNotifications) && $showNotifications)
            <button class="header-btn-right" id="notificationBtn">
                <i class="bi bi-bell"></i>
                @if(isset($notificationCount) && $notificationCount > 0)
                    <span class="notification-badge">{{ $notificationCount }}</span>
                @endif
            </button>
        @endif
        
        @if(isset($showSearch) && $showSearch)
            <button class="header-btn-right" id="searchBtn">
                <i class="bi bi-search"></i>
            </button>
        @endif
        
        @if(isset($showMore) && $showMore)
            <button class="header-btn-right" id="moreBtn">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
        @endif
    </div>
</header>

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

<script>
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

        // Menu button handler (you can customize this)
        if (backBtn && backBtn.querySelector('.bi-list')) {
            backBtn.addEventListener('click', function() {
                // Open side menu or show options
                console.log('Menu clicked');
                // You can implement a slide-out menu here
            });
        }
    })();
</script>
