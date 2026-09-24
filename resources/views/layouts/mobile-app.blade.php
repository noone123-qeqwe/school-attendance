<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1a1a1a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Smart Attendance">
    
    <!-- Android/Chrome -->
    <meta name="application-name" content="Smart Attendance">
    
    <!-- Prevent zoom on input focus -->
    <meta name="format-detection" content="telephone=no">

    <title>@yield('title', config('app.name', 'Smart Attendance'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Mobile Styles -->
    <style>
        :root {
            /* Colors */
            --gold-primary: #CFA46F;
            --gold-light: #F3E7CD;
            --gold-dark: #B39B82;
            --bg-dark: #1a1a1a;
            --bg-card: rgba(207, 164, 111, 0.08);
            --bg-card-hover: rgba(207, 164, 111, 0.12);
            --text-primary: #F3E7CD;
            --text-secondary: #B39B82;
            --text-muted: #64748B;
            --success: #22C55E;
            --warning: #F59E0B;
            --error: #EF4444;
            --info: #3B82F6;

            /* Spacing */
            --space-1: 4px;
            --space-2: 8px;
            --space-3: 12px;
            --space-4: 16px;
            --space-5: 20px;
            --space-6: 24px;
            --space-8: 32px;
            --space-10: 40px;
            --space-12: 48px;

            /* Safe Areas */
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
            --app-height: 100dvh;

            /* Sizes */
            --header-height: 56px;
            --bottom-nav-height: 64px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html {
            height: 100%;
            height: -webkit-fill-available;
            scroll-behavior: smooth;
            overscroll-behavior: none !important;
            overscroll-behavior-y: none !important;
            overscroll-behavior-x: none !important;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            min-height: 100dvh;
            min-height: var(--app-height, 100dvh);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overscroll-behavior: none !important;
            overscroll-behavior-y: none !important;
            overscroll-behavior-x: none !important;
            -webkit-overflow-scrolling: touch;
        }

        /* Hide body overflow when in browser (not standalone PWA) */
        @media (display-mode: browser) {
            body {
                padding-top: 0;
            }
        }

        /* Fullscreen mode for standalone PWA */
        @media (display-mode: standalone) {
            body {
                /* Already handled by default styles */
            }
        }

        @media (display-mode: fullscreen) {
            body {
                /* Full immersive mode */
            }
        }

        /* Mobile App Container */
        .mobile-app {
            min-height: 100vh;
            min-height: 100dvh;
            min-height: var(--app-height, 100dvh);
            box-sizing: border-box;
            padding-top: calc(var(--header-height) + var(--safe-top));
            padding-bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 16px);
            padding-left: max(16px, var(--safe-left));
            padding-right: max(16px, var(--safe-right));
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Disable text selection on UI elements */
        button, .nav-item, .header-btn {
            -webkit-user-select: none;
            user-select: none;
        }

        /* Touch feedback */
        .touchable {
            transition: transform 0.1s ease, opacity 0.1s ease;
        }

        .touchable:active {
            transform: scale(0.95);
            opacity: 0.8;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gold-dark);
            border-radius: 2px;
        }
    </style>

    @include('partials.pwa-tags')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/experience-refinements.css') }}?v={{ filemtime(public_path('css/experience-refinements.css')) }}">
</head>
<body class="mobile-app-layout">
    <!-- Mobile Header -->
    @include('components.mobile.header')

    <!-- Main Content -->
    <main class="mobile-app">
        @yield('content')
    </main>

    <!-- Bottom Navigation -->
    @include('components.mobile.bottom-nav')

    <!-- Install Prompt (only shows in browser mode, hidden when installed) -->
    @include('components.mobile.install-prompt')

    <!-- Scanner Modal (Students only) -->
    @auth
        @if(auth()->user()->isStudent() || auth()->user()->hasRole('student'))
            @include('partials.student-scanner-modal')
            @include('partials.student-presence-guardian')
        @endif
    @endauth

    <!-- Scripts -->
    <script @cspNonce>
        // CSRF Token setup
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        window.csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        // Check if running as installed PWA
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                            window.navigator.standalone || 
                            document.referrer.includes('android-app://');

        console.log('PWA Standalone Mode:', isStandalone);

        // Add class to body for CSS targeting
        if (isStandalone) {
            document.documentElement.classList.add('standalone-mode');
        } else {
            document.documentElement.classList.add('browser-mode');
        }

        // Simple haptic feedback
        function haptic(type = 'light') {
            if ('vibrate' in navigator) {
                switch(type) {
                    case 'light':
                        navigator.vibrate(10);
                        break;
                    case 'medium':
                        navigator.vibrate(20);
                        break;
                    case 'heavy':
                        navigator.vibrate(30);
                        break;
                    case 'success':
                        navigator.vibrate([30, 50, 30]);
                        break;
                    case 'error':
                        navigator.vibrate([50, 30, 50, 30, 50]);
                        break;
                }
            }
        }

        // Alias used by the scanner modal
        window.triggerHaptic = haptic;

        /**
         * mobileScanButtonTapped — opens the QR scanner modal inline or navigates to scan page
         */
        window.mobileScanButtonTapped = function(e) {
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
                e.stopPropagation();
            }
            if ('vibrate' in navigator) { navigator.vibrate(15); }
            if (typeof openStudentScanner === 'function') {
                openStudentScanner('scan');
            } else {
                window.location.href = '{{ route("mobile.scan") }}';
            }
        };

        // Delegated touch/click handler for scanner triggers (CSP-safe, works across all dynamic content)
        function triggerScanAction(e) {
            const scanTrigger = e.target.closest('#mobileNavScanBtn, [data-action="open-scanner"], .scan-open-btn, .quick-action-primary');
            if (scanTrigger) {
                e.preventDefault();
                e.stopPropagation();
                window.mobileScanButtonTapped(e);
            }
        }

        document.addEventListener('click', triggerScanAction, true);

        // Haptic feedback for interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('button, .touchable, .nav-item').forEach(el => {
                el.addEventListener('touchstart', () => haptic('light'), { passive: true });
            });

            // Auto-open QR scanner when ?open_scanner=1 is in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('open_scanner') === '1' && typeof openStudentScanner === 'function') {
                setTimeout(() => openStudentScanner('scan'), 200);
            } else if (urlParams.get('open_code') === '1' && typeof openStudentScanner === 'function') {
                setTimeout(() => openStudentScanner('code'), 200);
            }
        });

        // ── Dynamic Viewport & Safe-Area Synchronization ──
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
                var el = document.activeElement;
                return !!(el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable));
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
    </script>

    <script @cspNonce src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
