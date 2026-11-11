<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    @include('partials.pwa-tags')

    @php
        $destUrl = route('login');
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isAdmin()) {
                $destUrl = route('admin.dashboard');
            } elseif ($user->isTeacher()) {
                $destUrl = route('teacher.dashboard');
            } elseif ($user->isParent()) {
                $destUrl = route('parent.dashboard');
            } else {
                $destUrl = route('home');
            }
        }
        $isMobileDevice = class_exists(\App\Helpers\DeviceHelper::class) && \App\Helpers\DeviceHelper::isMobile();

        // Dynamically resolve video assets with cache-busting timestamp (filemtime)
        $mobilePath  = public_path('videos/Mobile.mp4');
        $desktopPath = public_path('videos/Desktop.mp4');
        $introPath   = public_path('videos/intro.mp4');

        $hasMobile  = file_exists($mobilePath);
        $hasDesktop = file_exists($desktopPath);
        $hasIntro   = file_exists($introPath);

        // Mobile video selection with fallback: Mobile.mp4 -> intro.mp4 -> Desktop.mp4
        if ($hasMobile) {
            $mobileVideoUrl = asset('videos/Mobile.mp4') . '?v=' . filemtime($mobilePath);
        } elseif ($hasIntro) {
            $mobileVideoUrl = asset('videos/intro.mp4') . '?v=' . filemtime($introPath);
        } elseif ($hasDesktop) {
            $mobileVideoUrl = asset('videos/Desktop.mp4') . '?v=' . filemtime($desktopPath);
        } else {
            $mobileVideoUrl = asset('videos/Mobile.mp4') . '?v=' . time();
        }

        // Desktop video selection with fallback: Desktop.mp4 -> intro.mp4 -> Mobile.mp4
        if ($hasDesktop) {
            $desktopVideoUrl = asset('videos/Desktop.mp4') . '?v=' . filemtime($desktopPath);
        } elseif ($hasIntro) {
            $desktopVideoUrl = asset('videos/intro.mp4') . '?v=' . filemtime($introPath);
        } elseif ($hasMobile) {
            $desktopVideoUrl = asset('videos/Mobile.mp4') . '?v=' . filemtime($mobilePath);
        } else {
            $desktopVideoUrl = asset('videos/Desktop.mp4') . '?v=' . time();
        }

        $initialVideoSrc = $isMobileDevice ? $mobileVideoUrl : $desktopVideoUrl;
    @endphp



    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            background: #110A0A;
            background-color: #110A0A;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            height: 100%;
            width: 100%;
        }

        /* ── VIDEO ── */
        #introVideo {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            z-index: 1;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: high-quality;
            filter: contrast(1.05) brightness(1.02) saturate(1.06);
        }

        @media (max-width: 768px), (orientation: portrait) {
            #introVideo {
                object-fit: contain;
                object-position: center center;
            }
            .vignette {
                background: radial-gradient(circle at center, transparent 60%, rgba(0,0,0,0.5) 100%);
            }
            .overlay {
                background: linear-gradient(to top, rgba(0,0,0,0.65) 0%, transparent 28%, transparent 72%, rgba(0,0,0,0.4) 100%);
            }
        }

        /* ── VIGNETTE OVERLAY ── */
        .vignette {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at center, transparent 40%, rgba(0,0,0,0.45) 100%);
            z-index: 2;
            pointer-events: none;
        }

        /* ── BRANDING OVERLAY ── */
        .branding {
            position: fixed;
            bottom: 60px;
            left: 40px;
            z-index: 5;
            color: white;
            animation: slideUpFade 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.3s;
            opacity: 0;
            transform: translateY(20px);
        }
        .branding h1 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 8px;
            text-shadow: 0 4px 12px rgba(0,0,0,0.5);
            background: linear-gradient(135deg, #fff, #f3e7cd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .branding p {
            font-size: 1rem;
            color: rgba(255,255,255,0.7);
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        @media (max-width: 768px) {
            .branding {
                bottom: 75px;
                left: 20px;
                right: 20px;
            }
            .branding h1 { font-size: 1.8rem; }
            .branding p { font-size: 0.8rem; }
        }

        @keyframes slideUpFade {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── OVERLAY ── */
        .overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.5) 0%, transparent 45%);
            z-index: 3;
            pointer-events: none;
        }

        /* ── SKIP BUTTON ── */
        .skip-btn {
            position: fixed;
            bottom: 40px;
            right: 40px;
            z-index: 1000;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.25);
            padding: 12px 26px;
            border-radius: 99px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            animation: fadeIn 0.8s ease forwards;
            animation-delay: 0.3s;
            opacity: 0;
            pointer-events: auto !important;
            touch-action: manipulation;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .skip-btn:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
            transform: scale(1.05);
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
            color: #ffffff;
        }
        .skip-btn:active {
            transform: scale(0.96);
        }
        @media (max-width: 768px) {
            .skip-btn {
                bottom: 24px;
                right: 20px;
                padding: 8px 18px;
                font-size: 0.82rem;
            }
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }

        /* Progress bar */
        .progress-bar-container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.1);
            z-index: 10;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #b91c1c, #f59e0b, #cfa46f);
            width: 0%;
            transition: width 0.15s linear;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
            border-radius: 0 4px 4px 0;
        }

        /* Keep a useful screen visible while the next document loads. */
        [hidden] { display: none !important; }
        .intro-leaving .branding {
            top: 50%;
            bottom: auto;
            left: 50%;
            right: auto;
            width: min(88%, 560px);
            text-align: center;
            opacity: 1;
            transform: translate(-50%, -50%);
            animation: none;
        }
        .intro-leaving .branding h1 { overflow-wrap: anywhere; }
        .intro-logo { display: block; margin: 0 auto 24px; object-fit: contain; }
        .branding .intro-status { font-size: 1rem; line-height: 1.6; letter-spacing: 0; text-transform: none; }
        .branding .intro-recovery { margin-top: 12px; font-size: .85rem; line-height: 1.6; letter-spacing: 0; text-transform: none; }
        .intro-leaving .skip-btn { animation: none; opacity: 1; }
        .skip-btn:focus-visible { outline: 2px solid #cfa46f; outline-offset: 4px; }
        @media (prefers-reduced-motion: reduce) {
            .branding, .skip-btn { animation: none; opacity: 1; }
            .branding { transform: none; }
            .intro-leaving .branding { transform: translate(-50%, -50%); }
        }
    </style>
</head>
<body>

    <!-- Video: Desktop intro = Desktop.mp4, Mobile intro = Mobile.mp4 -->
    <video id="introVideo" data-skip-intro autoplay muted playsinline preload="auto">
        <source id="introVideoSrcMobile" src="{{ $mobileVideoUrl }}" type="video/mp4" media="(max-width: 768px), (orientation: portrait)">
        <source id="introVideoSrcDesktop" src="{{ $desktopVideoUrl }}" type="video/mp4" media="(min-width: 769px) and (orientation: landscape)">
        <source id="introVideoSrc" src="{{ $initialVideoSrc }}" type="video/mp4">
    </video>

    <!-- Cinematic Overlays -->
    <div class="vignette" data-skip-intro></div>
    <div class="overlay" data-skip-intro></div>

    <!-- Animated Branding -->
    <div class="branding" data-skip-intro>
        <img src="{{ asset('images/logo.png') }}" class="intro-logo" id="introLogo" alt="" width="96" height="96" hidden>
        <h1>{{ config('app.name', 'Smart Classroom') }}</h1>
        <p id="introTagline">Intelligent Attendance</p>
        <p class="intro-status" id="introStatus" role="status" aria-live="polite" hidden>{{ Auth::check() ? 'Opening your dashboard…' : 'Opening sign-in…' }}</p>
        <p class="intro-recovery" id="introRecovery" hidden>The next page is taking longer than usual. Tap “Continue now” to try again.</p>
    </div>

    <!-- Progress bar -->
    <div class="progress-bar-container" data-skip-intro>
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <!-- Skip button -->
    <a href="{{ $destUrl }}" class="skip-btn" id="skipBtn" aria-label="Skip Intro Video">
        <span id="skipBtnLabel">Skip Intro</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </a>

    <script nonce="{{ csp_nonce() }}">
        const video       = document.getElementById('introVideo');
        const skipBtn     = document.getElementById('skipBtn');
        const destUrl     = @json($destUrl);
        const progressBar = document.getElementById('progressBar');
        const status      = document.getElementById('introStatus');
        const recovery    = document.getElementById('introRecovery');
        const skipLabel   = document.getElementById('skipBtnLabel');
        const isMobileScreen = window.innerWidth <= 768 || 
                               window.screen.width <= 768 || 
                               (window.matchMedia && window.matchMedia('(max-width: 768px), (orientation: portrait)').matches) ||
                               /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
        const INTRO_START_TIME = 0.0; // Play high-definition animation fully from the beginning
        let hasTransitioned = false;
        let hasInitialized = false;
        let recoveryTimer = null;

        // Strict mapping: Desktop intro = Desktop.mp4, Mobile intro = Mobile.mp4
        const MOBILE_VIDEO  = '{{ $mobileVideoUrl }}';
        const DESKTOP_VIDEO = '{{ $desktopVideoUrl }}';
        const targetVideo   = isMobileScreen ? MOBILE_VIDEO : DESKTOP_VIDEO;

        // Directly bind video.src on the video element for 100% reliable cross-browser source switching
        if (video) {
            const cur = video.currentSrc || video.src || '';
            if (!cur || !cur.includes(targetVideo)) {
                video.src = targetVideo;
                const srcEl = document.getElementById('introVideoSrc');
                if (srcEl) srcEl.src = targetVideo;
                video.load();
            }
        }

        // Handle dynamic viewport / orientation changes gracefully
        window.addEventListener('resize', () => {
            if (hasTransitioned || !video) return;
            const nowMobile = window.innerWidth <= 768 || 
                              window.screen.width <= 768 || 
                              (window.matchMedia && window.matchMedia('(orientation: portrait)').matches);
            const shouldTarget = nowMobile ? MOBILE_VIDEO : DESKTOP_VIDEO;
            const cur = video.currentSrc || video.src || '';

            if (!cur.includes(shouldTarget)) {
                const prev = video.currentTime || 0;
                video.src = shouldTarget;
                const srcEl = document.getElementById('introVideoSrc');
                if (srcEl) srcEl.src = shouldTarget;
                video.currentTime = prev;
                video.play().catch(() => {});
            }
        }, { passive: true });

        function goToNext(e) {
            // After starting navigation, leave the real link available for a
            // manual retry. Automatic media events must never restart the request.
            if (hasTransitioned) return;
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
            }
            hasTransitioned = true;
            document.body.classList.add('intro-leaving');
            document.getElementById('introLogo').hidden = false;
            document.getElementById('introTagline').hidden = true;
            if (status) status.hidden = false;
            if (skipLabel) skipLabel.textContent = 'Continue now';
            if (skipBtn) skipBtn.setAttribute('aria-label', 'Continue to the next page');

            // Release the video download so it does not compete with the page
            // request on a slow mobile connection. The branded status stays visible.
            if (video) {
                video.hidden = true;
                try {
                    video.pause();
                    video.removeAttribute('src');
                    video.querySelectorAll('source').forEach(source => source.removeAttribute('src'));
                    video.load();
                } catch (error) {
                    // A media failure must never prevent skipping the intro.
                }
            }
            recoveryTimer = setTimeout(() => {
                if (recovery) recovery.hidden = false;
            }, 8000);
            window.location.replace(destUrl);
        }

        // 1. One click handler covers mouse, touch, and keyboard activation.
        if (skipBtn) {
            skipBtn.addEventListener('click', goToNext);
        }

        // 2. Intro surfaces can skip, but links and PWA dialog controls keep
        // their own behavior. This listener calls preventDefault and is not passive.
        document.addEventListener('click', (e) => {
            if (e.defaultPrevented || e.target?.closest('a, button, input, select, textarea, [role="dialog"]')) return;
            if (e.target === document.body || e.target?.closest('[data-skip-intro]')) goToNext(e);
        });

        // 3. Keyboard navigation (Space, Enter, Escape)
        document.addEventListener('keydown', (e) => {
            if (e.defaultPrevented || (e.target !== document.body && e.target !== video)) return;
            if (['Space', 'Enter', 'Escape'].includes(e.code) || e.key === ' ' || e.key === 'Enter' || e.key === 'Escape') {
                goToNext(e);
            }
        });

        // 4. Video Events & Playback Monitoring
        if (video) {
            const startSmoothPlayback = () => {
                if (hasInitialized || hasTransitioned) return;
                hasInitialized = true;

                if (video.currentTime < INTRO_START_TIME) {
                    try {
                        video.currentTime = INTRO_START_TIME;
                    } catch (err) {}
                }

                // Native smooth 60fps playback without frame drops or jitter
                try {
                    video.playbackRate = 1.0;
                } catch (e) {}

                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.catch(() => {
                        setTimeout(() => goToNext(), 300);
                    });
                }
            };

            if (video.readyState >= 1) {
                startSmoothPlayback();
            } else {
                video.addEventListener('loadedmetadata', startSmoothPlayback, { once: true });
                video.addEventListener('canplay', startSmoothPlayback, { once: true });
            }

            // Real-time progress bar calibrated across full duration
            video.addEventListener('timeupdate', () => {
                if (video.duration && !isNaN(video.duration) && video.duration > 0) {
                    const pct = Math.min(100, Math.max(0, (video.currentTime / video.duration) * 100));
                    if (progressBar) progressBar.style.width = pct + '%';
                }
            });

            // Smooth finish: allow the final frame to linger gracefully before navigating
            video.addEventListener('ended', () => {
                if (progressBar) progressBar.style.width = '100%';
                setTimeout(() => goToNext(), 600);
            });

            video.addEventListener('error', () => goToNext());
        }

        // 5. Safety Watchdog Timeout: Never let intro freeze indefinitely
        setTimeout(() => {
            if (!hasTransitioned) {
                goToNext();
            }
        }, 12000);

        window.addEventListener('pagehide', () => clearTimeout(recoveryTimer));
    </script>
</body>
</html>
