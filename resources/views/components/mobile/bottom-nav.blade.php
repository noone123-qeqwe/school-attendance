@php
    $user = auth()->user();
    $currentRoute = Route::currentRouteName();
    $isStudent = $user->isStudent();
    
    // Role-based navigation items
    $navItems = [];
    
    if ($isStudent) {
        $navItems = [
            ['route' => 'home',              'icon' => 'house-fill',     'label' => 'Home',    'primary' => false, 'scan' => false],
            ['route' => 'student.classes',   'icon' => 'journal-bookmark-fill', 'label' => 'Classes', 'primary' => false, 'scan' => false],
            ['route' => 'mobile.scan',        'icon' => 'qr-code-scan',   'label' => 'Scan',    'primary' => true,  'scan' => true],
            ['route' => 'student.calendar', 'icon' => 'calendar-event-fill', 'label' => 'Calendar', 'primary' => false, 'scan' => false],
            ['route' => 'profile',           'icon' => 'person-fill',    'label' => 'Profile', 'primary' => false, 'scan' => false],
        ];
    } elseif ($user->isTeacher()) {
        $navItems = [
            ['route' => 'mobile.home',     'icon' => 'house-fill',    'label' => 'Home',     'primary' => false, 'scan' => false],
            ['route' => 'mobile.classes',  'icon' => 'book',          'label' => 'Classes',  'primary' => false, 'scan' => false],
            ['route' => 'mobile.classes',  'icon' => 'qr-code-scan',  'label' => 'Scan',     'primary' => true,  'scan' => false],
            ['route' => 'mobile.students', 'icon' => 'people',        'label' => 'Students', 'primary' => false, 'scan' => false],
            ['route' => 'mobile.profile',  'icon' => 'person-fill',   'label' => 'Profile',  'primary' => false, 'scan' => false],
        ];
    } elseif ($user->isParent()) {
        $navItems = [
            ['route' => 'mobile.home',       'icon' => 'house-fill',     'label' => 'Home',     'primary' => false, 'scan' => false],
            ['route' => 'mobile.children',   'icon' => 'people',         'label' => 'Children', 'primary' => false, 'scan' => false],
            ['route' => 'mobile.attendance', 'icon' => 'clipboard-check','label' => 'Attend',   'primary' => true,  'scan' => false],
            ['route' => 'mobile.reports',    'icon' => 'bar-chart',      'label' => 'Reports',  'primary' => false, 'scan' => false],
            ['route' => 'mobile.profile',    'icon' => 'person-fill',    'label' => 'Profile',  'primary' => false, 'scan' => false],
        ];
    } else {
        // Admin or other roles
        $navItems = [
            ['route' => 'mobile.home',      'icon' => 'house-fill',   'label' => 'Home',      'primary' => false, 'scan' => false],
            ['route' => 'mobile.dashboard', 'icon' => 'speedometer2', 'label' => 'Dashboard', 'primary' => false, 'scan' => false],
            ['route' => 'mobile.students',  'icon' => 'people',       'label' => 'Students',  'primary' => true,  'scan' => false],
            ['route' => 'mobile.reports',   'icon' => 'bar-chart',    'label' => 'Reports',   'primary' => false, 'scan' => false],
            ['route' => 'mobile.settings',  'icon' => 'gear-fill',    'label' => 'Settings',  'primary' => false, 'scan' => false],
        ];
    }
@endphp

<nav class="mobile-bottom-nav" id="mobileBottomNav" aria-label="Mobile navigation">
    @foreach($navItems as $item)
        @php
            $activeAliases = $isStudent ? match($item['route']) {
                'home' => ['home', 'mobile.home'],
                'student.classes' => ['student.classes', 'student.schedule', 'classes.index'],
                'student.calendar' => ['student.calendar', 'student.attendance.calendar', 'mobile.attendance', 'mobile.history', 'attendance.index'],
                'profile' => ['profile', 'mobile.profile'],
                default => [$item['route']],
            } : [$item['route']];
            $isActive = in_array($currentRoute, $activeAliases, true);
        @endphp
        @if($item['scan'] && $isStudent)
            {{-- Open the scanner as a dedicated page, not over the current screen. --}}
            <a href="{{ route($item['route']) }}"
                    class="nav-item nav-item-primary {{ $isActive ? 'active' : '' }}"
                    @if($isActive) aria-current="page" @endif
                    id="mobileNavScanBtn"
                    aria-label="Scan QR Code">
                <span class="nav-scan-jewel" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3.5 8.5V5.5C3.5 4.4 4.4 3.5 5.5 3.5H8.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15.5 3.5H18.5C19.6 3.5 20.5 4.4 20.5 5.5V8.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3.5 15.5V18.5C3.5 19.6 4.4 20.5 5.5 20.5H8.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15.5 20.5H18.5C19.6 20.5 20.5 19.6 20.5 18.5V15.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="7" y="7" width="3.5" height="3.5" rx="1" fill="currentColor"/>
                        <rect x="13.5" y="7" width="3.5" height="3.5" rx="1" fill="currentColor"/>
                        <rect x="7" y="13.5" width="3.5" height="3.5" rx="1" fill="currentColor"/>
                        <circle cx="15.25" cy="15.25" r="1.75" fill="currentColor"/>
                    </svg>
                </span>
                <span>{{ $item['label'] }}</span>
            </a>
        @else
            <a href="{{ route($item['route']) }}"
               class="nav-item {{ $item['primary'] ? 'nav-item-primary' : '' }} {{ $isActive ? 'active' : '' }}"
               @if($isActive) aria-current="page" @endif
               data-route="{{ $item['route'] }}">
                @if($item['primary'])
                    <span class="nav-scan-jewel" aria-hidden="true"><i class="bi bi-{{ $item['icon'] }}"></i></span>
                @else
                    <i class="bi bi-{{ $item['icon'] }}" aria-hidden="true"></i>
                @endif
                <span>{{ $item['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>

<style>
    .mobile-bottom-nav {
        position: fixed;
        bottom: calc(10px + var(--safe-bottom));
        left: calc(10px + var(--safe-left));
        right: calc(10px + var(--safe-right));
        height: 68px;
        border-radius: 24px;
        background: rgba(18, 12, 10, 0.92);
        backdrop-filter: blur(36px) saturate(200%);
        -webkit-backdrop-filter: blur(36px) saturate(200%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-top: 1px solid rgba(207, 164, 111, 0.32);
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding: 0 6px;
        z-index: 1000;
        box-shadow:
            0 16px 40px -6px rgba(0, 0, 0, 0.75),
            0 4px 12px rgba(0, 0, 0, 0.5),
            inset 0 1px 0 rgba(255, 255, 255, 0.12);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
    }

    .nav-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        height: 100%;
        min-height: 44px;
        padding: 6px 4px;
        text-decoration: none;
        color: rgba(225, 215, 198, 0.58);
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.25px;
        transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), color 0.18s ease;
        border-radius: 16px;
        margin: 0;
        position: relative;
        background: transparent;
        border: none;
        cursor: pointer;
        -webkit-appearance: none;
        appearance: none;
        font-family: inherit;
    }

    .nav-item i {
        font-size: 1.2rem;
        line-height: 1;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), color 0.2s ease;
    }

    .nav-item span {
        white-space: nowrap;
        line-height: 1.2;
        transition: color 0.2s ease;
    }

    /* Primary Action Button (Center Gold Scan Jewel) */
    .nav-item-primary {
        flex: 1.15;
        justify-content: flex-end;
        padding-bottom: 6px;
        z-index: 5;
    }

    .nav-scan-jewel {
        position: absolute;
        top: -18px;
        left: 50%;
        transform: translateX(-50%);
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: radial-gradient(circle at 35% 30%, #fff7db 0%, #e5c07b 40%, #b88638 80%, #87571e 100%);
        color: #120704;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3.5px solid #140c0a;
        box-shadow:
            inset 0 2px 2px rgba(255, 255, 255, 0.9),
            inset 0 -2px 4px rgba(0, 0, 0, 0.35),
            0 6px 18px rgba(207, 164, 111, 0.45),
            0 2px 6px rgba(0, 0, 0, 0.6);
        transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.22s ease;
    }

    .nav-scan-jewel svg {
        width: 24px;
        height: 24px;
        color: #140703;
        display: block;
    }

    .nav-scan-jewel i {
        font-size: 1.25rem;
        color: #140703;
    }

    .nav-item-primary span:last-child {
        color: #f3e7cd;
        font-weight: 700;
        font-size: 0.66rem;
    }

    /* Active State */
    .nav-item.active:not(.nav-item-primary) {
        color: #f5dfa8;
        background: transparent;
    }

    .nav-item.active:not(.nav-item-primary) i {
        color: #f5dfa8;
        transform: translateY(-1px) scale(1.08);
        filter: drop-shadow(0 2px 6px rgba(207, 164, 111, 0.5));
    }

    .nav-item.active:not(.nav-item-primary) span {
        color: #f5dfa8;
        font-weight: 700;
    }

    /* Top illuminated accent bar */
    .nav-item.active:not(.nav-item-primary)::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 2.5px;
        border-radius: 99px;
        background: linear-gradient(90deg, #f5dfa8, #cfa46f);
        box-shadow: 0 0 10px rgba(207, 164, 111, 0.85), 0 1px 3px rgba(207, 164, 111, 0.5);
    }

    .nav-item-primary.active::before {
        display: none;
    }

    .nav-item:active {
        transform: scale(0.92);
    }

    .nav-item-primary:active .nav-scan-jewel {
        transform: translateX(-50%) scale(0.88) translateY(2px);
        box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.6), 0 3px 8px rgba(207, 164, 111, 0.25);
    }

    .nav-item * {
        pointer-events: none;
    }

    /* Hide on desktop */
    @media (min-width: 768px) {
        .mobile-bottom-nav {
            display: none;
        }
    }
</style>

<script @cspNonce>
    // Scan actions are handled once by the mobile layout's delegated click listener.
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.nav-item:not(#mobileNavScanBtn)').forEach(function(el) {
            el.addEventListener('touchstart', function() {
                if ('vibrate' in navigator) { navigator.vibrate(10); }
            }, { passive: true });
        });
    });
</script>
