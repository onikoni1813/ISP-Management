// Pirgacha Internet PWA Service Worker v6.0
const CACHE_NAME = 'pirgacha-isp-cache-v6';
const STATIC_ASSETS = [
    '/',
    '/staff/login',
    '/staff/dashboard',
    '/manifest.json',
    '/logo.png',
    '/icon-192.png',
    '/icon-512.png',
    '/favicon.ico',
    '/favicon-32x32.png',
    '/favicon-16x16.png',
    '/apple-touch-icon.png',
];

// Install: Pre-cache static app shell and critical resources
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate: Clean up previous caches and claim clients immediately
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// Fetch: Strategy based on request type
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests (mutations are handled by IndexedDB sync queue)
    if (request.method !== 'GET') {
        return;
    }

    // Never cache admin routes or admin API calls in Service Worker
    if (url.pathname.startsWith('/admin')) {
        return;
    }

    // Static Assets & Scripts (Vite build assets, images, fonts) -> Cache-First
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js')
    ) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // Inertia X-Inertia requests & HTML Navigation Pages -> Network-First with Cache Fallback
    const isInertiaRequest = request.headers.get('x-inertia') === 'true';
    const isNavigation = request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html');

    if (isNavigation || isInertiaRequest) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    return caches.match(request).then((cached) => {
                        if (cached) return cached;
                        if (url.pathname.startsWith('/staff')) {
                            return caches.match('/staff/dashboard') || caches.match('/staff/login');
                        }
                        return caches.match('/');
                    });
                })
        );
        return;
    }

    // Default: Stale-While-Revalidate for other GET requests
    event.respondWith(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        cache.put(request, networkResponse.clone());
                    }
                    return networkResponse;
                }).catch(() => {
                    return cachedResponse;
                });
                return cachedResponse || fetchPromise;
            });
        })
    );
});

