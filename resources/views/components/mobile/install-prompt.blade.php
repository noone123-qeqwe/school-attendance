{{-- Install PWA Prompt - Only shows on Dashboard when native browser install is available --}}
<div id="installPrompt" class="install-prompt" style="display: none;" role="region" aria-label="Install application">
    <div class="install-prompt-row">
        <div class="install-prompt-badge" aria-hidden="true">
            <i class="bi bi-phone-fill"></i>
        </div>
        <div class="install-prompt-copy">
            <strong>Install Student App</strong>
            <span>1-tap access from your home screen &amp; fullscreen QR scanner.</span>
        </div>
        <div class="install-prompt-actions">
            <button type="button" class="install-prompt-btn" id="installPromptTrigger">Install</button>
            <button type="button" class="install-prompt-close" id="installPromptClose" aria-label="Dismiss install prompt">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div id="installGuideBox" class="install-guide-box" hidden>
        <p id="installGuidePlatform" class="install-guide-title"></p>
        <ol id="installGuideSteps" class="install-guide-steps"></ol>
    </div>
</div>

<script @cspNonce>
    if (typeof window.deferredPrompt === 'undefined') {
        window.deferredPrompt = null;
    }

    function isInstalledOrNativeApp() {
        return Boolean(
            (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.isNativeApp === 'function' && window.AndroidDeviceBridge.isNativeApp()) ||
            window.AttendancePasskey ||
            /SmartAttendanceApp/i.test(navigator.userAgent || '') ||
            localStorage.getItem('pwa_app_installed') === 'true' ||
            localStorage.getItem('pwa_apk_downloaded') === 'true'
        ) ||
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone ||
            document.referrer.includes('android-app://');
    }

    function shouldOfferInstallBanner() {
        if (isInstalledOrNativeApp()) return false;
        if (!/^\/home\/?$/i.test(window.location.pathname)) return false;
        const dismissed = localStorage.getItem('installPromptDismissed');
        const dismissTime = Number(localStorage.getItem('installPromptDismissTime') || 0);
        return !dismissed || (Date.now() - dismissTime) > 7 * 24 * 60 * 60 * 1000;
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.deferredPrompt = e;
    });

    window.openStudentInstallPrompt = function () {
        if (isInstalledOrNativeApp()) return;
        const promptEl = document.getElementById('installPrompt');
        if (promptEl) promptEl.style.display = 'block';
    };

    async function installPWA() {
        const promptEl = document.getElementById('installPrompt');
        if (window.deferredPrompt) {
            window.deferredPrompt.prompt();
            const { outcome } = await window.deferredPrompt.userChoice;
            if (outcome === 'accepted' && promptEl) {
                promptEl.style.display = 'none';
                localStorage.removeItem('installPromptDismissed');
            }
            window.deferredPrompt = null;
            return;
        }

        const guideBox = document.getElementById('installGuideBox');
        const guidePlatform = document.getElementById('installGuidePlatform');
        const guideSteps = document.getElementById('installGuideSteps');
        if (!guideBox || !guidePlatform || !guideSteps) return;

        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        guidePlatform.textContent = isIos ? 'iPhone / iPad Safari:' : 'Android Chrome / Edge:';
        guideSteps.replaceChildren();

        const steps = isIos
            ? ['Tap the Share icon in the browser bar', 'Choose "Add to Home Screen"', 'Tap "Add" to launch in full screen']
            : ['Tap the browser menu (3 dots) at the top right', 'Tap "Install app" or "Add to Home screen"', 'Confirm "Install"'];

        steps.forEach((step) => {
            const li = document.createElement('li');
            li.textContent = step;
            guideSteps.appendChild(li);
        });
        guideBox.hidden = !guideBox.hidden;
    }

    function dismissInstallPrompt() {
        const promptEl = document.getElementById('installPrompt');
        if (promptEl) promptEl.style.display = 'none';
        localStorage.setItem('installPromptDismissed', 'true');
        localStorage.setItem('installPromptDismissTime', String(Date.now()));
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('installPromptTrigger')?.addEventListener('click', installPWA);
        document.getElementById('installPromptClose')?.addEventListener('click', dismissInstallPrompt);
    });

    window.addEventListener('appinstalled', () => {
        const promptEl = document.getElementById('installPrompt');
        if (promptEl) promptEl.style.display = 'none';
        localStorage.removeItem('installPromptDismissed');
        localStorage.removeItem('installPromptDismissTime');
        localStorage.setItem('pwa_app_installed', 'true');
    });
</script>
