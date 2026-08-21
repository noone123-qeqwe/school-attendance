<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#110A0A">
    <title>Offline Mode | {{ config('app.name', 'School Attendance System') }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-180x180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/favicon-32x32.png">
    <style>
        :root {
            --bg-color: #110A0A;
            --card-bg: rgba(26, 17, 16, 0.95);
            --gold: #CFA46F;
            --gold-light: #F3E7CD;
            --gold-glow: rgba(207, 164, 111, 0.25);
            --text-muted: #B39B82;
            --danger: #EF4444;
            --success: #22C55E;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg-color);
            background-image: 
                radial-gradient(circle at 50% 15%, rgba(207, 164, 111, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 85% 85%, rgba(139, 90, 43, 0.06) 0%, transparent 50%);
            color: var(--gold-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
        }

        .offline-container {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border: 1px solid rgba(207, 164, 111, 0.25);
            border-radius: 24px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px var(--gold-glow);
            position: relative;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            animation: fadeIn 0.6s ease-out;
        }

        .offline-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .icon-wrapper {
            position: relative;
            width: 96px;
            height: 96px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-circle {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: rgba(207, 164, 111, 0.1);
            border: 1px solid rgba(207, 164, 111, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        .icon-pulse {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid var(--gold);
            opacity: 0.4;
            animation: ripple 2.5s cubic-bezier(0.1, 0.8, 0.3, 1) infinite;
        }

        @keyframes ripple {
            0% { transform: scale(0.85); opacity: 0.8; }
            100% { transform: scale(1.45); opacity: 0; }
        }

        .wifi-icon {
            width: 44px;
            height: 44px;
            fill: none;
            stroke: var(--gold);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 99px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #F87171;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #EF4444;
            animation: blink 1.5s infinite ease-in-out;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--gold-light);
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        p {
            font-size: 0.92rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .cached-profile-box {
            display: none;
            background: rgba(17, 10, 10, 0.8);
            border: 1px solid rgba(207, 164, 111, 0.2);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: left;
        }

        .cached-profile-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gold);
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cached-profile-name {
            font-size: 1rem;
            font-weight: 700;
            color: #FFFFFF;
        }

        .cached-profile-meta {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .btn-retry {
            width: 100%;
            padding: 14px 24px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, var(--gold) 0%, #8F6E4A 100%);
            color: #110A0A;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 18px rgba(207, 164, 111, 0.35);
        }

        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(207, 164, 111, 0.5);
            background: linear-gradient(135deg, #dfb783 0%, #9e7b54 100%);
        }

        .btn-retry:active {
            transform: translateY(0);
        }

        .reconnect-notice {
            display: none;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.35);
            color: #4ADE80;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .app-footer {
            margin-top: 28px;
            font-size: 0.75rem;
            color: rgba(179, 155, 130, 0.6);
            letter-spacing: 0.5px;
        }

        .spin {
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="offline-container">
        <!-- Reconnect alert -->
        <div class="reconnect-notice" id="reconnectNotice">
            <span>⚡ Connection restored! Reloading page...</span>
        </div>

        <div class="status-badge" id="statusBadge">
            <span class="status-dot"></span> No Connection
        </div>

        <div class="icon-wrapper">
            <div class="icon-pulse"></div>
            <div class="icon-circle">
                <!-- Disconnected Wi-Fi SVG -->
                <svg class="wifi-icon" viewBox="0 0 24 24">
                    <path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.58 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01" />
                </svg>
            </div>
        </div>

        <h1>You're Offline</h1>
        <p id="offlineMsg">
            We can't connect to the School Attendance server right now. Please check your Wi-Fi or mobile data connection.
        </p>

        <!-- Cached Profile Details if present -->
        <div class="cached-profile-box" id="cachedProfileBox">
            <div class="cached-profile-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Cached Student Profile
            </div>
            <div class="cached-profile-name" id="cachedName">Student</div>
            <div class="cached-profile-meta" id="cachedMeta">Attendance ID: --</div>
        </div>

        <button class="btn-retry" id="btnRetry" onclick="handleRetry()">
            <svg id="retryIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
            </svg>
            <span id="retryText">Retry Connection</span>
        </button>

        <div class="app-footer">
            {{ config('app.name', 'School Attendance System') }} &bull; PWA Offline Mode
        </div>
    </div>

    <script>
        // Check for cached profile in localStorage
        try {
            const raw = localStorage.getItem('cached_student_profile');
            if (raw) {
                const profile = JSON.parse(raw);
                if (profile && profile.name) {
                    document.getElementById('cachedName').textContent = profile.name;
                    let metaText = (profile.role ? profile.role.toUpperCase() : 'USER');
                    if (profile.student_number) metaText += ` &bull; ID: ${profile.student_number}`;
                    if (profile.course) metaText += ` &bull; ${profile.course}`;
                    document.getElementById('cachedMeta').innerHTML = metaText;
                    document.getElementById('cachedProfileBox').style.display = 'block';
                }
            }
        } catch (e) {}

        function handleRetry() {
            const btn = document.getElementById('btnRetry');
            const icon = document.getElementById('retryIcon');
            const text = document.getElementById('retryText');
            
            icon.classList.add('spin');
            text.textContent = 'Checking connection...';
            btn.disabled = true;

            fetch('/manifest.json', { method: 'HEAD', cache: 'no-store' })
                .then(() => {
                    text.textContent = 'Connected! Loading...';
                    window.location.reload();
                })
                .catch(() => {
                    setTimeout(() => {
                        icon.classList.remove('spin');
                        text.textContent = 'Still Offline - Try Again';
                        btn.disabled = false;
                    }, 1200);
                });
        }

        // Automatic reconnection detection
        window.addEventListener('online', () => {
            const notice = document.getElementById('reconnectNotice');
            if (notice) {
                notice.style.display = 'flex';
            }
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
    </script>
</body>
</html>
