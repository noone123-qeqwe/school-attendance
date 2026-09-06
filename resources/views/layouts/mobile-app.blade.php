<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no, maximum-scale=1, minimum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1a1a1a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Attendance">
    
    <!-- Android/Chrome -->
    <meta name="application-name" content="Smart Attendance">
    
    <!-- Prevent zoom on input focus -->
    <meta name="format-detection" content="telephone=no">

    <title>@yield('title', config('app.name', 'Smart Attendance'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <!-- Bootstrap Icons -->
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
            --safe-top: env(safe-area-inset-top);
            --safe-bottom: env(safe-area-inset-bottom);
            --safe-left: env(safe-area-inset-left);
            --safe-right: env(safe-area-inset-right);

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

        body {
            font-family: 'Figtree', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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

    @stack('styles')
</head>
<body>
    <!-- Mobile Header -->
    @include('components.mobile.header')

    <!-- Main Content -->
    <main class="mobile-app">
        @yield('content')
    </main>

    <!-- Bottom Navigation -->
    @include('components.mobile.bottom-nav')

    <!-- Install Prompt (only shows in browser mode) -->
    {{-- @include('components.mobile.install-prompt') --}}

    <!-- Scripts -->
    <script>
        // CSRF Token setup
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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

        // Prevent pull-to-refresh on iOS
        document.body.addEventListener('touchmove', function(e) {
            if (e.target.closest('.scrollable')) return;
            if (window.scrollY === 0) {
                e.preventDefault();
            }
        }, { passive: false });

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
                }
            }
        }

        // Add haptic to all buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('button, .touchable, .nav-item').forEach(el => {
                el.addEventListener('touchstart', () => haptic('light'), { passive: true });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
