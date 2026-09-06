{{-- Install PWA Prompt - Only shows when NOT in standalone mode --}}
<div id="installPrompt" class="install-prompt" style="display: none;">
    <div class="install-prompt-content">
        <button class="install-prompt-close" onclick="dismissInstallPrompt()">
            <i class="bi bi-x"></i>
        </button>
        
        <div class="install-prompt-icon">
            <i class="bi bi-phone"></i>
        </div>
        
        <h3>Install App for Better Experience</h3>
        <p>Add to your home screen to hide browser bars and get a native app experience.</p>
        
        <div class="install-instructions">
            <div class="install-android">
                <p><strong>Android Chrome:</strong></p>
                <ol>
                    <li>Tap menu <i class="bi bi-three-dots-vertical"></i></li>
                    <li>Tap "Install app" or "Add to Home screen"</li>
                    <li>Tap "Install"</li>
                </ol>
            </div>
            
            <div class="install-ios">
                <p><strong>iPhone Safari:</strong></p>
                <ol>
                    <li>Tap share button <i class="bi bi-box-arrow-up"></i></li>
                    <li>Scroll down and tap "Add to Home Screen"</li>
                    <li>Tap "Add"</li>
                </ol>
            </div>
        </div>
        
        <button class="install-prompt-btn" onclick="installPWA()">
            <i class="bi bi-download"></i>
            Install Now
        </button>
        
        <button class="install-prompt-later" onclick="dismissInstallPrompt()">
            Maybe Later
        </button>
    </div>
</div>

<style>
    .install-prompt {
        position: fixed;
        bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 16px);
        left: 16px;
        right: 16px;
        background: linear-gradient(135deg, rgba(207, 164, 111, 0.15), rgba(207, 164, 111, 0.05));
        border: 1px solid rgba(207, 164, 111, 0.3);
        border-radius: 16px;
        padding: 24px;
        z-index: 998;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .install-prompt-close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 32px;
        height: 32px;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: var(--text-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        cursor: pointer;
    }

    .install-prompt-icon {
        font-size: 48px;
        color: var(--gold-primary);
        text-align: center;
        margin-bottom: 16px;
    }

    .install-prompt h3 {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 8px;
        text-align: center;
    }

    .install-prompt p {
        font-size: 14px;
        color: var(--text-secondary);
        text-align: center;
        margin-bottom: 20px;
    }

    .install-instructions {
        margin-bottom: 20px;
    }

    .install-android,
    .install-ios {
        margin-bottom: 16px;
    }

    .install-instructions p {
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: var(--gold-primary);
        margin-bottom: 8px;
    }

    .install-instructions ol {
        margin: 0;
        padding-left: 20px;
        color: var(--text-secondary);
        font-size: 13px;
        line-height: 1.6;
    }

    .install-instructions li {
        margin-bottom: 4px;
    }

    .install-prompt-btn {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        color: var(--bg-dark);
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        margin-bottom: 12px;
    }

    .install-prompt-btn:active {
        transform: scale(0.98);
    }

    .install-prompt-later {
        width: 100%;
        padding: 12px;
        background: transparent;
        color: var(--text-secondary);
        border: none;
        font-size: 14px;
        cursor: pointer;
    }

    /* Hide on desktop */
    @media (min-width: 768px) {
        .install-prompt {
            display: none !important;
        }
    }

    /* Already installed */
    .standalone-mode .install-prompt {
        display: none !important;
    }
</style>

<script>
    let deferredPrompt;

    // Check if should show install prompt
    window.addEventListener('load', function() {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                            window.navigator.standalone || 
                            document.referrer.includes('android-app://');
        
        const dismissed = localStorage.getItem('installPromptDismissed');
        const dismissTime = localStorage.getItem('installPromptDismissTime');
        
        // Show prompt if not standalone and not recently dismissed (wait 7 days)
        if (!isStandalone && (!dismissed || (Date.now() - dismissTime) > 7 * 24 * 60 * 60 * 1000)) {
            setTimeout(() => {
                document.getElementById('installPrompt').style.display = 'block';
            }, 3000); // Show after 3 seconds
        }
    });

    // Capture the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        console.log('Install prompt available');
    });

    // Install PWA
    async function installPWA() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log('Install outcome:', outcome);
            
            if (outcome === 'accepted') {
                document.getElementById('installPrompt').style.display = 'none';
                localStorage.removeItem('installPromptDismissed');
            }
            
            deferredPrompt = null;
        } else {
            // Show instructions if no prompt available
            alert('To install:\n\n' +
                  'Android: Tap menu → "Install app"\n' +
                  'iPhone: Tap Share → "Add to Home Screen"');
        }
    }

    // Dismiss install prompt
    function dismissInstallPrompt() {
        document.getElementById('installPrompt').style.display = 'none';
        localStorage.setItem('installPromptDismissed', 'true');
        localStorage.setItem('installPromptDismissTime', Date.now());
    }

    // Detect when PWA is installed
    window.addEventListener('appinstalled', () => {
        console.log('PWA installed successfully');
        document.getElementById('installPrompt').style.display = 'none';
        localStorage.removeItem('installPromptDismissed');
        localStorage.removeItem('installPromptDismissTime');
    });

    // Detect when user returns to browser after uninstalling
    // If user had dismissed but is now in browser mode, clear the dismissal
    if (!window.matchMedia('(display-mode: standalone)').matches && 
        !window.navigator.standalone && 
        !document.referrer.includes('android-app://')) {
        // User is in browser mode
        // If they previously dismissed but it's been more than 1 day, reset
        const dismissTime = localStorage.getItem('installPromptDismissTime');
        if (dismissTime && (Date.now() - dismissTime) > 24 * 60 * 60 * 1000) {
            // Been 1 day since dismissal, and they're back in browser (likely uninstalled)
            localStorage.removeItem('installPromptDismissed');
            localStorage.removeItem('installPromptDismissTime');
        }
    }
</script>
