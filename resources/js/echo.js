import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Only connect to Reverb when explicitly enabled (avoids errors when only "php artisan serve" is running).
const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED === 'true' || import.meta.env.VITE_REVERB_ENABLED === '1';
const key = import.meta.env.VITE_REVERB_APP_KEY;
const host = import.meta.env.VITE_REVERB_HOST;
const scheme = (import.meta.env.VITE_REVERB_SCHEME ?? 'http').toLowerCase();
const useTLS = scheme === 'https';
const port = Number(import.meta.env.VITE_REVERB_PORT) || (useTLS ? 443 : 80);

const hasReverb = reverbEnabled && key && host;

if (hasReverb) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: host,
            wsPort: port,
            wssPort: port,
            forceTLS: useTLS,
            enabledTransports: useTLS ? ['wss'] : ['ws'],
            authEndpoint: '/api/broadcasting/auth',
            auth: {
                headers: {
                    get Authorization() {
                        const t = typeof window !== 'undefined' && window.Auth?.getToken?.();
                        return t ? `Bearer ${t}` : '';
                    },
                    Accept: 'application/json',
                },
            },
        });
    } catch (e) {
        console.warn('Reverb Echo init failed:', e);
        window.Echo = null;
    }
} else {
    window.Echo = null;
}

let notificationChannelsSetup = false;

/**
 * Subscribe to notification channels for real-time updates. Call once when user is authenticated.
 * @param {number} userId - Current user id (for private channel).
 */
export function setupNotificationEcho(userId) {
    if (notificationChannelsSetup || !window.Echo) return;
    if (!userId) return;

    const refresh = () => {
        if (typeof window.updateNotificationBadge === 'function') window.updateNotificationBadge();
        if (typeof window.vendorNotificationBadge === 'function') window.vendorNotificationBadge();
        const listEl = document.getElementById('notification-list');
        const dd = document.getElementById('notification-dropdown');
        if (listEl && dd && !dd.classList.contains('hidden') && typeof window.loadNotificationDropdown === 'function') {
            window.loadNotificationDropdown();
        }
        const vendorList = document.getElementById('vendor-notif-list');
        const vendorDd = document.getElementById('vendor-notif-dropdown');
        if (vendorList && vendorDd && !vendorDd.classList.contains('hidden') && typeof window.loadVendorNotificationDropdown === 'function') {
            window.loadVendorNotificationDropdown();
        }
    };

    try {
        window.Echo.channel('notifications.public').listen('.AdminNotificationSent', refresh);
        window.Echo.private(`App.Models.User.${userId}`).listen('.AdminNotificationSent', refresh);
        notificationChannelsSetup = true;
    } catch (e) {
        console.warn('Notification Echo subscribe failed:', e);
    }
}

window.setupNotificationEcho = setupNotificationEcho;

function trySubscribe() {
    if (!window.Echo || notificationChannelsSetup) return;
    const user = typeof window.Auth !== 'undefined' && window.Auth.getUser ? window.Auth.getUser() : null;
    if (user && user.id) {
        setupNotificationEcho(user.id);
    }
}

// Subscribe when Echo is ready; delay so Auth/token are available for private channel auth.
if (window.Echo) {
    setTimeout(trySubscribe, 400);
    setTimeout(trySubscribe, 1500);
}
window.dispatchEvent(new CustomEvent('echo-ready'));
