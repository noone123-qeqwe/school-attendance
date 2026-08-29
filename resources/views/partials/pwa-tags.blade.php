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
        bottom: 24px;
        left: 24px;
        right: 24px;
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

    /* iOS Safari Install Sheet */
    .pwa-ios-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 100000;
        display: none;
        align-items: flex-end;
        justify-content: center;
        padding: 20px;
        animation: pwaFadeIn 0.3s ease;
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

    /* System Update Banner Card */
    .pwa-update-banner {
        position: fixed;
        bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        left: 16px;
        right: 16px;
        max-width: 400px;
        margin: 0 auto;
        background: rgba(18, 12, 10, 0.98);
        border: 1px solid rgba(74, 222, 128, 0.4);
        border-radius: 20px;
        padding: 16px 18px;
        box-shadow: 0 16px 45px rgba(0, 0, 0, 0.85), 0 0 25px rgba(74, 222, 128, 0.18);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        z-index: 100000;
        display: none;
        flex-direction: column;
        gap: 12px;
        animation: pwaSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .pwa-update-banner-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        position: relative;
    }

    .pwa-update-icon-wrapper {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: rgba(74, 222, 128, 0.12);
        border: 1px solid rgba(74, 222, 128, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .pwa-update-text-area {
        flex: 1;
        min-width: 0;
        padding-right: 22px; /* Space for close X */
    }

    .pwa-update-title {
        font-size: 0.98rem;
        font-weight: 700;
        color: #F3E7CD;
        line-height: 1.25;
        margin-bottom: 4px;
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
    }

    .pwa-update-subtitle {
        font-size: 0.8rem;
        color: #B39B82;
        line-height: 1.45;
        white-space: normal;
    }

    .pwa-update-close-btn {
        position: absolute;
        top: -4px;
        right: -4px;
        background: transparent;
        border: none;
        color: #8F7D6D;
        font-size: 1.35rem;
        cursor: pointer;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        padding: 0;
        transition: color 0.2s ease;
    }

    .pwa-update-close-btn:hover {
        color: #F3E7CD;
    }

    .pwa-update-banner-actions {
        display: flex;
        width: 100%;
    }

    .pwa-btn-update-apply {
        width: 100%;
        background: linear-gradient(135deg, #4ADE80 0%, #22C55E 100%);
        color: #062412;
        font-weight: 700;
        font-size: 0.88rem;
        border: none;
        border-radius: 12px;
        padding: 10px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 4px 14px rgba(74, 222, 128, 0.35);
        transition: all 0.2s ease;
        touch-action: manipulation;
    }

    .pwa-btn-update-apply:hover {
        filter: brightness(1.08);
        transform: translateY(-1px);
    }

    .pwa-btn-update-apply:active {
        transform: scale(0.97);
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

<!-- System Update Available Popup Notification -->
<div class="pwa-update-banner" id="pwaSystemUpdatePopup" style="display: none;">
    <div class="pwa-update-banner-header">
        <div class="pwa-update-icon-wrapper">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
            </svg>
        </div>
        <div class="pwa-update-text-area">
            <div class="pwa-update-title">System Update Ready</div>
            <div class="pwa-update-subtitle">A new version is available. Tap Update to refresh and apply the latest changes.</div>
        </div>
        <button type="button" class="pwa-update-close-btn" id="pwaDismissUpdatePopupBtn" aria-label="Dismiss">&times;</button>
    </div>
    <div class="pwa-update-banner-actions">
        <button type="button" class="pwa-btn-update-apply" id="pwaApplyUpdateBtn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pwa-update-spin-icon" style="display:none; animation: ptr-spin 0.8s linear infinite;">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
            </svg>
            <span id="pwaApplyUpdateBtnText">Update Now</span>
        </button>
    </div>
</div>

<!-- Real-time Connectivity Toast -->
<div class="pwa-network-toast" id="pwaNetworkToast"></div>

@php
    $swCacheVer = \Illuminate\Support\Facades\Cache::get('pwa_sw_version', '37');
    $swFileMtime = file_exists(public_path('sw.js')) ? filemtime(public_path('sw.js')) : time();
    $swQueryVer = 'v' . preg_replace('/[^0-9]/', '', (string)$swCacheVer) . '_' . $swFileMtime;
@endphp

<script @cspNonce>
    // ── 1. Register Service Worker & Handle Real-Time Update Notifications ──
    let swRegistration = null;
    let deferredPrompt = null;
    let latestDetectedVersion = null;

    function showAppUpdatePopup(version) {
        if (!version) return;
        
        const currentVer = localStorage.getItem('pwa_app_version');
        const sessionDismissed = sessionStorage.getItem('pwa_update_dismissed_ver');

        if (currentVer === version || sessionDismissed === version) {
            return;
        }

        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) {
            popup.style.display = 'flex';
        }
    }

    function hideAppUpdatePopup(version) {
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) popup.style.display = 'none';
        
        const targetVersion = version || latestDetectedVersion;
        if (targetVersion) {
            sessionStorage.setItem('pwa_update_dismissed_ver', targetVersion);
        }
    }

    let lastVersionCheckTime = 0;
    const VERSION_CHECK_COOLDOWN_MS = 60000;

    async function checkServerVersion(force = false) {
        const now = Date.now();
        if (!force && (now - lastVersionCheckTime < VERSION_CHECK_COOLDOWN_MS)) {
            return;
        }
        lastVersionCheckTime = now;

        try {
            const res = await fetch('/pwa/version', { cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            if (data && data.version) {
                latestDetectedVersion = data.version;
                const currentVer = localStorage.getItem('pwa_app_version');
                const sessionDismissed = sessionStorage.getItem('pwa_update_dismissed_ver');

                if (!currentVer) {
                    // First time load: establish current version baseline
                    localStorage.setItem('pwa_app_version', data.version);
                } else if (currentVer !== data.version && sessionDismissed !== data.version) {
                    if (swRegistration) {
                        try { swRegistration.update(); } catch(e) {}
                    }
                    showAppUpdatePopup(data.version);
                }
            }
        } catch (e) {}
    }

    // Run check immediately on script evaluation
    checkServerVersion();

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const reg = await navigator.serviceWorker.register('/sw.js?v=86?v={{ $swQueryVer }}', { 
                    scope: '/',
                    updateViaCache: 'none'
                });
                swRegistration = reg;

                // 1. Immediate version check on page ready
                checkServerVersion();

                // 2. Periodic check every 3 minutes for real-time detection while using the app
                setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        checkServerVersion();
                    }
                }, 180000);

                // 3. If an update is already downloaded and waiting, verify version
                if (reg.waiting) {
                    checkServerVersion(true);
                }

                // 4. When a new update is found and finishes installing in the background
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed') {
                                checkServerVersion(true);
                            }
                        });
                    }
                });

                // 5. Listen for broadcast message from push or system broadcast
                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data && (event.data.type === 'UPDATE_AVAILABLE' || event.data.type === 'SW_UPDATED')) {
                        checkServerVersion(true);
                    }
                });

                // 6. On app focus or unlock, check for updates (throttled)
                window.addEventListener('focus', () => {
                    checkServerVersion();
                });
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        checkServerVersion();
                    }
                });

            } catch (err) {
                console.warn('PWA service worker registration failed:', err);
            }
        });
    }

    // ── 2. Standalone State & Universal Install Display Logic ──
    function checkIsStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://') ||
               window.matchMedia('(display-mode: fullscreen)').matches ||
               window.matchMedia('(display-mode: minimal-ui)').matches;
    }

    function syncPwaInstallVisibility() {
        const standalone = checkIsStandalone();
        const triggers = document.querySelectorAll('.pwa-install-trigger');
        triggers.forEach(el => {
            if (standalone) {
                el.style.display = 'none';
            } else {
                el.style.display = el.getAttribute('data-display') || 'inline-flex';
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

    // ── 3. Capture Native beforeinstallprompt Event ──
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        syncPwaInstallVisibility();
    });

    // ── 4. Guide Modal Builder for iOS, Android & In-App Browsers ──
    function showPwaGuideModal() {
        const modal = document.getElementById('pwaIosModal');
        const titleEl = document.getElementById('pwaModalTitle');
        const subEl = document.getElementById('pwaModalSub');
        const stepsEl = document.getElementById('pwaModalSteps');

        if (!modal || !stepsEl) return;

        const ua = (window.navigator.userAgent || '').toLowerCase();
        const isIos = /iphone|ipad|ipod/.test(ua);
        const isIosSafari = isIos && !ua.includes('crios') && !ua.includes('fxios') && !ua.includes('edgios');
        const isInApp = /fban|fbav|fb_iab|fbios|instagram|line\/|twitter|snapchat|micromessenger|tiktok|kakaotalk|threads/i.test(ua);
        const isSamsung = ua.includes('samsungbrowser');
        const isAndroid = /android/.test(ua);

        if (isInApp) {
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
            if (subEl) subEl.textContent = 'Add Smart Attendance to your Home Screen in 3 quick steps:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div>Tap the <strong>Share</strong> button <svg style="display:inline;vertical-align:middle;margin:0 2px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#CFA46F" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg> in the bottom bar</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div>Scroll down and tap <strong>Add to Home Screen</strong></div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">3</div>
                    <div>Tap <strong>Add</strong> in the top right corner to complete</div>
                </div>
            `;
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
                    <div>Select <strong>Add to Home Screen</strong> (or open in Safari for full PWA features)</div>
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
            if (subEl) subEl.textContent = 'Install on your computer or mobile device:';
            stepsEl.innerHTML = `
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">1</div>
                    <div>Look for the <strong>Install App icon (⊕ or ⬇)</strong> in your browser address bar</div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">2</div>
                    <div>Or open the browser menu (⋮) and select <strong>Install Attendance App</strong></div>
                </div>
                <div class="pwa-ios-step">
                    <div class="pwa-ios-step-num">3</div>
                    <div>Click <strong>Install</strong> to add the app</div>
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

    // ── 5. Universal Trigger Install Handler ──
    async function triggerPwaInstall() {
        if (deferredPrompt) {
            try {
                deferredPrompt.prompt();
                const choice = await deferredPrompt.userChoice;
                deferredPrompt = null;
                if (choice && choice.outcome === 'accepted') {
                    const installBanner = document.getElementById('pwaInstallBanner');
                    if (installBanner) installBanner.style.display = 'none';
                    document.querySelectorAll('.pwa-install-trigger').forEach(el => {
                        el.style.display = 'none';
                    });
                    return;
                }
            } catch(err) {
                console.warn('Native install prompt failed, falling back to guide modal:', err);
            }
        }

        showPwaGuideModal();
    }

    // ── 6. Global Event Delegation (Guarantees ALL install buttons work anytime) ──
    document.addEventListener('click', (e) => {
        const target = e.target;
        if (!target) return;

        // Install button clicked
        const installTrigger = target.closest('.pwa-install-trigger') || target.closest('#pwaInstallBtn');
        if (installTrigger) {
            e.preventDefault();
            e.stopPropagation();
            triggerPwaInstall();
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
        const dismissUpdateBtn = target.closest('#pwaDismissUpdatePopupBtn');
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
            localStorage.setItem('cached_student_profile', JSON.stringify(userProfile));
        } catch(e) {}
    @endauth
</script>
