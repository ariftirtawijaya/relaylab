// service-worker.js
const SW_VERSION = 'no-cache-v1';

self.addEventListener('install', () => {
    self.skipWaiting(); // langsung aktif
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        // bersihkan SEMUA cache lama kalau pernah pakai
        const names = await caches.keys();
        await Promise.all(names.map((n) => caches.delete(n)));
        // aktifkan navigation preload (opsional)
        if (self.registration.navigationPreload) {
            await self.registration.navigationPreload.enable();
        }
        await self.clients.claim();
    })());
});

// Network-only: tidak menyentuh Cache Storage
self.addEventListener('fetch', (event) => {
    event.respondWith((async () => {
        const req = new Request(event.request, { cache: 'no-store' });
        const preloaded = await event.preloadResponse;
        if (preloaded) return preloaded;
        return fetch(req);
    })());
});
