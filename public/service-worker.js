self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
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

    event.respondWith(
        caches.match(event.request).then((response) => response || fetch(event.request))
    );
});
