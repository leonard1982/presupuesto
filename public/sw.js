// Proyecto PRESUPUESTO - Service Worker base (fase preparatoria).
self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function () {
    // Estrategia de cache se definira en fase PWA activa.
});
