const CACHE_VERSION = '2026-07-07-v1';
const STATIC_CACHE = `prawkonaraz-static-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/favicon.ico',
    '/favicon.png',
    '/apple-touch-icon.png',
    '/pwa/icon-192.png',
    '/pwa/icon-512.png',
    '/pwa/maskable-192.png',
    '/pwa/maskable-512.png',
];

const PRIVATE_PATH_PREFIXES = [
    '/api/',
    '/auth/',
    '/sanctum/',
    '/login',
    '/logout',
    '/dashboard',
    '/profile',
    '/checkout',
    '/nauka',
    '/study-sessions',
    '/trener-pamieci',
    '/ranking',
    '/admin',
    '/moderator',
];

const STATIC_PATH_PREFIXES = [
    '/build/assets/',
    '/pwa/',
    '/css/',
    '/fonts/',
    '/images/',
    '/js/',
    '/traffic-signs/',
];

const STATIC_EXACT_PATHS = [
    '/favicon.ico',
    '/favicon.png',
    '/apple-touch-icon.png',
];

const isSameOrigin = (url) => url.origin === self.location.origin;

const isPrivateRequest = (url) => PRIVATE_PATH_PREFIXES.some((path) => (
    url.pathname === path || url.pathname.startsWith(path)
));

const isStaticAsset = (url) => (
    STATIC_PATH_PREFIXES.some((path) => url.pathname.startsWith(path))
    || STATIC_EXACT_PATHS.includes(url.pathname)
);

const cacheFirst = async (request) => {
    const cache = await caches.open(STATIC_CACHE);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
        cache.put(request, networkResponse.clone());
    }

    return networkResponse;
};

const networkFirst = async (request) => {
    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        throw error;
    }
};

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS)),
    );

    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('prawkonaraz-') && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (!isSameOrigin(url)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL)),
        );

        return;
    }

    if (isPrivateRequest(url)) {
        return;
    }

    if (url.pathname === '/manifest.webmanifest') {
        event.respondWith(networkFirst(request));

        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
    }
});
