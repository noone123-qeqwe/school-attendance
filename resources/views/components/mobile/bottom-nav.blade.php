@php
    $user = auth()->user();
    $currentRoute = Route::currentRouteName();
    $isStudent = $user->isStudent();
    
    // Role-based navigation items
    $navItems = [];
    
    if ($isStudent) {
        $navItems = [
            ['route' => 'mobile.home',       'icon' => 'house-fill',     'label' => 'Home',    'primary' => false, 'scan' => false],
            ['route' => 'mobile.attendance',  'icon' => 'clipboard-check','label' => 'Attend',  'primary' => false, 'scan' => false],
            ['route' => 'mobile.scan',        'icon' => 'qr-code-scan',   'label' => 'Scan',    'primary' => true,  'scan' => true],
            ['route' => 'mobile.history',     'icon' => 'clock-history',  'label' => 'History', 'primary' => false, 'scan' => false],
            ['route' => 'mobile.profile',     'icon' => 'person-fill',    'label' => 'Profile', 'primary' => false, 'scan' => false],
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

<nav class="mobile-bottom-nav" id="mobileBottomNav">
    @foreach($navItems as $item)
        @if($item['scan'] && $isStudent)
            {{-- Student Scan button: always opens the scanner modal directly --}}
            <button type="button"
                    class="nav-item nav-item-primary {{ $currentRoute === $item['route'] ? 'active' : '' }}"
                    id="mobileNavScanBtn"
                    data-action="open-scanner"
                    onclick="if(typeof mobileScanButtonTapped==='function'){mobileScanButtonTapped(event)}"
                    aria-label="Scan QR Code">
                <i class="bi bi-{{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </button>
        @else
            <a href="{{ route($item['route']) }}"
               class="nav-item {{ $item['primary'] ? 'nav-item-primary' : '' }} {{ $currentRoute === $item['route'] ? 'active' : '' }}"
               data-route="{{ $item['route'] }}">
                <i class="bi bi-{{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endif
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
        background: transparent;
        border: none;
        cursor: pointer;
        -webkit-appearance: none;
        appearance: none;
        font-family: inherit;
    }

    .nav-item i {
        font-size: 24px;
        transition: all 0.2s ease;
    }

    .nav-item span {
        white-space: nowrap;
        transition: all 0.2s ease;
    }

    /* Primary Action Button (Scan) */
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
    // Direct binding for Scan button and touch feedback
    document.addEventListener('DOMContentLoaded', function() {
        const scanBtn = document.getElementById('mobileNavScanBtn');
        if (scanBtn) {
            const triggerScan = function(e) {
                if (e) {
                    if (typeof e.preventDefault === 'function') e.preventDefault();
                    if (typeof e.stopPropagation === 'function') e.stopPropagation();
                }
                if (typeof window.triggerHaptic === 'function') {
                    window.triggerHaptic('medium');
                } else if ('vibrate' in navigator) {
                    navigator.vibrate(15);
                }

                if (typeof window.openStudentScanner === 'function') {
                    window.openStudentScanner('scan');
                } else if (typeof window.mobileScanButtonTapped === 'function') {
                    window.mobileScanButtonTapped(e);
                } else {
                    window.location.href = "{{ route('mobile.scan') }}";
                }
            };

            scanBtn.addEventListener('click', triggerScan);
            scanBtn.addEventListener('touchend', function(e) {
                const now = Date.now();
                if (scanBtn._lastTouch && now - scanBtn._lastTouch < 400) return;
                scanBtn._lastTouch = now;
                triggerScan(e);
            }, { passive: false });
        }

        document.querySelectorAll('.nav-item:not(#mobileNavScanBtn)').forEach(function(el) {
            el.addEventListener('touchstart', function() {
                if ('vibrate' in navigator) { navigator.vibrate(10); }
            }, { passive: true });
        });
    });
</script>
