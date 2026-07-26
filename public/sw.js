// Board — service worker.
// Doel: installeerbaar (PWA) en offline-lezen van eerder bezochte boards.

const CACHE = 'board-v1';
const APP_SHELL = ['/', '/offline'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(APP_SHELL).catch(() => undefined)),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Gehashte build-assets: cache-first (immutable).
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetchAndCache(request)),
        );
        return;
    }

    // Navigaties: network-first met cache-fallback (offline-lezen).
    if (request.mode === 'navigate') {
        event.respondWith(
            fetchAndCache(request).catch(() => caches.match(request).then((c) => c || caches.match('/offline'))),
        );
    }
});

function fetchAndCache(request) {
    return fetch(request).then((response) => {
        if (response.ok) {
            const copy = response.clone();
            caches.open(CACHE).then((cache) => cache.put(request, copy));
        }
        return response;
    });
}
