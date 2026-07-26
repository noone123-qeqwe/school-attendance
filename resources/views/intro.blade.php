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
        }

        /* ── OVERLAY (subtle dark gradient at bottom) ── */
        .overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.5) 0%, transparent 60%);
            z-index: 2;
            pointer-events: none;
        }

        /* ── SKIP BUTTON ── */
        .skip-btn {
            position: fixed;
            bottom: 36px;
            right: 36px;
            z-index: 10;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.3);
            padding: 10px 22px;
            border-radius: 99px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.3px;
        }
        .skip-btn:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
            transform: scale(1.05);
        }

        /* Progress bar */
        .progress-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #800000, #f59e0b);
            z-index: 10;
            width: 0%;
            transition: width 0.3s linear;
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

    <!-- Video: desktop default, switch to mobile file on small screens -->
    <video id="introVideo" autoplay muted playsinline>
        <source id="introSource" src="{{ asset('videos/intro.mp4') }}" type="video/mp4">
    </video>

    <!-- Overlay gradient -->
    <div class="overlay"></div>

    <!-- Progress bar -->
    <div class="progress-bar" id="progressBar"></div>

    <!-- Skip button -->
    <button class="skip-btn" onclick="goToLogin()">
        Skip <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
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
