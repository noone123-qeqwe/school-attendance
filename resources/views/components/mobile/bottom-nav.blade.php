@php
    $user = auth()->user();
    $currentRoute = Route::currentRouteName();
    
    // Role-based navigation items
    $navItems = [];
    
    if ($user->hasRole('student')) {
        $navItems = [
            ['route' => 'mobile.home', 'icon' => 'house-fill', 'label' => 'Home', 'primary' => false],
            ['route' => 'mobile.attendance', 'icon' => 'clipboard-check', 'label' => 'Attend', 'primary' => false],
            ['route' => 'mobile.scan', 'icon' => 'qr-code-scan', 'label' => 'Scan', 'primary' => true],
            ['route' => 'mobile.history', 'icon' => 'clock-history', 'label' => 'History', 'primary' => false],
            ['route' => 'mobile.profile', 'icon' => 'person-fill', 'label' => 'Profile', 'primary' => false],
        ];
    } elseif ($user->hasRole('teacher')) {
        $navItems = [
            ['route' => 'mobile.home', 'icon' => 'house-fill', 'label' => 'Home', 'primary' => false],
            ['route' => 'mobile.classes', 'icon' => 'book', 'label' => 'Classes', 'primary' => false],
            ['route' => 'mobile.scan', 'icon' => 'qr-code-scan', 'label' => 'Scan', 'primary' => true],
            ['route' => 'mobile.students', 'icon' => 'people', 'label' => 'Students', 'primary' => false],
            ['route' => 'mobile.profile', 'icon' => 'person-fill', 'label' => 'Profile', 'primary' => false],
        ];
    } elseif ($user->hasRole('parent')) {
        $navItems = [
            ['route' => 'mobile.home', 'icon' => 'house-fill', 'label' => 'Home', 'primary' => false],
            ['route' => 'mobile.children', 'icon' => 'people', 'label' => 'Children', 'primary' => false],
            ['route' => 'mobile.attendance', 'icon' => 'clipboard-check', 'label' => 'Attend', 'primary' => true],
            ['route' => 'mobile.reports', 'icon' => 'bar-chart', 'label' => 'Reports', 'primary' => false],
            ['route' => 'mobile.profile', 'icon' => 'person-fill', 'label' => 'Profile', 'primary' => false],
        ];
    } else {
        // Admin or other roles
        $navItems = [
            ['route' => 'mobile.home', 'icon' => 'house-fill', 'label' => 'Home', 'primary' => false],
            ['route' => 'mobile.dashboard', 'icon' => 'speedometer2', 'label' => 'Dashboard', 'primary' => false],
            ['route' => 'mobile.students', 'icon' => 'people', 'label' => 'Students', 'primary' => true],
            ['route' => 'mobile.reports', 'icon' => 'bar-chart', 'label' => 'Reports', 'primary' => false],
            ['route' => 'mobile.settings', 'icon' => 'gear-fill', 'label' => 'Settings', 'primary' => false],
        ];
    }
@endphp

<nav class="mobile-bottom-nav">
    @foreach($navItems as $item)
        <a href="{{ route($item['route']) }}" 
           class="nav-item {{ $item['primary'] ? 'nav-item-primary' : '' }} {{ $currentRoute === $item['route'] ? 'active' : '' }}"
           data-route="{{ $item['route'] }}">
            <i class="bi bi-{{ $item['icon'] }}"></i>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>

<style>
    .mobile-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: calc(var(--bottom-nav-height) + var(--safe-bottom));
        padding-bottom: var(--safe-bottom);
        background: var(--bg-dark);
        border-top: 1px solid rgba(207, 164, 111, 0.1);
        display: flex;
        align-items: flex-start;
        justify-content: space-around;
        padding-left: max(8px, var(--safe-left));
        padding-right: max(8px, var(--safe-right));
        z-index: 1000;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.3);
    }

    .nav-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 12px 8px;
        text-decoration: none;
        color: var(--text-muted);
        font-size: 10px;
        font-weight: 500;
        transition: all 0.2s ease;
        border-radius: 12px;
        margin: 4px 2px;
        position: relative;
    }

    .nav-item i {
        font-size: 24px;
        transition: all 0.2s ease;
    }

    .nav-item span {
        white-space: nowrap;
        transition: all 0.2s ease;
    }

    /* Primary Action Button (usually Scan) */
    .nav-item-primary {
        margin-top: -8px;
    }

    .nav-item-primary i {
        font-size: 28px;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        color: var(--bg-dark);
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(207, 164, 111, 0.4);
    }

    .nav-item-primary span {
        color: var(--gold-primary);
        font-weight: 600;
    }

    /* Active State */
    .nav-item.active {
        color: var(--gold-primary);
        background: var(--bg-card);
    }

    .nav-item.active i {
        color: var(--gold-primary);
        transform: scale(1.1);
    }

    .nav-item.active span {
        color: var(--gold-primary);
        font-weight: 600;
    }

    /* Active indicator line */
    .nav-item.active::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 32px;
        height: 3px;
        background: var(--gold-primary);
        border-radius: 0 0 3px 3px;
    }

    /* Remove indicator from primary button */
    .nav-item-primary.active::before {
        display: none;
    }

    /* Hover/Touch feedback */
    .nav-item:active {
        transform: scale(0.95);
        background: var(--bg-card-hover);
    }

    .nav-item-primary:active i {
        transform: scale(0.95);
    }

    /* Hide on desktop */
    @media (min-width: 768px) {
        .mobile-bottom-nav {
            display: none;
        }
    }
</style>

<script>
    // Add active class handling
    document.addEventListener('DOMContentLoaded', function() {
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Haptic feedback
                if ('vibrate' in navigator) {
                    navigator.vibrate(10);
                }
            });
        });
    });
</script>
