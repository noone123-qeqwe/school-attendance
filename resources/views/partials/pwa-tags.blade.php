@php
    $swCacheVer = \Illuminate\Support\Facades\Cache::get('pwa_sw_version', '37');
    $swFileMtime = file_exists(public_path('sw.js')) ? filemtime(public_path('sw.js')) : time();
    $swQueryVer = 'v' . preg_replace('/[^0-9]/', '', (string)$swCacheVer) . '_' . $swFileMtime;
@endphp
<!-- PWA Head Meta Tags -->
<meta name="theme-color" content="#110A0A">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Smart Attendance">
<meta name="application-name" content="Smart Attendance">
<meta name="msapplication-TileColor" content="#110A0A">
<meta name="msapplication-TileImage" content="/images/icons/icon-144x144.png">

<!-- PWA Manifest & Icons -->
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
<link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-180x180.png">
<link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
<link rel="apple-touch-icon" sizes="128x128" href="/images/icons/icon-128x128.png">
<link rel="icon" type="image/png" sizes="192x192" href="/images/icons/icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="/images/icons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/images/icons/favicon-16x16.png">

<style>
    /* PWA Install Banners & Overlays */
    .pwa-install-banner {
        position: fixed;
        bottom: calc(88px + env(safe-area-inset-bottom, 16px));
        left: 16px;
        right: 16px;
        max-width: 440px;
        margin: 0 auto;
        background: rgba(26, 17, 16, 0.96);
        border: 1px solid rgba(207, 164, 111, 0.35);
        border-radius: 20px;
        padding: 16px 20px;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.65), 0 0 25px rgba(207, 164, 111, 0.2);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        z-index: 99999;
        display: none;
        align-items: center;
        gap: 16px;
        animation: pwaSlideUp 0.45s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes pwaSlideUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .pwa-banner-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #110A0A;
        border: 1px solid rgba(207, 164, 111, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        overflow: hidden;
    }

    .pwa-banner-icon img {
        width: 36px;
        height: 36px;
        object-fit: contain;
    }

    .pwa-banner-content {
        flex: 1;
        min-width: 0;
    }

    .pwa-banner-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #F3E7CD;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pwa-banner-subtitle {
        font-size: 0.8rem;
        color: #B39B82;
        line-height: 1.35;
    }

    .pwa-banner-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pwa-btn-install {
        background: linear-gradient(135deg, #CFA46F 0%, #8F6E4A 100%);
        color: #110A0A;
        border: none;
        border-radius: 10px;
        padding: 9px 18px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        box-shadow: 0 4px 14px rgba(207, 164, 111, 0.35);
    }

    .pwa-btn-install:hover {
        background: linear-gradient(135deg, #DFB783 0%, #9E7B54 100%);
        transform: translateY(-1px);
    }

    .pwa-btn-close {
        background: transparent;
        border: none;
        color: #B39B82;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: color 0.2s;
    }

    .pwa-btn-close:hover {
        color: #F3E7CD;
    }

    /* Universal PWA Install Guide Modal */
    .pwa-ios-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 100000;
        display: none;
        align-items: flex-end;
        justify-content: center;
        padding: 20px;
        animation: pwaFadeIn 0.3s ease;
    }

    @media (min-width: 769px) {
        .pwa-ios-modal {
            align-items: center;
        }
    }

    .pwa-ios-arrow-pointer {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        margin-top: 14px;
        padding-top: 10px;
        border-top: 1px dashed rgba(207, 164, 111, 0.25);
    }

    @keyframes pwaBounceDown {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(6px); }
    }
    .pwa-bounce-anim {
        animation: pwaBounceDown 1.3s infinite ease-in-out;
    }

    .pwa-btn-loading {
        opacity: 0.8 !important;
        pointer-events: none !important;
        cursor: wait !important;
    }
    .pwa-spinner {
        display: inline-block;
        width: 13px;
        height: 13px;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: pwaSpin 0.75s linear infinite;
        vertical-align: -2px;
        margin-right: 6px;
    }
    @keyframes pwaSpin {
        to { transform: rotate(360deg); }
    }

    .pwa-ios-sheet {
        background: rgba(26, 17, 16, 0.98);
        border: 1px solid rgba(207, 164, 111, 0.35);
        border-radius: 24px;
        padding: 28px 24px;
        max-width: 420px;
        width: 100%;
        color: #F3E7CD;
        text-align: center;
        box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        position: relative;
    }

    .pwa-ios-sheet h3 {
        font-size: 1.15rem;
        font-weight: 700;
        color: #F3E7CD;
        margin-bottom: 10px;
    }

    .pwa-ios-sheet p {
        font-size: 0.88rem;
        color: #B39B82;
        line-height: 1.5;
        margin-bottom: 20px;
    }

    .pwa-ios-steps {
        display: flex;
        flex-direction: column;
        gap: 12px;
        text-align: left;
        margin-bottom: 24px;
        background: rgba(17, 10, 10, 0.7);
        border-radius: 14px;
        padding: 16px;
        border: 1px solid rgba(207, 164, 111, 0.2);
    }

    .pwa-ios-step {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.85rem;
        color: #F3E7CD;
    }

    .pwa-ios-step-num {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #CFA46F;
        color: #110A0A;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    /* Connectivity Toast */
    .pwa-network-toast {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(-60px);
        padding: 10px 22px;
        border-radius: 99px;
        font-size: 0.85rem;
        font-weight: 700;
        z-index: 100000;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
        opacity: 0;
        pointer-events: none;
    }

    .pwa-network-toast.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }

    .pwa-network-toast.offline {
        background: rgba(239, 68, 68, 0.95);
        color: #FFFFFF;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .pwa-network-toast.online {
        background: rgba(34, 197, 94, 0.95);
        color: #FFFFFF;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* ── Big Tech / Enterprise System Update Card (Apple / Linear / Slack Style) ── */
    .pwa-update-banner {
        position: fixed;
        bottom: 24px;
        right: 24px;
        left: auto;
        width: 410px;
        max-width: calc(100vw - 32px);
        background: rgba(14, 6, 9, 0.94);
        border: 1px solid rgba(232, 192, 100, 0.28);
        border-radius: 22px;
        padding: 18px 20px;
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.85), 
                    0 4px 20px rgba(0, 0, 0, 0.5), 
                    inset 0 1px 0 rgba(255, 255, 255, 0.15),
                    0 0 35px rgba(232, 192, 100, 0.08);
        backdrop-filter: blur(28px) saturate(180%);
        -webkit-backdrop-filter: blur(28px) saturate(180%);
        z-index: 100000;
        display: none;
        flex-direction: column;
        gap: 13px;
        animation: pwaSlideUpEnterprise 0.45s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
    }

    /* Ambient Subtle Shimmer Glow */
    .pwa-update-glow {
        position: absolute;
        top: -60px;
        right: -60px;
        width: 140px;
        height: 140px;
        background: radial-gradient(circle, rgba(232, 192, 100, 0.18) 0%, transparent 70%);
        pointer-events: none;
        z-index: 0;
    }

    @keyframes pwaSlideUpEnterprise {
        0% {
            opacity: 0;
            transform: translateY(30px) scale(0.96);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* On mobile / tablets: Gracefully float above navigation with safe area */
    @media (max-width: 768px) {
        .pwa-update-banner {
            bottom: calc(88px + env(safe-area-inset-bottom, 10px));
            left: 12px;
            right: 12px;
            width: auto;
            max-width: calc(100vw - 24px);
            margin: 0 auto;
            padding: 16px;
            border-radius: 20px;
        }
    }

    .pwa-update-banner-header {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        gap: 13px;
    }

    .pwa-update-icon-container {
        position: relative;
        flex-shrink: 0;
    }

    .pwa-update-app-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        border: 1px solid rgba(232, 192, 100, 0.35);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
        background: #18080c;
        display: block;
        object-fit: cover;
    }

    .pwa-update-pulse-indicator {
        position: absolute;
        top: -3px;
        right: -3px;
        width: 11px;
        height: 11px;
        background: #22C55E;
        border: 2px solid #0e0609;
        border-radius: 50%;
        box-shadow: 0 0 10px #22C55E;
        animation: pwaPulseDot 2s infinite ease-in-out;
    }

    @keyframes pwaPulseDot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.75; }
    }

    .pwa-update-text-area {
        flex: 1;
        min-width: 0;
        padding-right: 18px;
    }

    .pwa-update-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 3px;
    }

    .pwa-update-tag {
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: #e8c064;
        background: rgba(232, 192, 100, 0.12);
        border: 1px solid rgba(232, 192, 100, 0.25);
        padding: 2px 7px;
        border-radius: 6px;
    }

    .pwa-update-version-badge {
        font-size: 0.72rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.6);
        font-variant-numeric: tabular-nums;
    }

    .pwa-update-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.02rem;
        font-weight: 700;
        color: #FFFFFF;
        line-height: 1.25;
        letter-spacing: -0.2px;
        margin-bottom: 3px;
    }

    .pwa-update-subtitle {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.68);
        line-height: 1.4;
    }

    .pwa-update-close-btn {
        position: absolute;
        top: -6px;
        right: -6px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.55);
        font-size: 1.25rem;
        cursor: pointer;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        padding: 0;
        transition: all 0.2s ease;
    }

    .pwa-update-close-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #FFFFFF;
        border-color: rgba(255, 255, 255, 0.25);
        transform: scale(1.08);
    }

    /* Highlights Chips */
    .pwa-update-highlights {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 9px 12px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
    }

    .pwa-update-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.76rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.88);
    }

    .pwa-update-chip svg {
        color: #e8c064;
        flex-shrink: 0;
    }

    /* Actions */
    .pwa-update-banner-actions {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
    }

    .pwa-btn-update-later {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
        font-weight: 600;
        font-size: 0.86rem;
        border-radius: 12px;
        padding: 11px 18px;
        cursor: pointer;
        transition: all 0.2s ease;
        touch-action: manipulation;
        white-space: nowrap;
    }

    .pwa-btn-update-later:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #FFFFFF;
        border-color: rgba(255, 255, 255, 0.25);
    }

    .pwa-btn-update-apply {
        flex: 1;
        background: linear-gradient(135deg, #e8c064 0%, #cfa46f 100%);
        color: #0a0305;
        font-weight: 700;
        font-size: 0.88rem;
        border: none;
        border-radius: 12px;
        padding: 11px 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 18px rgba(232, 192, 100, 0.35);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        touch-action: manipulation;
        white-space: nowrap;
    }

    .pwa-btn-update-apply:hover {
        filter: brightness(1.08);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(232, 192, 100, 0.45);
    }

    .pwa-btn-update-apply:active {
        transform: scale(0.97);
    }

    .pwa-btn-arrow-icon {
        transition: transform 0.2s ease;
    }

    .pwa-btn-update-apply:hover .pwa-btn-arrow-icon {
        transform: translateX(3px);
    }

    @keyframes pwaFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>

<!-- Universal PWA Install Guide Modal (Android, iOS & In-App Browsers) -->
<div class="pwa-ios-modal" id="pwaIosModal">
    <div class="pwa-ios-sheet">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <img src="/images/icons/icon-72x72.png" style="width:34px; height:34px; border-radius:8px; border:1px solid rgba(207,164,111,0.3);" alt="App Icon">
                <h3 id="pwaModalTitle" style="margin:0; font-size:1.1rem; color:#F3E7CD; text-align:left;">Install Smart Attendance</h3>
            </div>
            <button type="button" id="pwaModalCloseIcon" style="background:none; border:none; color:#B39B82; font-size:1.4rem; cursor:pointer; line-height:1; padding:4px;">&times;</button>
        </div>
        <p id="pwaModalSub" style="text-align:left; font-size:0.85rem; color:#B39B82; line-height:1.4; margin-bottom:16px;">Add to your home screen for rapid clock-in, instant notifications, and offline access.</p>
        
        <div class="pwa-ios-steps" id="pwaModalSteps">
            <!-- Dynamically populated based on device & browser -->
        </div>

        <button type="button" class="pwa-btn-install" id="pwaIosCloseBtn" style="width: 100%;">Understood</button>
    </div>
</div>

<!-- Enterprise-Grade System Update Notification (Apple / Linear / Slack style) -->
<div class="pwa-update-banner" id="pwaSystemUpdatePopup" style="display: none;">
    <div class="pwa-update-glow"></div>
    <div class="pwa-update-banner-header">
        <div class="pwa-update-icon-container">
            <img src="/images/icons/icon-72x72.png" class="pwa-update-app-icon" alt="Smart Attendance">
            <span class="pwa-update-pulse-indicator" title="New build ready"></span>
        </div>
        <div class="pwa-update-text-area">
            <div class="pwa-update-meta">
                <span class="pwa-update-tag">SYSTEM UPDATE</span>
                <span class="pwa-update-version-badge" id="pwaUpdateVersionBadge">v{{ ltrim((string)$swCacheVer, 'v') }}.0</span>
            </div>
            <div class="pwa-update-title">Software Update Available</div>
            <div class="pwa-update-subtitle">A new version of Smart Attendance is ready with enhanced performance, faster scanning, and security improvements.</div>
        </div>
        <button type="button" class="pwa-update-close-btn" id="pwaDismissUpdatePopupBtn" aria-label="Dismiss">&times;</button>
    </div>

    <div class="pwa-update-highlights">
        <div class="pwa-update-chip">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <span>Faster Clock-In &amp; Sync</span>
        </div>
        <div class="pwa-update-chip">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Latest Security Fixes</span>
        </div>
    </div>

    <div class="pwa-update-banner-actions">
        <button type="button" class="pwa-btn-update-later" id="pwaLaterUpdateBtn">
            Later
        </button>
        <button type="button" class="pwa-btn-update-apply" id="pwaApplyUpdateBtn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pwa-update-spin-icon" style="display:none; animation: ptr-spin 0.8s linear infinite;">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
            </svg>
            <span id="pwaApplyUpdateBtnText">Restart &amp; Update</span>
            <svg class="pwa-btn-arrow-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
    </div>
</div>

<!-- Real-time Connectivity Toast -->
<div class="pwa-network-toast" id="pwaNetworkToast"></div>

<script @cspNonce>
    // ── 1. Register Service Worker & Handle Real-Time Update Notifications ──
    let swRegistration = null;
    let deferredPrompt = null;
    let latestDetectedVersion = null;

    // Track which specific version we already notified about (not just a boolean)
    // so that a NEW version from the server always triggers a fresh popup
    let lastNotifiedVersion = null;

    function showAppUpdatePopup(version, force = false) {
        if (version) latestDetectedVersion = version;
        
        const sessionDismissed = sessionStorage.getItem('pwa_update_dismissed_ver');

        // Only block if this EXACT version was dismissed AND not forced
        if (!force && sessionDismissed && latestDetectedVersion && sessionDismissed === latestDetectedVersion) {
            return;
        }

        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) {
            const badge = document.getElementById('pwaUpdateVersionBadge');
            if (badge && latestDetectedVersion) {
                const vStr = String(latestDetectedVersion);
                badge.textContent = vStr.startsWith('v') ? vStr : 'v' + vStr;
            }
            // Re-trigger slide-up animation by re-inserting the element
            popup.style.display = 'none';
            void popup.offsetHeight; // force reflow
            popup.style.display = 'flex';
        }

        // Trigger multi-channel update alerts ONCE PER NEW VERSION
        // If the server version changed, this is a new update — always notify again
        const isNewVersion = !lastNotifiedVersion || lastNotifiedVersion !== latestDetectedVersion;
        if (isNewVersion) {
            lastNotifiedVersion = latestDetectedVersion;

            // 1. Mobile Haptic Vibration Feedback
            if (window.triggerHaptic) {
                window.triggerHaptic('success');
            } else if (navigator.vibrate) {
                navigator.vibrate([100, 50, 100]);
            }

            // 3. Native OS/Browser Push Notification if permitted
            if ('Notification' in window && Notification.permission === 'granted') {
                try {
                    const notifTitle = '🚀 Smart Attendance Update Available';
                    const notifOptions = {
                        body: 'A new version of the attendance portal is ready. Tap to load the latest features and optimizations.',
                        icon: '/images/icons/icon-192x192.png',
                        badge: '/images/icons/icon-72x72.png',
                        vibrate: [100, 50, 100],
                        tag: 'app-update-' + (latestDetectedVersion || Date.now()),
                        renotify: true,
                        data: { url: window.location.href, action: 'update' }
                    };

                    if (swRegistration && swRegistration.showNotification) {
                        swRegistration.showNotification(notifTitle, notifOptions);
                    } else {
                        new Notification(notifTitle, notifOptions);
                    }
                } catch(e) {}
            }

            // 4. Update In-App Notification Bell & Badge in Real-Time
            if (typeof window.refreshNotificationBell === 'function') {
                try { window.refreshNotificationBell(); } catch(e) {}
            }
        }
    }

    function hideAppUpdatePopup(version) {
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) popup.style.display = 'none';
        
        // Only dismiss THIS specific version — a new version from the server
        // will have a different string and bypass this guard automatically
        const targetVersion = version || latestDetectedVersion;
        if (targetVersion) {
            sessionStorage.setItem('pwa_update_dismissed_ver', targetVersion);
        }
    }

    let lastVersionCheckTime = 0;
    const VERSION_CHECK_COOLDOWN_MS = 10000; // 10s cooldown for immediate mobile responsiveness

    async function checkServerVersion(force = false) {
        const now = Date.now();
        if (!force && (now - lastVersionCheckTime < VERSION_CHECK_COOLDOWN_MS)) {
            return;
        }
        lastVersionCheckTime = now;

        if (swRegistration) {
            try { swRegistration.update(); } catch(e) {}
        }

        try {
            const res = await fetch('/pwa/version?_t=' + now, { cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            if (data && data.version) {
                const serverVersion = data.version;
                const currentVer = localStorage.getItem('pwa_app_version');

                // If server version differs from what we previously notified about,
                // clear the dismiss guard so the new update can show
                if (serverVersion !== sessionStorage.getItem('pwa_update_dismissed_ver')) {
                    // New version from server — allow popup to show
                }

                latestDetectedVersion = serverVersion;

                if (!currentVer) {
                    // First time load: establish current version baseline
                    localStorage.setItem('pwa_app_version', serverVersion);
                    if (swRegistration && swRegistration.waiting) {
                        showAppUpdatePopup(serverVersion, true);
                    }
                } else if (currentVer !== serverVersion) {
                    // Server has a newer version than what we last applied
                    // Clear any previous dismiss for an old version
                    const prevDismissed = sessionStorage.getItem('pwa_update_dismissed_ver');
                    if (prevDismissed && prevDismissed !== serverVersion) {
                        sessionStorage.removeItem('pwa_update_dismissed_ver');
                    }

                    if (swRegistration) {
                        try { swRegistration.update(); } catch(e) {}
                    }
                    showAppUpdatePopup(serverVersion, true);
                } else if (force && swRegistration && swRegistration.waiting) {
                    showAppUpdatePopup(serverVersion, true);
                }
            }
        } catch (e) {}
    }

    // Run check immediately on script evaluation
    checkServerVersion();

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const reg = await navigator.serviceWorker.register('/sw.js?v=137?v={{ $swQueryVer }}', { 
                    scope: '/',
                    updateViaCache: 'none'
                });
                swRegistration = reg;

                // 1. Force update check with server and SW registration on load
                try { reg.update(); } catch(e) {}
                checkServerVersion(true);

                // 2. Periodic check every 30s for real-time mobile background detection
                setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        if (swRegistration) try { swRegistration.update(); } catch(e) {}
                        checkServerVersion(true);
                    }
                }, 30000);

                // 3. If an update is already downloaded and waiting in background, trigger popup immediately
                if (reg.waiting) {
                    showAppUpdatePopup(latestDetectedVersion || 'new', true);
                }

                // 4. When a new update is found and finishes installing in the background
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed') {
                                if (navigator.serviceWorker.controller) {
                                    showAppUpdatePopup(latestDetectedVersion || 'new', true);
                                } else {
                                    checkServerVersion(true);
                                }
                            }
                        });
                    }
                });

                // 5. Listen for broadcast message from push or system broadcast
                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data && (event.data.type === 'UPDATE_AVAILABLE' || event.data.type === 'SW_UPDATED')) {
                        showAppUpdatePopup(event.data.version || latestDetectedVersion || 'new', true);
                    }
                });

                // 6. On app focus, unlock, or tab switch, immediately check for updates on mobile
                window.addEventListener('focus', () => {
                    if (swRegistration) try { swRegistration.update(); } catch(e) {}
                    checkServerVersion(true);
                });
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        if (swRegistration) try { swRegistration.update(); } catch(e) {}
                        checkServerVersion(true);
                    }
                });

            } catch (err) {
                console.warn('PWA service worker registration failed:', err);
            }
        });
    }

    // ── 2. DOM Health: Ensure PWA Modals & Overlays live in document.body ──
    function ensurePwaModalsInBody() {
        ['pwaIosModal', 'pwaSystemUpdatePopup', 'pwaNetworkToast'].forEach(id => {
            const el = document.getElementById(id);
            if (el && document.body && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensurePwaModalsInBody);
    } else {
        ensurePwaModalsInBody();
    }

    // ── 3. Standalone State & Universal Install Display Logic ──
    function checkIsStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://') ||
               window.matchMedia('(display-mode: fullscreen)').matches ||
               window.matchMedia('(display-mode: minimal-ui)').matches;
    }

    async function checkIsAppInstalled() {
        if (checkIsStandalone()) return true;
        if (localStorage.getItem('pwa_app_installed') === 'true') return true;
        if ('getInstalledRelatedApps' in navigator) {
            try {
                const related = await navigator.getInstalledRelatedApps();
                if (related && related.length > 0) {
                    localStorage.setItem('pwa_app_installed', 'true');
                    return true;
                }
            } catch(e) {}
        }
        return false;
    }

    async function syncPwaInstallVisibility() {
        const standalone = checkIsStandalone();
        const triggers = document.querySelectorAll('.pwa-install-trigger');
        if (standalone) {
            triggers.forEach(el => { el.style.display = 'none'; });
            return;
        }

        const isInstalled = await checkIsAppInstalled();
        triggers.forEach(el => {
            el.style.display = el.getAttribute('data-display') || 'inline-flex';
            if (isInstalled) {
                el.setAttribute('data-installed', 'true');
                const textEl = el.querySelector('.pwa-install-text') || el.querySelector('.nav-link-text');
                if (textEl) textEl.textContent = 'Open App';
                const icon = el.querySelector('i');
                if (icon) icon.className = 'bi bi-box-arrow-up-right';
            } else {
                el.removeAttribute('data-installed');
                const textEl = el.querySelector('.pwa-install-text') || el.querySelector('.nav-link-text');
                if (textEl) {
                    textEl.textContent = el.id === 'pwaLoginInstallBtn' || el.classList.contains('pwa-login-btn') 
                        ? 'Install Attendance App' 
                        : 'Install App';
                }
            }
        });
    }

    // Initialize display when DOM is ready and window loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncPwaInstallVisibility);
    } else {
        syncPwaInstallVisibility();
    }
    window.addEventListener('load', syncPwaInstallVisibility);

    // ── 4. Capture Native beforeinstallprompt Event ──
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        console.log('[PWA] Native install prompt captured and ready.');
        syncPwaInstallVisibility();
    });

    // ── 5. Guide Modal Builder for iOS, Android, Insecure Context & Desktops ──
    function showPwaGuideModal(forceMode = null) {
        ensurePwaModalsInBody();
        const modal = document.getElementById('pwaIosModal');
        const titleEl = document.getElementById('pwaModalTitle');
        const subEl = document.getElementById('pwaModalSub');
        const stepsEl = document.getElementById('pwaModalSteps');
        const actionBtn = document.getElementById('pwaIosCloseBtn');

        if (!modal || !stepsEl) return;

        // Clean up previous dynamically inserted pointer or rows
        const existingPointer = modal.querySelector('.pwa-ios-arrow-pointer');
        if (existingPointer) existingPointer.remove();

        const ua = (window.navigator.userAgent || '').toLowerCase();
        const isIos = /iphone|ipad|ipod/.test(ua);
        const isIosSafari = isIos && !ua.includes('crios') && !ua.includes('fxios') && !ua.includes('edgios');
        const isInApp = /fban|fbav|fb_iab|fbios|instagram|line\/|twitter|snapchat|micromessenger|tiktok|kakaotalk|threads/i.test(ua);
        const isSamsung = ua.includes('samsungbrowser');
        const isAndroid = /android/.test(ua);
        const isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

        // Default close behavior
        if (actionBtn) {
            actionBtn.textContent = 'Got It';
            actionBtn.onclick = (e) => {
                e.preventDefault();
                closePwaGuideModal();
            };
        }

        if (forceMode === 'already_installed') {
            if (titleEl) titleEl.textContent = 'App Already Installed';
            if (subEl) subEl.textContent = 'Smart Attendance is already installed and ready on this device:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num" style="background:#22C55E;color:#0E0609;">✓</div>
                    <div>Launch directly from your <strong>Home Screen</strong>, <strong>App Drawer</strong>, or <strong>Windows Start Menu</strong></div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num" style="background:#CFA46F;color:#0E0609;">⚡</div>
                    <div>Enjoy fast offline scanning, biometric clock-in, and instant school alerts</div>
                </div>
            `;
            if (actionBtn) {
                actionBtn.textContent = 'Open Application';
                actionBtn.onclick = (e) => {
                    e.preventDefault();
                    closePwaGuideModal();
                    window.location.href = '/home';
                };
            }
        } else if (forceMode === 'insecure_context' || !isSecure) {
            if (titleEl) titleEl.textContent = 'Secure Connection (HTTPS) Required';
            if (subEl) subEl.textContent = 'Browsers strictly require a secure HTTPS connection or localhost to install web applications:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step" style="border-left: 3px solid #EF4444; padding-left: 10px; background: rgba(239, 68, 68, 0.08); border-radius: 8px;">
                    <div class="pwa-ios-step-num" style="background:#EF4444;color:#FFFFFF;">!</div>
                    <div>Connected via <strong>${window.location.protocol}//${window.location.host}</strong> (unencrypted HTTP)</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div><strong>On PC / Laptop:</strong> Access using <code>http://localhost:8002</code> or <code>http://127.0.0.1:8002</code> instead of a local network IP</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div><strong>On Mobile (Wi-Fi):</strong> Connect via an HTTPS tunnel (such as Cloudflare or ngrok), or tap browser menu (⋮) &gt; <strong>Add to Home screen</strong> for a quick bookmark shortcut</div>
                </div>
            `;
            if (actionBtn) {
                actionBtn.textContent = 'Close';
            }
        } else if (isInApp) {
            if (titleEl) titleEl.textContent = 'Open in Browser to Install';
            if (subEl) subEl.textContent = 'In-app browsers do not support direct app installation. Please open this page in Chrome or Safari:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div>Tap the <strong>More options menu (⋯ or ⋮)</strong> at the top corner of your screen</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div>Select <strong>Open in Chrome</strong> or <strong>Open in Safari</strong></div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">3</div>
                    <div>Tap <strong>Install Smart Attendance</strong> once opened in the browser</div>
                </div>
            `;
        } else if (isIosSafari) {
            if (titleEl) titleEl.textContent = 'Install Smart Attendance';
            if (subEl) subEl.textContent = 'Add Smart Attendance to your iOS Home Screen in 3 easy steps:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div>Tap the <strong>Share</strong> button <svg style="display:inline;vertical-align:middle;margin:0 2px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#CFA46F" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg> in Safari's toolbar</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div>Scroll down and tap <strong>Add to Home Screen</strong> <svg style="display:inline;vertical-align:middle;margin:0 2px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#CFA46F" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">3</div>
                    <div>Tap <strong>Add</strong> in the top right corner to complete</div>
                </div>
            `;
            const pointer = document.createElement('div');
            pointer.className = 'pwa-ios-arrow-pointer';
            pointer.innerHTML = `
                <span style="font-size:0.75rem; color:#CFA46F; font-weight:700; letter-spacing:0.3px;">Tap Safari's Share button below</span>
                <svg class="pwa-bounce-anim" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#CFA46F" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
            `;
            stepsEl.after(pointer);
        } else if (isIos) {
            if (titleEl) titleEl.textContent = 'Install Smart Attendance';
            if (subEl) subEl.textContent = 'To install on iOS from this browser:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div>Tap the <strong>Share</strong> or <strong>Menu (⋯)</strong> button</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div>Select <strong>Add to Home Screen</strong> (or open in Safari for full PWA capabilities)</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">3</div>
                    <div>Tap <strong>Add</strong> to put Smart Attendance on your home screen</div>
                </div>
            `;
        } else if (isSamsung) {
            if (titleEl) titleEl.textContent = 'Install Smart Attendance';
            if (subEl) subEl.textContent = 'Add Smart Attendance to your Home Screen:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div>Tap the <strong>Menu button (☰)</strong> at the bottom right corner</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div>Tap <strong>+ Add page to</strong> and select <strong>Home screen</strong></div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">3</div>
                    <div>Tap <strong>Add</strong> or <strong>Install</strong> to finish</div>
                </div>
            `;
        } else if (isAndroid) {
            if (titleEl) titleEl.textContent = 'Install Smart Attendance';
            if (subEl) subEl.textContent = 'Install Smart Attendance from your browser menu:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div>Tap the <strong>Three Dots menu (⋮)</strong> at the top-right corner of your browser</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div>Tap <strong>Install app</strong> or <strong>Add to Home screen</strong></div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">3</div>
                    <div>Confirm by tapping <strong>Install</strong></div>
                </div>
            `;
        } else {
            if (titleEl) titleEl.textContent = 'Install Smart Attendance';
            if (subEl) subEl.textContent = 'Install directly on your computer:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div>Look at the right side of the <strong>URL address bar</strong> at the top of your browser</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div>Click the <strong>Install App icon</strong> <span style="background:rgba(207,164,111,0.2);padding:2px 7px;border-radius:6px;color:#CFA46F;font-size:0.8rem;font-weight:700;display:inline-block;margin-left:4px;">⊕ or ⬇</span></div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">3</div>
                    <div>Or click <strong>Menu (⋮) &gt; Save and share &gt; Install Smart Attendance</strong></div>
                </div>
            `;
        }

        modal.style.display = 'flex';
    }

    function closePwaGuideModal() {
        const modal = document.getElementById('pwaIosModal');
        if (modal) modal.style.display = 'none';
        localStorage.setItem('pwa_ios_prompt_dismissed', 'true');
    }

    // ── 6. Universal Trigger Install Handler ──
    async function triggerPwaInstall(triggerEl) {
        if (window.__pwaActionInProgress) return;
        window.__pwaActionInProgress = true;
        setTimeout(() => { window.__pwaActionInProgress = false; }, 1500);

        // 1. Check if open in standalone
        if (checkIsStandalone()) {
            showPwaGuideModal('already_installed');
            return;
        }

        // 2. Check if installed
        const isInstalled = await checkIsAppInstalled();
        if (isInstalled) {
            showPwaGuideModal('already_installed');
            return;
        }

        // 3. Security / HTTPS check
        const isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        if (!isSecure) {
            showPwaGuideModal('insecure_context');
            return;
        }

        // 4. Native prompt immediately available
        if (deferredPrompt) {
            try {
                if (triggerEl) triggerEl.classList.add('pwa-btn-loading');
                deferredPrompt.prompt();
                const choice = await deferredPrompt.userChoice;
                if (triggerEl) triggerEl.classList.remove('pwa-btn-loading');
                deferredPrompt = null;
                
                if (choice && choice.outcome === 'accepted') {
                    localStorage.setItem('pwa_app_installed', 'true');
                    showNetworkToast('✓ Smart Attendance installed successfully!', 'online');
                    syncPwaInstallVisibility();
                    return;
                } else {
                    showNetworkToast('Installation cancelled.', 'offline');
                    return;
                }
            } catch(err) {
                console.warn('[PWA] Prompt error:', err);
                if (triggerEl) triggerEl.classList.remove('pwa-btn-loading');
            }
        }

        // 5. If deferredPrompt not yet available, wait briefly in case beforeinstallprompt is in flight
        const ua = (window.navigator.userAgent || '').toLowerCase();
        const isIos = /iphone|ipad|ipod/.test(ua);
        if (!deferredPrompt && !isIos && isSecure && !window.__pwaWaitedBeforePrompt) {
            window.__pwaWaitedBeforePrompt = true;
            if (triggerEl) {
                const originalHtml = triggerEl.innerHTML;
                triggerEl.innerHTML = '<span class="pwa-spinner"></span> Loading installer...';
                triggerEl.classList.add('pwa-btn-loading');

                const promptFired = await Promise.race([
                    new Promise(resolve => {
                        const handler = () => {
                            window.removeEventListener('beforeinstallprompt', handler);
                            resolve(true);
                        };
                        window.addEventListener('beforeinstallprompt', handler, { once: true });
                    }),
                    new Promise(resolve => setTimeout(() => resolve(false), 900))
                ]);

                triggerEl.innerHTML = originalHtml;
                triggerEl.classList.remove('pwa-btn-loading');

                if (promptFired && deferredPrompt) {
                    try {
                        deferredPrompt.prompt();
                        const choice = await deferredPrompt.userChoice;
                        deferredPrompt = null;
                        if (choice && choice.outcome === 'accepted') {
                            localStorage.setItem('pwa_app_installed', 'true');
                            showNetworkToast('✓ Smart Attendance installed successfully!', 'online');
                            syncPwaInstallVisibility();
                            return;
                        } else {
                            showNetworkToast('Installation cancelled.', 'offline');
                            return;
                        }
                    } catch(e) {}
                }
            }
        }

        // 6. Show browser-tailored guide modal
        showPwaGuideModal();
    }

    // ── 7. Global Event Delegation (Guarantees ALL install buttons work anytime) ──
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closePwaGuideModal();
        }
    });

    document.addEventListener('click', (e) => {
        const target = e.target;
        if (!target) return;

        // Install button clicked
        const installTrigger = target.closest('.pwa-install-trigger') || target.closest('#pwaInstallBtn');
        if (installTrigger) {
            e.preventDefault();
            e.stopPropagation();
            triggerPwaInstall(installTrigger);
            return;
        }

        // Close guide modal
        const closeTrigger = target.closest('#pwaIosCloseBtn') || target.closest('#pwaModalCloseIcon');
        if (closeTrigger) {
            e.preventDefault();
            closePwaGuideModal();
            return;
        }

        // Modal backdrop click
        const iosModal = document.getElementById('pwaIosModal');
        if (iosModal && target === iosModal) {
            closePwaGuideModal();
            return;
        }

        // Apply Update button clicked
        const applyUpdateBtn = target.closest('#pwaApplyUpdateBtn');
        if (applyUpdateBtn) {
            e.preventDefault();
            const btnText = document.getElementById('pwaApplyUpdateBtnText');
            if (btnText) btnText.textContent = 'Updating...';
            const spinIcon = applyUpdateBtn.querySelector('.pwa-update-spin-icon');
            if (spinIcon) spinIcon.style.display = 'inline-block';
            applyUpdateBtn.style.opacity = '0.85';
            applyUpdateBtn.style.pointerEvents = 'none';

            if (latestDetectedVersion) {
                localStorage.setItem('pwa_app_version', latestDetectedVersion);
                localStorage.removeItem('pwa_update_dismissed_ver');
                localStorage.removeItem('pwa_update_prompted_ver');
                sessionStorage.removeItem('pwa_update_dismissed_ver');
            }

            try {
                fetch('/pwa/version', { cache: 'no-store' })
                    .then(r => r.json())
                    .then(d => { 
                        if (d?.version) {
                            localStorage.setItem('pwa_app_version', d.version);
                            localStorage.removeItem('pwa_update_dismissed_ver');
                            localStorage.removeItem('pwa_update_prompted_ver');
                            sessionStorage.removeItem('pwa_update_dismissed_ver');
                        }
                    })
                    .catch(() => {});
            } catch(e) {}

            if (swRegistration && swRegistration.waiting) {
                swRegistration.waiting.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' });
            }
            if (navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ action: 'clearCache', type: 'CLEAR_CACHE' });
            }

            if ('caches' in window) {
                caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k)))).then(() => {
                    setTimeout(() => window.location.reload(true), 300);
                }).catch(() => {
                    window.location.reload(true);
                });
            } else {
                window.location.reload(true);
            }
            return;
        }

        // Dismiss Update popup (Dismiss for current session)
        const dismissUpdateBtn = target.closest('#pwaDismissUpdatePopupBtn') || target.closest('#pwaLaterUpdateBtn');
        if (dismissUpdateBtn) {
            e.preventDefault();
            hideAppUpdatePopup(latestDetectedVersion);
            return;
        }
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        const installBanner = document.getElementById('pwaInstallBanner');
        const iosModal = document.getElementById('pwaIosModal');
        if (installBanner) installBanner.style.display = 'none';
        if (iosModal) iosModal.style.display = 'none';
        localStorage.setItem('pwa_prompt_dismissed', 'true');
        document.querySelectorAll('.pwa-install-trigger').forEach(el => {
            el.style.display = 'none';
        });
    });

    // ── 7. Real-time Network Connectivity Notifications ──
    const networkToast = document.getElementById('pwaNetworkToast');
    function showNetworkToast(message, type) {
        const toast = document.getElementById('pwaNetworkToast');
        if (!toast) return;
        toast.textContent = message;
        toast.className = `pwa-network-toast show ${type}`;
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3500);
    }

    window.addEventListener('offline', () => {
        showNetworkToast('⚡ You are currently offline. Offline mode active.', 'offline');
    });

    window.addEventListener('online', () => {
        showNetworkToast('✓ Connection restored! Back online.', 'online');
    });

    // ── 8. Cache User Session for Offline Mode ──
    @auth
        try {
            const userProfile = {
                name: "{{ addslashes(auth()->user()->name) }}",
                role: "{{ auth()->user()->role }}",
                email: "{{ auth()->user()->email }}",
                student_number: "{{ auth()->user()->student_number ?? '' }}",
                course: "{{ auth()->user()->course ?? '' }}",
                year_level: "{{ auth()->user()->year_level ?? '' }}",
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('cached_user_profile', JSON.stringify(userProfile));
            localStorage.setItem('cached_student_profile', JSON.stringify(userProfile));
        } catch(e) {}
    @endauth
</script>
