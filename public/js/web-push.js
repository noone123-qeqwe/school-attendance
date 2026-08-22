/**
 * Web Push Notification Manager
 * Handles Service Worker PushManager subscription, permission flows, and server sync.
 */

window.WebPushManager = (function() {
    let isSubscribed = false;
    let swRegistration = null;

    // Helper: Convert VAPID base64 string to Uint8Array
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Helper: Get CSRF token
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Check browser compatibility
    function isSupported() {
        return ('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window);
    }

    // Initialize Push Manager on page load
    async function init() {
        if (!isSupported()) {
            console.log('[WebPush] Push notifications are not supported in this browser.');
            updateUI(false, false);
            return;
        }

        try {
            swRegistration = await navigator.serviceWorker.ready;
            const subscription = await swRegistration.pushManager.getSubscription();
            
            isSubscribed = !(subscription === null);

            if (isSubscribed) {
                console.log('[WebPush] User is actively subscribed to Web Push.');
                // Re-sync subscription with backend in background
                syncSubscription(subscription);
            } else {
                console.log('[WebPush] User is not subscribed.');
                if (Notification.permission === 'granted') {
                    subscribe();
                } else if (Notification.permission === 'default') {
                    setTimeout(showPushPromptBanner, 3500);
                }
            }

            updateUI(true, isSubscribed);
        } catch (error) {
            console.error('[WebPush] Initialization error:', error);
            updateUI(true, false);
        }
    }

    // Sync subscription payload to Laravel backend
    async function syncSubscription(subscription) {
        try {
            const rawKey = subscription.getKey ? subscription.getKey('p256dh') : '';
            const key = rawKey ? btoa(String.fromCharCode.apply(null, new Uint8Array(rawKey))) : '';
            const rawAuthSecret = subscription.getKey ? subscription.getKey('auth') : '';
            const authSecret = rawAuthSecret ? btoa(String.fromCharCode.apply(null, new Uint8Array(rawAuthSecret))) : '';

            const payload = {
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: key,
                    auth: authSecret
                },
                content_encoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0] || 'aes128gcm',
                device_name: getDeviceName()
            };

            await fetch('/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
        } catch (err) {
            console.warn('[WebPush] Subscription sync error:', err);
        }
    }

    // Friendly device name guess
    function getDeviceName() {
        const ua = navigator.userAgent;
        if (/android/i.test(ua)) return 'Android Device';
        if (/iPad|iPhone|iPod/.test(ua)) return 'iOS Device';
        if (/windows/i.test(ua)) return 'Windows PC';
        if (/macintosh/i.test(ua)) return 'Mac OS';
        if (/linux/i.test(ua)) return 'Linux';
        return 'Web Browser';
    }

    // Subscribe user to Push Notifications
    async function subscribe() {
        if (!isSupported()) {
            showToast('Push notifications are not supported by your browser.', 'warning');
            return false;
        }

        try {
            // 1. Fetch public VAPID key
            const keyRes = await fetch('/push/public-key');
            const keyData = await keyRes.json();

            if (!keyData.success || !keyData.publicKey) {
                showToast('Push notifications are not configured on the server yet.', 'warning');
                return false;
            }

            // 2. Request Notification permission
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                showToast('Push notification permission was declined or blocked.', 'warning');
                updateUI(true, false);
                return false;
            }

            // 3. Register with PushManager
            swRegistration = await navigator.serviceWorker.ready;
            const applicationServerKey = urlBase64ToUint8Array(keyData.publicKey);

            const subscription = await swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            // 4. Save to backend
            await syncSubscription(subscription);

            isSubscribed = true;
            updateUI(true, true);
            showToast('Web Push notifications enabled successfully!', 'success');
            return true;
        } catch (error) {
            console.error('[WebPush] Failed to subscribe:', error);
            showToast('Failed enabling push notifications: ' + error.message, 'error');
            updateUI(true, false);
            return false;
        }
    }

    // Unsubscribe user
    async function unsubscribe() {
        if (!isSupported()) return;

        try {
            swRegistration = await navigator.serviceWorker.ready;
            const subscription = await swRegistration.pushManager.getSubscription();

            if (subscription) {
                const endpoint = subscription.endpoint;
                await subscription.unsubscribe();

                await fetch('/push/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ endpoint: endpoint })
                });
            }

            isSubscribed = false;
            updateUI(true, false);
            showToast('Push notifications disabled for this device.', 'info');
            return true;
        } catch (error) {
            console.error('[WebPush] Error unsubscribing:', error);
            showToast('Error disabling push notifications: ' + error.message, 'error');
            return false;
        }
    }

    // Send instant test notification
    async function sendTest() {
        try {
            const res = await fetch('/push/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (data.success) {
                showToast(data.message || 'Test push notification dispatched!', 'success');
            } else {
                showToast(data.message || 'Failed to dispatch test push.', 'warning');
            }
        } catch (e) {
            showToast('Error sending test push: ' + e.message, 'error');
        }
    }

    // Update UI elements across the page
    function updateUI(supported, subscribed) {
        const toggleInputs = document.querySelectorAll('.push-toggle-input');
        const statusBadges = document.querySelectorAll('.push-status-badge');
        const testButtons = document.querySelectorAll('.push-test-btn');

        toggleInputs.forEach(input => {
            input.disabled = !supported;
            input.checked = subscribed;
        });

        statusBadges.forEach(badge => {
            if (!supported) {
                badge.className = 'push-status-badge badge-unsupported';
                badge.textContent = 'Unsupported';
            } else if (subscribed) {
                badge.className = 'push-status-badge badge-active';
                badge.textContent = 'Active (Subscribed)';
            } else {
                badge.className = 'push-status-badge badge-inactive';
                badge.textContent = 'Inactive';
            }
        });

        testButtons.forEach(btn => {
            btn.style.display = (supported && subscribed) ? 'inline-flex' : 'none';
        });
    }

    // Simple toast helper
    function showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }
        if (typeof Toastify !== 'undefined') {
            const bg = type === 'success' ? '#22c55e' : (type === 'error' ? '#ef4444' : (type === 'warning' ? '#f59e0b' : '#3b82f6'));
            Toastify({
                text: message,
                duration: 3500,
                gravity: "top",
                position: "right",
                style: { background: bg, color: "#ffffff", borderRadius: "10px", fontWeight: "600" }
            }).showToast();
        } else {
            console.log(`[Toast ${type}] ${message}`);
        }
    }

    // Floating Push Notification Prompt Banner
    function showPushPromptBanner() {
        if (localStorage.getItem('push_prompt_dismissed_until')) {
            const dismissedUntil = parseInt(localStorage.getItem('push_prompt_dismissed_until'), 10);
            if (Date.now() < dismissedUntil) return;
        }

        if (document.getElementById('webPushPromptBanner')) return;

        const banner = document.createElement('div');
        banner.id = 'webPushPromptBanner';
        banner.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            max-width: 380px;
            background: rgba(26, 17, 16, 0.98);
            border: 1px solid rgba(207, 164, 111, 0.4);
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.7), 0 0 25px rgba(207, 164, 111, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 99998;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            animation: pwaSlideUp 0.4s ease;
        `;

        banner.innerHTML = `
            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,rgba(207,164,111,0.2),rgba(207,164,111,0.05));border:1px solid rgba(207,164,111,0.3);display:flex;align-items:center;justify-content:center;color:#CFA46F;font-size:1.1rem;flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.92rem;font-weight:700;color:#F3E7CD;margin-bottom:3px;">Enable Live Alerts</div>
                <div style="font-size:0.8rem;color:#B39B82;line-height:1.4;margin-bottom:12px;">Get instant class announcements, clock-in confirmations, and system updates directly on this device.</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" id="enablePushBannerBtn" style="background:linear-gradient(135deg,#CFA46F,#8F6E4A);color:#110A0A;border:none;border-radius:8px;padding:7px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;transition:all 0.2s;">
                        Enable Alerts
                    </button>
                    <button type="button" id="dismissPushBannerBtn" style="background:transparent;color:#B39B82;border:none;padding:7px 10px;font-size:0.8rem;cursor:pointer;">
                        Not now
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(banner);

        document.getElementById('enablePushBannerBtn')?.addEventListener('click', async () => {
            banner.remove();
            await subscribe();
        });

        document.getElementById('dismissPushBannerBtn')?.addEventListener('click', () => {
            banner.remove();
            // Suppress prompt for 3 days
            localStorage.setItem('push_prompt_dismissed_until', (Date.now() + 3 * 24 * 60 * 60 * 1000).toString());
        });
    }

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return {
        init: init,
        subscribe: subscribe,
        unsubscribe: unsubscribe,
        sendTest: sendTest,
        showPushPromptBanner: showPushPromptBanner,
        isSupported: isSupported,
        isSubscribed: () => isSubscribed
    };
})();
