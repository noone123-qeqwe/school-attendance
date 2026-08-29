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
    @endphp

    <!-- Instant Preload Destination Page -->
    <link rel="prefetch" href="{{ $destUrl }}">
    <link rel="prerender" href="{{ $destUrl }}">

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
            z-index: 1;
            filter: brightness(1.05) contrast(1.1) saturate(1.15);
        }

        /* ── VIGNETTE OVERLAY ── */
        .vignette {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at center, transparent 30%, rgba(0,0,0,0.8) 100%);
            z-index: 2;
            pointer-events: none;
        }

        @keyframes slideUpFade {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── OVERLAY ── */
        .overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
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
                bottom: 30px;
                right: 20px;
                padding: 10px 20px;
                font-size: 0.85rem;
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

    <!-- Video (Mobile & Desktop native sources) -->
    <video id="introVideo" autoplay muted playsinline preload="auto" poster="{{ asset('images/logo.png') }}">
        <source src="{{ asset('videos/intro_mobile.mp4') }}" type="video/mp4" media="(max-width: 768px)">
        <source src="{{ asset('videos/intro.mp4') }}" type="video/mp4">
    </video>

    <!-- Cinematic Overlays -->
    <div class="vignette"></div>
    <div class="overlay"></div>

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
        let hasTransitioned = false;

        function goToNext(e) {
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
            }
            if (hasTransitioned) return;
            hasTransitioned = true;

            if (fadeOut) {
                fadeOut.classList.add('active');
            }

            // Navigate immediately
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
            // Attempt playback; if autoplay is prevented by browser policy, proceed
            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.catch(() => {
                    // Autoplay was prevented by browser policy - skip smoothly rather than freezing
                    setTimeout(() => goToNext(), 400);
                });
            }

            // Real-time progress bar and end-point detection
            video.addEventListener('timeupdate', () => {
                if (video.duration && !isNaN(video.duration) && video.duration > 0) {
                    const pct = Math.min(100, (video.currentTime / video.duration) * 100);
                    if (progressBar) progressBar.style.width = pct + '%';

                    if (video.currentTime >= (video.duration - 0.2)) {
                        goToNext();
                    }
                }
            });

            video.addEventListener('ended', () => goToNext());
            video.addEventListener('error', () => goToNext());
            video.addEventListener('stalled', () => {
                // If video stalls for more than 1s, proceed
                setTimeout(() => {
                    if (video.paused && !hasTransitioned) goToNext();
                }, 1000);
            });
        }

        // 5. Safety Watchdog Timeout: Never let intro freeze for more than 4.5 seconds under any circumstance
        setTimeout(() => {
            if (!hasTransitioned) {
                goToNext();
            }
        }, 4500);
    </script>
</body>
</html>
