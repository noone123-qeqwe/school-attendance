import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.content?.trim() ?? '';
const envOrMeta = (envValue, metaName) => envValue?.trim() || metaContent(metaName);

const reverbKey = envOrMeta(import.meta.env.VITE_REVERB_APP_KEY, 'reverb-app-key');
const reverbHost = envOrMeta(import.meta.env.VITE_REVERB_HOST, 'reverb-host') || window.location.hostname;
const reverbPort = envOrMeta(import.meta.env.VITE_REVERB_PORT, 'reverb-port');
const reverbScheme = envOrMeta(import.meta.env.VITE_REVERB_SCHEME, 'reverb-scheme') || 'https';
const authUserId = metaContent('auth-user-id');
const port = Number(reverbPort || 8080);
const forceTLS = reverbScheme === 'https' || window.location.protocol === 'https:';

window.Echo = null;
if (reverbKey) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: port,
            wssPort: port,
            forceTLS,
            enabledTransports: forceTLS ? ['wss', 'ws'] : ['ws'],
        });
    } catch (error) {
        console.warn('Live notifications are unavailable.', error);
    }
}

window.adminEcho = window.Echo;
window.teacherEcho = window.Echo;

if (window.Echo && authUserId) {
    try {
        window.Echo.private(`notifications.${authUserId}`)
            .listen('.notification.sent', (event) => {
                window.refreshNotificationBell?.();
                if (typeof window.showToast === 'function') {
                    window.showToast(event.message, event.type || 'custom');
                }
            });
    } catch (error) {
        console.warn('Live notifications could not be initialized.', error);
    }
}
