// Invita service worker: minimal offline fallback + web push handling.
// Deliberately does NOT cache the app shell/JS bundles — this is a fast-moving
// Inertia SPA, and caching hashed build assets aggressively risks serving a
// stale bundle after a deploy. It only caches the tiny offline fallback itself.

const CACHE_NAME = 'invita-shell-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll([OFFLINE_URL, '/icons/icon-192.png']))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

// Only intercepts page navigations, and only to show the offline page when the
// network truly fails — everything else (assets, API calls) passes straight through.
self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return;

    event.respondWith(
        fetch(event.request).catch(() => caches.match(OFFLINE_URL))
    );
});

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Invita', body: event.data.text() };
    }

    const title = payload.title ?? 'Invita';
    const options = {
        body: payload.body,
        icon: payload.icon ?? '/icons/icon-192.png',
        badge: payload.badge ?? '/icons/icon-192.png',
        data: payload.data ?? {},
        tag: payload.tag,
        requireInteraction: payload.requireInteraction ?? false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Focuses an already-open tab on the target URL when possible, instead of
// always spawning a new one.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url ?? '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if (client.url === url && 'focus' in client) return client.focus();
            }
            return self.clients.openWindow(url);
        })
    );
});
