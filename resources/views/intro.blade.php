<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #000;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }

        /* ── VIDEO ── */
        #introVideo {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            filter: brightness(1.05) contrast(1.1) saturate(1.15); /* Enhance colors */
        }

        /* ── VIGNETTE OVERLAY ── */
        .vignette {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at center, transparent 30%, rgba(0,0,0,0.8) 100%);
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
            animation: slideUpFade 1.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.5s;
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
                bottom: 80px;
                left: 24px;
            }
            .branding h1 { font-size: 2rem; }
            .branding p { font-size: 0.85rem; }
        }

        @keyframes slideUpFade {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── OVERLAY (subtle dark gradient at bottom) ── */
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
            z-index: 10;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 12px 26px;
            border-radius: 99px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            animation: fadeIn 1s ease forwards;
            animation-delay: 1.5s;
            opacity: 0;
        }
        .skip-btn:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.4);
            transform: scale(1.08);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
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
            transition: width 0.3s linear;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
            border-radius: 0 4px 4px 0;
        }

        /* ── FADE OUT OVERLAY ── */
        .fade-out {
            position: fixed;
            inset: 0;
            background: white;
            z-index: 100;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.8s ease;
        }
        .fade-out.active {
            opacity: 1;
            pointer-events: all;
        }
    </style>
</head>
<body>

    <!-- Video -->
    <video id="introVideo" autoplay muted playsinline>
        <source id="introSource" src="{{ asset('videos/intro.mp4') }}" type="video/mp4">
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
    <button class="skip-btn" onclick="goToLogin()">
        Skip Intro <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
    </button>

    <!-- Fade out overlay -->
    <div class="fade-out" id="fadeOut"></div>

    <script>
        const video       = document.getElementById('introVideo');
        const introSource = document.getElementById('introSource');

        // Use mobile intro on small viewports
        if (window.innerWidth <= 768) {
            introSource.src = '{{ asset("videos/intro_mobile.mp4") }}';
            // reload video with new source
            video.load();
        }
        const progressBar = document.getElementById('progressBar');
        const fadeOut     = document.getElementById('fadeOut');

        // Update progress bar as video plays
        video.addEventListener('timeupdate', () => {
            if (video.duration) {
                const pct = (video.currentTime / video.duration) * 100;
                progressBar.style.width = pct + '%';
            }
        });

        // Auto-redirect when video ends
        video.addEventListener('ended', () => {
            goToLogin();
        });

        // If video fails to load, redirect immediately
        video.addEventListener('error', () => {
            window.location.href = '{{ route("login") }}';
        });

        function goToLogin() {
            fadeOut.classList.add('active');
            setTimeout(() => {
                window.location.href = '{{ route("login") }}';
            }, 800);
        }
    </script>
</body>
</html>
