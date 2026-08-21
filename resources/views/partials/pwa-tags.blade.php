<!-- PWA Head Meta Tags -->
<meta name="theme-color" content="#110A0A">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Attendance') }}">
<meta name="application-name" content="{{ config('app.name', 'Attendance') }}">
<meta name="msapplication-TileColor" content="#110A0A">
<meta name="msapplication-TileImage" content="/images/icons/icon-144x144.png">

<!-- PWA Manifest & Icons -->
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-180x180.png">
<link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
<link rel="apple-touch-icon" sizes="128x128" href="/images/icons/icon-128x128.png">
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

<!-- iOS Safari Manual Install Sheet -->
<div class="pwa-ios-modal" id="pwaIosModal">
    <div class="pwa-ios-sheet">
        <h3>Install on iPhone / iPad</h3>
        <p>Install this app on your home screen for instantaneous access and offline support.</p>
        <div class="pwa-ios-steps">
            <div class="pwa-ios-step">
                <div class="pwa-ios-step-num">1</div>
                <div>Tap the <strong>Share</strong> button in Safari toolbar</div>
            </div>
            <div class="pwa-ios-step">
                <div class="pwa-ios-step-num">2</div>
                <div>Scroll down and select <strong>Add to Home Screen</strong></div>
            </div>
            <div class="pwa-ios-step">
                <div class="pwa-ios-step-num">3</div>
                <div>Tap <strong>Add</strong> in the top right corner</div>
            </div>
        </div>
        <button class="pwa-btn-install" id="pwaIosCloseBtn" style="width: 100%;">Got it</button>
    </div>
</div>

<!-- Real-time Connectivity Toast -->
<div class="pwa-network-toast" id="pwaNetworkToast"></div>

<script>
    // ── 1. Register Service Worker & Handle Updates ──
    let swRegistration = null;
    const updateBanner = document.getElementById('pwaUpdateBanner');
    const updateBtn = document.getElementById('pwaUpdateBtn');
    const updateDismissBtn = document.getElementById('pwaUpdateDismissBtn');

    function showUpdateBannerOnce() {
        if (!updateBanner) return;
        // Only show once: if user dismissed or already saw it in this session, do not show again
        if (localStorage.getItem('pwa_update_dismissed') === 'true' || sessionStorage.getItem('pwa_update_shown') === 'true') {
            return;
        }
        sessionStorage.setItem('pwa_update_shown', 'true');
        updateBanner.style.display = 'flex';
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js?v=8')
                .then((registration) => {
                    swRegistration = registration;

                    // If a worker is already waiting and banner hasn't been shown/dismissed
                    if (registration.waiting && navigator.serviceWorker.controller) {
                        showUpdateBannerOnce();
                    }

                    // Periodic update check every 20 minutes
                    setInterval(() => {
                        registration.update();
                    }, 20 * 60 * 1000);

                    // Re-check on tab focus
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            registration.update();
                        }
                    });

                    // Listen for incoming waiting worker
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        if (newWorker) {
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    showUpdateBannerOnce();
                                }
                            });
                        }
                    });
                })
                .catch((err) => {
                    console.warn('[PWA] ServiceWorker registration failed: ', err);
                });

            // Smooth reload when new worker takes over
            let refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!refreshing) {
                    refreshing = true;
                    window.location.reload();
                }
            });
        });
    }

    if (updateBtn) {
        updateBtn.addEventListener('click', () => {
            localStorage.removeItem('pwa_update_dismissed');
            sessionStorage.removeItem('pwa_update_shown');
            if (swRegistration && swRegistration.waiting) {
                swRegistration.waiting.postMessage({ action: 'skipWaiting' });
            } else {
                window.location.reload();
            }
        });
    }

    if (updateDismissBtn) {
        updateDismissBtn.addEventListener('click', () => {
            if (updateBanner) updateBanner.style.display = 'none';
            localStorage.setItem('pwa_update_dismissed', 'true');
            sessionStorage.setItem('pwa_update_shown', 'true');
        });
    }

    // ── 2. In-App Install Prompt Handler (Android / Chrome / Edge / Desktop) ──
    let deferredPrompt = null;
    const installBanner = document.getElementById('pwaInstallBanner');
    const installBtn = document.getElementById('pwaInstallBtn');
    const dismissBtn = document.getElementById('pwaDismissBtn');
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        document.querySelectorAll('.pwa-install-trigger').forEach(el => {
            el.style.display = 'inline-flex';
        });

        // Only show once and respect dismissal
        if (!isStandalone && !localStorage.getItem('pwa_prompt_dismissed') && !sessionStorage.getItem('pwa_prompt_shown')) {
            sessionStorage.setItem('pwa_prompt_shown', 'true');
            setTimeout(() => {
                if (installBanner) installBanner.style.display = 'flex';
            }, 3000);
        }
    });

    async function triggerPwaInstall() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (installBanner) installBanner.style.display = 'none';
            localStorage.setItem('pwa_prompt_dismissed', 'true');
            document.querySelectorAll('.pwa-install-trigger').forEach(el => {
                el.style.display = 'none';
            });
        } else if (isIosSafari && !isStandalone) {
            const iosModal = document.getElementById('pwaIosModal');
            if (iosModal) iosModal.style.display = 'flex';
        }
    }

    if (installBtn) {
        installBtn.addEventListener('click', triggerPwaInstall);
    }

    document.querySelectorAll('.pwa-install-trigger').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            triggerPwaInstall();
        });
    });

    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            if (installBanner) installBanner.style.display = 'none';
            localStorage.setItem('pwa_prompt_dismissed', 'true');
            sessionStorage.setItem('pwa_prompt_shown', 'true');
        });
    }

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        if (installBanner) installBanner.style.display = 'none';
        localStorage.setItem('pwa_prompt_dismissed', 'true');
        document.querySelectorAll('.pwa-install-trigger').forEach(el => {
            el.style.display = 'none';
        });
    });

    // ── 3. iOS Safari Instructions Handler ──
    const userAgent = window.navigator.userAgent.toLowerCase();
    const isIosSafari = /iphone|ipad|ipod/.test(userAgent) && !userAgent.includes('crios') && !userAgent.includes('fxios');
    const iosModal = document.getElementById('pwaIosModal');
    const iosCloseBtn = document.getElementById('pwaIosCloseBtn');

    if (isIosSafari && !isStandalone && !localStorage.getItem('pwa_ios_prompt_dismissed')) {
        setTimeout(() => {
            if (iosModal) iosModal.style.display = 'flex';
        }, 5000);
    }

    if (iosCloseBtn) {
        iosCloseBtn.addEventListener('click', () => {
            if (iosModal) iosModal.style.display = 'none';
            localStorage.setItem('pwa_ios_prompt_dismissed', 'true');
        });
    }

    // ── 4. Real-time Network Connectivity Notifications ──
    const networkToast = document.getElementById('pwaNetworkToast');
    function showNetworkToast(message, type) {
        if (!networkToast) return;
        networkToast.textContent = message;
        networkToast.className = `pwa-network-toast show ${type}`;
        setTimeout(() => {
            networkToast.classList.remove('show');
        }, 3500);
    }

    window.addEventListener('offline', () => {
        showNetworkToast('⚡ You are currently offline. Offline mode active.', 'offline');
    });

    window.addEventListener('online', () => {
        showNetworkToast('✓ Connection restored! Back online.', 'online');
    });

    // ── 5. Cache User Session for Offline Mode ──
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
