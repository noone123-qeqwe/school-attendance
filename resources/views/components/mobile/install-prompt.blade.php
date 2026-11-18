{{-- Install PWA Prompt - Only shows when NOT in standalone mode --}}
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

<style>
    .install-prompt {
        position: fixed;
        bottom: calc(var(--bottom-nav-height, 76px) + var(--safe-bottom, 0px) + 22px);
        left: 12px;
        right: 12px;
        max-width: 440px;
        margin-inline: auto;
        background: rgba(24, 15, 13, 0.94);
        border: 1px solid rgba(207, 164, 111, 0.3);
        border-radius: 18px;
        padding: 12px 14px;
        z-index: 998;
        box-shadow: 0 14px 34px rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        animation: slideUpPrompt 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUpPrompt {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .install-prompt-row {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .install-prompt-badge {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.24), rgba(122, 26, 26, 0.35));
        border: 1px solid rgba(212, 175, 55, 0.35);
        color: var(--gold-primary, #D4AF37);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .install-prompt-copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .install-prompt-copy strong {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-primary, #FCF8F2);
        line-height: 1.2;
    }

    .install-prompt-copy span {
        font-size: 0.75rem;
        color: var(--text-secondary, #D1C5B4);
        line-height: 1.3;
    }

    .install-prompt-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .install-prompt-btn {
        min-height: 36px;
        padding: 0 13px;
        background: linear-gradient(135deg, var(--gold-primary, #D4AF37), var(--gold-dark, #A68822));
        color: #140907;
        border: none;
        border-radius: 10px;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
    }

    .install-prompt-btn:active {
        transform: scale(0.95);
    }

    .install-prompt-close {
        width: 36px;
        height: 36px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-secondary, #D1C5B4);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
    }

    .install-guide-box {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .install-guide-title {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--gold-primary, #D4AF37);
        margin: 0 0 6px;
        text-align: left;
    }

    .install-guide-steps {
        margin: 0;
        padding-left: 18px;
        color: var(--text-secondary, #D1C5B4);
        font-size: 0.76rem;
        line-height: 1.5;
    }

    /* Hide on desktop & when modal/keyboard/scanner overlays are active */
    @media (min-width: 768px) {
        .install-prompt {
            display: none !important;
        }
    }

    .standalone-mode .install-prompt,
    body.keyboard-open .install-prompt,
    body.modal-open .install-prompt,
    body.scanner-active .install-prompt {
        display: none !important;
    }
</style>

<script @cspNonce>
    if (typeof window.deferredPrompt === 'undefined') {
        window.deferredPrompt = null;
    }

    window.addEventListener('load', function() {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                            window.navigator.standalone ||
                            document.referrer.includes('android-app://');

        const dismissed = localStorage.getItem('installPromptDismissed');
        const dismissTime = localStorage.getItem('installPromptDismissTime');

        if (!isStandalone && (!dismissed || (Date.now() - dismissTime) > 7 * 24 * 60 * 60 * 1000)) {
            setTimeout(() => {
                const promptEl = document.getElementById('installPrompt');
                if (promptEl) promptEl.style.display = 'block';
            }, 3000);
        }
    });

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.deferredPrompt = e;
    });

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
        localStorage.setItem('installPromptDismissTime', Date.now());
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

    if (!window.matchMedia('(display-mode: standalone)').matches &&
        !window.navigator.standalone &&
        !document.referrer.includes('android-app://')) {
        const dismissTime = localStorage.getItem('installPromptDismissTime');
        if (dismissTime && (Date.now() - dismissTime) > 24 * 60 * 60 * 1000) {
            localStorage.removeItem('installPromptDismissed');
            localStorage.removeItem('installPromptDismissTime');
        }
    }
</script>
