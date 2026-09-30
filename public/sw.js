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

    // The notification puts its own fields in `data()` and the toast text at the
    // top level, so title/body are read from the top and the rest from `data`.
    // `requireInteraction` is a top level flag of the message itself.
    const data = payload.data || {};
    const title = payload.title || data.title || 'Cab Services';
    const body = payload.body || data.body || '';

    event.waitUntil(self.registration.showNotification(title, {
        body,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        // Tagging by booking replaces an earlier toast for the same ride instead
        // of stacking them up when the driver answers more than once.
        tag: data.booking_id ? 'booking-' + data.booking_id : undefined,
        // A refusal sets this on the message so the toast stays until dismissed.
        requireInteraction: Boolean(payload.requireInteraction),
        data: { url: data.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
        // Reuse a tab of this app rather than piling up new ones, but leave
        // unrelated tabs alone.
        const sameOrigin = clientList.find((client) => client.url.startsWith(self.location.origin));

        if (sameOrigin && 'focus' in sameOrigin) {
            return sameOrigin.navigate(target).then((client) => client.focus());
        }

        return self.clients.openWindow(target);
    }));
});
