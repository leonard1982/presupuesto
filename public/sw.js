// Proyecto PRESUPUESTO - Service Worker de instalacion y cache ligera.
var CACHE_PREFIX = 'presupuesto-static-v';
var CACHE_VERSION = '0.6.2';
var CURRENT_CACHE = CACHE_PREFIX + CACHE_VERSION;

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CURRENT_CACHE).then(function () {
            self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.map(function (cacheName) {
                    if (cacheName.indexOf(CACHE_PREFIX) === 0 && cacheName !== CURRENT_CACHE) {
                        return caches.delete(cacheName);
                    }
                    return Promise.resolve();
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    if (!event.request || event.request.method !== 'GET') {
        return;
    }

    var requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    var isStaticAsset = requestUrl.pathname.indexOf('/public/assets/') !== -1 || requestUrl.pathname.indexOf('/public/manifest.webmanifest') !== -1;

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(function () {
                return caches.match(event.request);
            })
        );
        return;
    }

    if (!isStaticAsset) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(function (cachedResponse) {
            var fetchPromise = fetch(event.request).then(function (networkResponse) {
                if (networkResponse && networkResponse.status === 200) {
                    caches.open(CURRENT_CACHE).then(function (cache) {
                        cache.put(event.request, networkResponse.clone());
                    });
                }
                return networkResponse;
            }).catch(function () {
                return cachedResponse;
            });

            return cachedResponse || fetchPromise;
        })
    );
});
