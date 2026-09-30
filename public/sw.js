/**
 * Receives the desktop notifications pushed by the server and shows them as
 * operating system toasts, which appear over any other software as long as
 * this browser is running.
 */
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        // A malformed payload still deserves a toast, just without the detail.
        payload = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(self.registration.showNotification(payload.title || 'Cab Services', {
        body: payload.body || '',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: payload.booking_id ? 'booking-' + payload.booking_id : undefined,
        // A refusal sets this on the message so the toast stays until dismissed.
        requireInteraction: Boolean(payload.requireInteraction),
        data: { url: payload.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
        // Reuse a tab that is already open rather than piling up new ones.
        for (const client of clientList) {
            if ('focus' in client) {
                client.navigate(target);
                return client.focus();
            }
        }

        return self.clients.openWindow(target);
    }));
});
