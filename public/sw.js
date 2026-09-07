/* BUMP_TIMESTAMP: 2026-09-06T14:30:00+08:00 */
const CACHE_VERSION = 'v220';
const CACHE_NAME = `attendance-v220`;
const STATIC_CACHE_NAME = CACHE_NAME;
const RUNTIME_CACHE_NAME = `attendance-runtime-v220`;
const OFFLINE_URL = '/offline';
const FALLBACK_IMAGE = `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="%23CFA46F" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>`;

// Core essential resources to pre-cache on install
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/manifest.json',
    '/images/logo.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
    '/images/icons/icon-maskable-512x512.png',
    '/css/design-tokens.css',
    '/css/premium.css',
    '/css/dashboard-enterprise.css',
    '/css/mobile-enterprise.css',
    '/js/html5-qrcode.min.js'
];

// Maximum items to keep in runtime cache
const MAX_RUNTIME_ITEMS = 50;

async function trimCache(cacheName, maxItems) {
    try {
        const cache = await caches.open(cacheName);
        const keys = await cache.keys();
        if (keys.length > maxItems) {
            await cache.delete(keys[0]);
            await trimCache(cacheName, maxItems);
        }
    } catch (e) {
        console.warn('[PWA SW] Cache trim error:', e);
    }
}

// Install: precache essential offline assets peacefully in background (no force skipWaiting)
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[PWA SW] Pre-cache partial warning:', err);
            });
        }).then(() => {
            // Instantly notify all open window clients that an update is installed and ready
            return self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
                windowClients.forEach((client) => {
                    client.postMessage({
                        type: 'SW_UPDATED',
                        version: CACHE_VERSION,
                        timestamp: Date.now()
                    });
                });
            }).catch(() => {});
        })
    );
});

// Activate: clean up old caches (seamless, no auto-reloading or claiming open windows)
self.addEventListener('activate', (event) => {
    const validCaches = [STATIC_CACHE_NAME, RUNTIME_CACHE_NAME];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (!validCaches.includes(cacheName)) {
                        console.log('[PWA SW] Clearing old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            if (self.registration.navigationPreload) {
                return self.registration.navigationPreload.enable();
            }
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// Fetch Strategy:
// 1. Navigation (HTML pages): Network-first with /offline fallback
// 2. Static Assets (CSS, JS, Fonts): Stale-While-Revalidate
// 3. Media/Images: Cache-First with network fallback
// 4. API & Non-GET requests: Direct network pass-through
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // 1. Navigation requests (Page transitions, link clicks)
    if (request.mode === 'navigate') {
        event.respondWith(
            (async () => {
                try {
                    const preloadResponse = await event.preloadResponse;
                    if (preloadResponse) {
                        return preloadResponse;
                    }
                    const networkResponse = await fetch(request);
                    return networkResponse;
                } catch (error) {
                    const cache = await caches.open(STATIC_CACHE_NAME);
                    const offlineResponse = await cache.match(OFFLINE_URL);
                    return offlineResponse || new Response(
                        '<!DOCTYPE html><html><head><title>Offline</title></head><body><h1>Offline</h1><p>Please check your internet connection.</p></body></html>',
                        { headers: { 'Content-Type': 'text/html' } }
                    );
                }
            })()
        );
        return;
    }

    // 2. Static Assets (CSS, JS, Web Fonts) -> Stale-While-Revalidate
    const isStyleOrScript = (
        url.pathname.startsWith('/css/') ||
        url.pathname.startsWith('/js/') ||
        url.pathname.startsWith('/build/') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com') ||
        url.hostname.includes('cdn.jsdelivr.net')
    );

    if (isStyleOrScript) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && (networkResponse.status === 200 || networkResponse.status === 0) && networkResponse.type !== 'error') {
                        const responseToCache = networkResponse.clone();
                        caches.open(RUNTIME_CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                            trimCache(RUNTIME_CACHE_NAME, MAX_RUNTIME_ITEMS);
                        });
                    }
                    return networkResponse;
                }).catch(() => null);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // 3. Static Images & Icons -> Cache-First
    const isImage = (
        url.pathname.startsWith('/images/') ||
        request.destination === 'image'
    );

    if (isImage) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && (networkResponse.status === 200 || networkResponse.status === 0) && networkResponse.type !== 'error') {
                        const responseToCache = networkResponse.clone();
                        caches.open(RUNTIME_CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                            trimCache(RUNTIME_CACHE_NAME, MAX_RUNTIME_ITEMS);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    return new Response(FALLBACK_IMAGE, {
                        headers: { 'Content-Type': 'image/svg+xml' }
                    });
                });
            })
        );
        return;
    }

    // 4. All other requests pass through natively to the browser
});

// Push Notifications Listener
self.addEventListener('push', (event) => {
    if (!event.data) return;

    try {
        const payload = event.data.json();
        const title = payload.title || 'Attendance System Alert';
        const options = {
            body: payload.body || 'You have a new school update.',
            icon: payload.icon || '/images/icons/icon-192x192.png',
            badge: '/images/icons/icon-72x72.png',
            data: {
                url: payload.url || '/home',
                tag: payload.tag || ''
            },
            vibrate: [100, 50, 100],
            actions: payload.actions || [
                { action: 'open', title: 'Open App' }
            ]
        };

        // Instantly notify all open desktop/mobile browser windows
        const notifyClientsPromise = clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            windowClients.forEach((client) => {
                client.postMessage({
                    type: 'UPDATE_AVAILABLE',
                    title: title,
                    body: payload.body,
                    timestamp: Date.now()
                });
            });
        });

        event.waitUntil(
            Promise.all([
                self.registration.showNotification(title, options),
                notifyClientsPromise
            ])
        );
    } catch (e) {
        const title = 'Attendance Notification';
        const options = {
            body: event.data.text(),
            icon: '/images/icons/icon-192x192.png'
        };
        event.waitUntil(self.registration.showNotification(title, options));
    }
});

// Notification Click Handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (let client of windowClients) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// Message Listener (skipWaiting, clearCache)
self.addEventListener('message', (event) => {
    if (!event.data) return;
    const action = event.data.action || event.data.type;
    if (action === 'skipWaiting' || action === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (action === 'clearCache' || action === 'CLEAR_CACHE') {
        caches.keys().then((keys) => {
            return Promise.all(keys.map((k) => caches.delete(k)));
        });
    }
});
