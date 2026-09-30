

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// The desktop notification card needs to call the browser APIs, which are
// attached further down this file, so it is registered with Alpine up front.
Alpine.data('desktopNotificationCard', () => ({
    message: '',
    failed: false,

    async enable() {
        const result = await window.enableDesktopNotifications();

        this.message = result.message;
        this.failed = !result.ok;
    },

    async disable() {
        const result = await window.disableDesktopNotifications();

        this.message = result.message;
        this.failed = !result.ok;
    },
}));

Alpine.start();

/**
 * Desktop notifications.
 *
 * The service worker lives at the site root so its scope covers every panel,
 * but it is only registered for the pages that actually receive pushes: the
 * public booking site has no use for it.
 */
const pushKey = document.querySelector('meta[name="vapid-public-key"]')?.content;

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');

    const raw = window.atob(base64);

    return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
}

async function pushRegistration() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return null;
    }

    return navigator.serviceWorker.register('/sw.js');
}

/**
 * Turn desktop notifications on. Must run from a real click, because the
 * browser only grants permission from a user gesture.
 */
window.enableDesktopNotifications = async function () {
    if (!('Notification' in window) || !pushKey) {
        return { ok: false, message: 'This browser cannot show desktop notifications.' };
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        return { ok: false, message: 'Desktop notifications were blocked by the browser.' };
    }

    const registration = await pushRegistration();

    if (!registration) {
        return { ok: false, message: 'This browser cannot show desktop notifications.' };
    }

    const existing = await registration.pushManager.getSubscription();

    const subscription = existing ?? await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(pushKey),
    });

    const response = await fetch('/notifications/subscription', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify(subscription.toJSON()),
    });

    if (!response.ok) {
        return { ok: false, message: 'The subscription could not be saved.' };
    }

    return { ok: true, message: 'Desktop notifications are on for this browser.' };
};

/**
 * Stop pushing to this browser and forget the subscription.
 */
window.disableDesktopNotifications = async function () {
    const registration = await pushRegistration();

    if (registration) {
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await fetch('/notifications/subscription', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ endpoint: subscription.endpoint }),
            });

            await subscription.unsubscribe();
        }
    }

    return { ok: true, message: 'Desktop notifications are off for this browser.' };
};

/**
 * Whether this browser is already subscribed, so the screen can show the
 * correct button before the user clicks anything.
 */
window.desktopNotificationStatus = async function () {
    if (!('Notification' in window) || Notification.permission === 'denied') {
        return 'unsupported';
    }

    const registration = await pushRegistration();

    if (!registration) {
        return 'unsupported';
    }

    const subscription = await registration.pushManager.getSubscription();

    return subscription ? 'enabled' : 'disabled';
};

// Register the worker on every page of the panels that can receive a push, so
// a toast arrives even when the user is on a different screen.
if (pushKey) {
    pushRegistration();
}

