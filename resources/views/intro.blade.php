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
            } elseif ($user->isTeacher() || $user->isDepartmentHead()) {
                $destUrl = route('teacher.dashboard');
            } elseif ($user->isParent()) {
                $destUrl = route('parent.dashboard');
            } else {
                $destUrl = route('home');
            }
        }
        $isMobileDevice = class_exists(\App\Helpers\DeviceHelper::class) && \App\Helpers\DeviceHelper::isMobile();
        $mobileVideoUrl = asset('videos/Mobile.mp4');
        $desktopVideoUrl = asset('videos/Desktop.mp4');
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
                object-fit: cover;
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

        /* ── FADE OUT OVERLAY ── */
        .fade-out {
            position: fixed;
            inset: 0;
            background: #110A0A;
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease-out;
        }
        .fade-out.active {
            opacity: 1;
            pointer-events: all;
        }
    </style>
</head>
<body>

    <!-- Video: Desktop intro = Desktop.mp4, Mobile intro = Mobile.mp4 -->
    <video id="introVideo" autoplay muted playsinline preload="auto" src="{{ $initialVideoSrc }}">
        <source id="introVideoSrcMobile" src="{{ $mobileVideoUrl }}" type="video/mp4" media="(max-width: 768px), (orientation: portrait)">
        <source id="introVideoSrcDesktop" src="{{ $desktopVideoUrl }}" type="video/mp4" media="(min-width: 769px) and (orientation: landscape)">
        <source id="introVideoSrc" src="{{ $initialVideoSrc }}" type="video/mp4">
    </video>

    <!-- Cinematic Overlays -->
    <div class="vignette"></div>
    <div class="overlay"></div>

    <!-- Animated Branding -->
    <div class="branding">
        <h1>{{ config('app.name', 'Smart Classroom') }}</h1>
        <p>Intelligent Attendance</p>
    </div>

    <!-- Progress bar -->
    <div class="progress-bar-container">
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <!-- Skip button -->
    <a href="{{ $destUrl }}" class="skip-btn" id="skipBtn" role="button" aria-label="Skip Intro Video">
        <span>Skip Intro</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </a>

    <!-- Fade out overlay -->
    <div class="fade-out" id="fadeOut"></div>

    <script nonce="{{ csp_nonce() }}">
        const video       = document.getElementById('introVideo');
        const skipBtn     = document.getElementById('skipBtn');
        const destUrl     = @json($destUrl);
        const progressBar = document.getElementById('progressBar');
        const fadeOut     = document.getElementById('fadeOut');
        const isMobileScreen = window.innerWidth <= 768 || 
                               window.screen.width <= 768 || 
                               /Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
        const INTRO_START_TIME = 0.0; // Play high-definition animation fully from the beginning
        let hasTransitioned = false;
        let hasInitialized = false;

        // Strict mapping: Desktop intro = Desktop.mp4, Mobile intro = Mobile.mp4
        const MOBILE_VIDEO  = '{{ $mobileVideoUrl }}';
        const DESKTOP_VIDEO = '{{ $desktopVideoUrl }}';
        const targetVideo   = isMobileScreen ? MOBILE_VIDEO : DESKTOP_VIDEO;

        // Directly bind video.src on the video element for 100% reliable cross-browser source switching
        if (video) {
            const cur = (video.currentSrc || video.src || '').toLowerCase();
            const shouldBeMobile = isMobileScreen;
            const isCurrentlyMobile = cur.includes('mobile.mp4');
            const isCurrentlyDesktop = cur.includes('desktop.mp4');

            if ((shouldBeMobile && !isCurrentlyMobile) || (!shouldBeMobile && !isCurrentlyDesktop)) {
                video.src = targetVideo;
                const srcEl = document.getElementById('introVideoSrc');
                if (srcEl) srcEl.src = targetVideo;
                video.load();
            }
        }

        // Handle dynamic viewport / orientation changes gracefully
        window.addEventListener('resize', () => {
            if (hasTransitioned || !video) return;
            const nowMobile = window.innerWidth <= 768 || window.screen.width <= 768;
            const cur = (video.currentSrc || video.src || '').toLowerCase();
            const shouldTarget = nowMobile ? MOBILE_VIDEO : DESKTOP_VIDEO;
            const isCorrect = nowMobile ? cur.includes('mobile.mp4') : cur.includes('desktop.mp4');

            if (!isCorrect) {
                const prev = video.currentTime || 0;
                video.src = shouldTarget;
                const srcEl = document.getElementById('introVideoSrc');
                if (srcEl) srcEl.src = shouldTarget;
                video.currentTime = prev;
                video.play().catch(() => {});
            }
        }, { passive: true });

        function goToNext(e) {
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
            }
            if (hasTransitioned) return;
            hasTransitioned = true;

            if (fadeOut) {
                fadeOut.classList.add('active');
            }

            window.location.href = destUrl;
            setTimeout(() => {
                window.location.replace(destUrl);
            }, 50);
        }

        // 1. Skip button click & touch
        if (skipBtn) {
            skipBtn.addEventListener('click', goToNext);
            skipBtn.addEventListener('touchend', goToNext);
        }

        // 2. Click/Tap anywhere on screen to skip
        document.addEventListener('click', (e) => {
            goToNext(e);
        }, { passive: true });

        // 3. Keyboard navigation (Space, Enter, Escape)
        document.addEventListener('keydown', (e) => {
            if (['Space', 'Enter', 'Escape'].includes(e.code) || e.key === ' ' || e.key === 'Enter' || e.key === 'Escape') {
                goToNext(e);
            }
        });

        // 4. Video Events & Playback Monitoring
        if (video) {
            const startSmoothPlayback = () => {
                if (hasInitialized) return;
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
    </script>
</body>
</html>
