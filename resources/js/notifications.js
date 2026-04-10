/**
 * Real-time admin notification listener (public + private).
 * Subscribes to WebSocket channels and shows a toast when a notification is received.
 */
function showNotificationToast(title, body) {
    const container = document.getElementById('sz-notification-toast-container') || (() => {
        const el = document.createElement('div');
        el.id = 'sz-notification-toast-container';
        el.className = 'fixed bottom-4 right-4 z-[100] flex max-w-sm flex-col gap-2';
        document.body.appendChild(el);
        return el;
    })();

    const toast = document.createElement('div');
    toast.setAttribute('role', 'alert');
    toast.className = 'rounded-lg border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800';
    toast.innerHTML = `
        <p class="font-semibold text-gray-900 dark:text-white">${escapeHtml(title)}</p>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">${escapeHtml(body)}</p>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 8000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function setupNotificationListeners() {
    if (!window.Echo || !import.meta.env.VITE_REVERB_APP_KEY) {
        return;
    }
    if (window._notificationListenersSetup) {
        return;
    }
    window._notificationListenersSetup = true;

    const handleEvent = (e) => {
        const title = e?.title ?? e?.data?.title;
        const body = e?.body ?? e?.data?.body;
        if (title && body) showNotificationToast(title, body);
    };

    window.Echo.channel('notifications.public').listen('.AdminNotificationSent', handleEvent);

    const user = typeof window.Auth !== 'undefined' && window.Auth.getUser && window.Auth.getUser();
    if (user && user.id) {
        window.Echo.private(`App.Models.User.${user.id}`).listen('.AdminNotificationSent', handleEvent);
    }
}

function runWhenEchoReady() {
    if (window.Echo) {
        setupNotificationListeners();
    } else {
        window.addEventListener('echo-ready', setupNotificationListeners, { once: true });
        setTimeout(setupNotificationListeners, 1500);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runWhenEchoReady);
} else {
    runWhenEchoReady();
}
