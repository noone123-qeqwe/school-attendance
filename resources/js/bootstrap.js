import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.content?.trim() ?? '';
const envOrMeta = (envValue, metaName) => envValue?.trim() || metaContent(metaName);

const reverbKey = envOrMeta(import.meta.env.VITE_REVERB_APP_KEY, 'reverb-app-key');
const reverbHost = envOrMeta(import.meta.env.VITE_REVERB_HOST, 'reverb-host');
const reverbPort = envOrMeta(import.meta.env.VITE_REVERB_PORT, 'reverb-port');
const reverbScheme = envOrMeta(import.meta.env.VITE_REVERB_SCHEME, 'reverb-scheme') || 'https';

window.Echo = reverbKey && reverbHost
    ? new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: Number(reverbPort || 80),
        wssPort: Number(reverbPort || 443),
        forceTLS: reverbScheme === 'https',
        enabledTransports: reverbScheme === 'https' ? ['wss', 'ws'] : ['ws'],
    })
    : null;
