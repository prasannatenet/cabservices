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

/**
 * The bell in the navigation bar.
 *
 * The route URLs are passed in as options because the admin and the associate
 * render the same bell from their own navigation partials.
 */
Alpine.data('notificationBell', (routes = {}) => ({
    open: false,
    loading: false,
    notifications: [],
    unreadCount: 0,

    init() {
        this.load();
    },

    async load() {
        this.loading = true;

        try {
            const response = await fetch(routes.index, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            this.notifications = data.notifications ?? [];
            this.unreadCount = data.unread_count ?? 0;
        } catch (error) {
            // A failed poll must never break the panel around the bell.
        } finally {
            this.loading = false;
        }
    },

    async toggle() {
        this.open = !this.open;

        if (this.open) {
            this.load();
        }
    },

    /**
     * Clear one notification. The badge drops straight away so the click feels
     * instant; a request that fails is corrected by the next load.
     */
    async markRead(id) {
        if (!this.notifications.some((notification) => notification.id === id)) {
            return;
        }

        this.notifications = this.notifications.filter((notification) => notification.id !== id);
        this.unreadCount = Math.max(0, this.unreadCount - 1);

        try {
            await this.post(routes.read.replace('__id__', encodeURIComponent(id)));
        } catch (error) {
            this.load();
        }
    },

    async markAllRead() {
        this.notifications = [];
        this.unreadCount = 0;

        try {
            await this.post(routes.readAll);
        } catch (error) {
            this.load();
        }
    },

    post(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
    },
}));

/**
 * The booking review page. The customer can still correct the dates on the
 * prefilled trip form, so the amount he is about to request is worked out again
 * with the rule the server bills with: one day for a hire that has no drop date
 * or drops on the day it starts, otherwise the pickup day up to and including
 * the drop day.
 */
Alpine.data('bookingReview', ({ ratePerDay = 0, kmPerDay = 0, pickupDate = '', dropDate = '' }) => ({
    pickupDate,
    dropDate,
    ratePerDay: Number(ratePerDay),
    kmPerDay: Number(kmPerDay),

    get days() {
        if (!this.pickupDate || !this.dropDate) {
            return 1;
        }

        const start = new Date(`${this.pickupDate}T00:00:00`);
        const end = new Date(`${this.dropDate}T00:00:00`);

        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {
            return 1;
        }

        return Math.round((end - start) / 86400000) + 1;
    },

    get total() {
        return this.days * this.ratePerDay;
    },

    get includedKm() {
        return this.days * this.kmPerDay;
    },

    money(value) {
        return Number(value).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    },

    whole(value) {
        return Number(value).toLocaleString('en-IN');
    },
}));

/**
 * The services a vehicle provides. One service is picked at a time from the
 * dropdown, and each pick joins the chosen ones shown above it; taking a pick
 * off puts that service back into the dropdown.
 */
Alpine.data('servicePicker', (services = [], pickedIds = []) => ({
    services,
    selected: [],
    choice: '',

    init() {
        const picked = pickedIds.map((id) => Number(id));
        this.selected = this.services.filter((service) => picked.includes(Number(service.id)));
    },

    get available() {
        const picked = this.selected.map((service) => Number(service.id));

        return this.services.filter((service) => !picked.includes(Number(service.id)));
    },

    add() {
        const service = this.services.find((item) => Number(item.id) === Number(this.choice));

        if (!service) {
            return;
        }

        this.selected = [...this.selected, service];
        this.choice = '';
    },

    remove(id) {
        this.selected = this.selected.filter((service) => Number(service.id) !== Number(id));
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
