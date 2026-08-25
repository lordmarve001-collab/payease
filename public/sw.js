const CACHE_NAME = 'payease-v1';
const STATIC_CACHE = 'payease-static-v1';
const DYNAMIC_CACHE = 'payease-dynamic-v1';

const STATIC_ASSETS = [
    '/',
    '/login',
    '/register',
    '/css/app.css',
    '/js/app.js',
    '/manifest.json',
    '/favicon.ico',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
];

const PRECACHE_ROUTES = [
    '/login',
    '/dashboard',
    '/send-money',
    '/pay-bills',
    '/buy-airtime',
    '/my-ajo',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch(() => {
                return Promise.allSettled(
                    STATIC_ASSETS.map((url) => cache.add(url).catch(() => null))
                );
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== STATIC_CACHE && name !== DYNAMIC_CACHE)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    if (request.url.includes('/livewire/update') || request.url.includes('/api/')) {
        event.respondWith(
            fetch(request).catch(() => {
                return new Response(JSON.stringify({ error: 'Offline' }), {
                    headers: { 'Content-Type': 'application/json' },
                    status: 503,
                });
            })
        );
        return;
    }

    if (request.url.includes('/build/')) {
        event.respondWith(
            caches.open(STATIC_CACHE).then((cache) => {
                return cache.match(request).then((cached) => {
                    if (cached) return cached;
                    return fetch(request).then((response) => {
                        if (response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    });
                });
            })
        );
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) return cached;

            return fetch(request)
                .then((response) => {
                    if (response.ok && request.url.startsWith(self.location.origin)) {
                        const responseClone = response.clone();
                        caches.open(DYNAMIC_CACHE).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    if (request.destination === 'document') {
                        return caches.match('/login');
                    }
                    return new Response('', { status: 503 });
                });
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const action = event.action;
    const data = event.notification.data;

    let url = '/dashboard';
    if (action === 'send-money') url = '/send-money';
    else if (action === 'pay-bills') url = '/pay-bills';
    else if (data?.url) url = data.url;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        payload = {
            title: 'PayEase',
            body: event.data.text(),
        };
    }

    const options = {
        body: payload.body || 'You have a new notification',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-96x96.png',
        vibrate: [200, 100, 200],
        tag: payload.tag || 'payease-notification',
        renotify: true,
        data: payload.data || {},
        actions: payload.actions || [
            { action: 'open', title: 'Open App' },
        ],
    };

    event.waitUntil(
        self.registration.showNotification(payload.title || 'PayEase', options)
    );
});
