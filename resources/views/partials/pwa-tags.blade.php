<!-- PWA Head Meta Tags -->
<meta name="theme-color" content="#110A0A">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Attendance">
<meta name="application-name" content="Attendance">
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

    @keyframes pwaFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>

<!-- In-App Install Prompt Banner -->
<div class="pwa-install-banner" id="pwaInstallBanner">
    <div class="pwa-banner-icon">
        <img src="/images/icons/icon-192x192.png" alt="App Icon">
    </div>
    <div class="pwa-banner-content">
        <div class="pwa-banner-title">Install Attendance App</div>
        <div class="pwa-banner-subtitle">Fast access, offline attendance & alerts</div>
    </div>
    <div class="pwa-banner-actions">
        <button class="pwa-btn-install" id="pwaInstallBtn">Install</button>
        <button class="pwa-btn-close" id="pwaDismissBtn" aria-label="Dismiss">&times;</button>
    </div>
</div>

<!-- In-App Update Notification Banner -->
<div class="pwa-install-banner" id="pwaUpdateBanner" style="background: rgba(20, 15, 10, 0.98); border-color: rgba(74, 222, 128, 0.4);">
    <div class="pwa-banner-icon" style="background: rgba(74, 222, 128, 0.15); border-color: rgba(74, 222, 128, 0.3);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
        </svg>
    </div>
    <div class="pwa-banner-content">
        <div class="pwa-banner-title">App Update Available</div>
        <div class="pwa-banner-subtitle">A newer version is ready. Click to refresh.</div>
    </div>
    <div class="pwa-banner-actions">
        <button class="pwa-btn-install" id="pwaUpdateBtn" style="background: linear-gradient(135deg, #4ADE80 0%, #22C55E 100%); color: #0F172A;">Update</button>
        <button class="pwa-btn-close" id="pwaUpdateDismissBtn" aria-label="Dismiss">&times;</button>
    </div>
</div>

<!-- Universal PWA Install Guide Modal (Android, iOS & In-App Browsers) -->
<div class="pwa-ios-modal" id="pwaIosModal">
    <div class="pwa-ios-sheet">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <img src="/images/icons/icon-72x72.png" style="width:34px; height:34px; border-radius:8px; border:1px solid rgba(207,164,111,0.3);" alt="App Icon">
                <h3 id="pwaModalTitle" style="margin:0; font-size:1.1rem; color:#F3E7CD; text-align:left;">Install Attendance App</h3>
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

<!-- Real-time Connectivity Toast -->
<div class="pwa-network-toast" id="pwaNetworkToast"></div>

<script>
    // ── 1. Register Service Worker & Handle Real-Time Updates ──
    let swRegistration = null;
    let deferredPrompt = null;

    function showUpdateBannerOnce() {
        const updateBanner = document.getElementById('pwaUpdateBanner');
        if (!updateBanner) return;
        sessionStorage.removeItem('pwa_update_shown');
        localStorage.removeItem('pwa_update_dismissed');
        updateBanner.style.display = 'flex';
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const swUrl = '/sw.js?v=11?v={{ preg_replace("/[^0-9]/", "", \Illuminate\Support\Facades\Cache::get("pwa_sw_version", "8")) }}';
                const reg = await navigator.serviceWorker.register(swUrl, { scope: '/' });
                swRegistration = reg;

                // Immediately check if a waiting worker is already present
                if (reg.waiting) {
                    showUpdateBannerOnce();
                }

                // Check for updates when a new worker starts installing
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                showUpdateBannerOnce();
                            }
                        });
                    }
                });

                // Listen for real-time broadcast message sent from Service Worker activate event
                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data && (event.data.type === 'SW_UPDATED' || event.data.action === 'SW_UPDATED')) {
                        showUpdateBannerOnce();
                    }
                });

                // Poll for Service Worker updates periodically (every 30 seconds) on active tabs
                setInterval(() => {
                    try {
                        reg.update();
                    } catch (e) {}
                }, 30000);

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

        const installBanner = document.getElementById('pwaInstallBanner');
        if (!checkIsStandalone() && !localStorage.getItem('pwa_prompt_dismissed') && !sessionStorage.getItem('pwa_prompt_shown')) {
            sessionStorage.setItem('pwa_prompt_shown', 'true');
            setTimeout(() => {
                const banner = document.getElementById('pwaInstallBanner');
                if (banner && !checkIsStandalone()) banner.style.display = 'flex';
            }, 3000);
        }
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
                    <div>Tap <strong>Install Attendance App</strong> once opened in the browser</div>
                </div>
            `;
        } else if (isIosSafari) {
            if (titleEl) titleEl.textContent = 'Install on iPhone / iPad';
            if (subEl) subEl.textContent = 'Add Attendance to your Home Screen in 3 quick steps:';
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
            if (titleEl) titleEl.textContent = 'Install on iPhone / iPad';
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
                    <div>Tap <strong>Add</strong> to put the app on your home screen</div>
                </div>
            `;
        } else if (isSamsung) {
            if (titleEl) titleEl.textContent = 'Install on Samsung Internet';
            if (subEl) subEl.textContent = 'Add Attendance to your Home Screen:';
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
            if (titleEl) titleEl.textContent = 'Install on Android';
            if (subEl) subEl.textContent = 'Install the Attendance App from your browser menu:';
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
            if (titleEl) titleEl.textContent = 'Install Attendance App';
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

        // Dismiss install banner
        const dismissBtn = target.closest('#pwaDismissBtn');
        if (dismissBtn) {
            e.preventDefault();
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) banner.style.display = 'none';
            localStorage.setItem('pwa_prompt_dismissed', 'true');
            sessionStorage.setItem('pwa_prompt_shown', 'true');
            return;
        }

        // Update button clicked
        const updateBtn = target.closest('#pwaUpdateBtn');
        if (updateBtn) {
            e.preventDefault();
            updateBtn.textContent = 'Updating...';
            updateBtn.style.opacity = '0.7';

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

        // Dismiss update banner
        const updateDismissBtn = target.closest('#pwaUpdateDismissBtn');
        if (updateDismissBtn) {
            e.preventDefault();
            const updateBanner = document.getElementById('pwaUpdateBanner');
            if (updateBanner) updateBanner.style.display = 'none';
            localStorage.setItem('pwa_update_dismissed', 'true');
            sessionStorage.setItem('pwa_update_shown', 'true');
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
