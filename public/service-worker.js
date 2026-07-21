self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // This worker does not maintain an application cache. Remove caches left
    // behind by older releases so stale HTML cannot load a newer JS bundle.
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(
                cacheNames.map((cacheName) => caches.delete(cacheName))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Vite/HMR and third-party requests must go directly to the network.
    if (
        event.request.method !== 'GET'
        || url.origin !== self.location.origin
        || url.pathname.startsWith('/@vite')
        || url.pathname.startsWith('/@react-refresh')
        || url.pathname.startsWith('/resources/')
        || url.pathname.startsWith('/node_modules/')
    ) {
        return;
    }

    // Always fetch navigations and versioned Vite assets from the network.
    // Their HTML/manifest pairing must come from the same deployment.
    if (event.request.mode === 'navigate' || url.pathname.startsWith('/build/')) {
        event.respondWith(fetch(event.request));
        return;
    }

    event.respondWith(fetch(event.request));
});
