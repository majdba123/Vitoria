import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

/**
 * Token management utilities.
 */
window.Auth = {
    getToken() {
        return localStorage.getItem('auth_token');
    },

    setToken(token) {
        localStorage.setItem('auth_token', token);
        this.applyToken();
    },

    removeToken() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        delete window.axios.defaults.headers.common['Authorization'];
    },

    setUser(user) {
        localStorage.setItem('auth_user', JSON.stringify(user));
    },

    getUser() {
        const user = localStorage.getItem('auth_user');
        return user ? JSON.parse(user) : null;
    },

    applyToken() {
        const token = this.getToken();
        if (token) {
            window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }
    },

    isAuthenticated() {
        return !!this.getToken();
    },

    clearAll() {
        this.removeToken();
        // Clear any other auth-related data
        localStorage.removeItem('auth_user');
        sessionStorage.clear();
    },
};

// Apply token on page load if it exists
window.Auth.applyToken();

/**
 * Echo / Reverb: only load when explicitly enabled so no WebSocket runs with just "php artisan serve".
 */
const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED === 'true' || import.meta.env.VITE_REVERB_ENABLED === '1';
if (reverbEnabled) {
    import('./echo');
} else {
    window.Echo = null;
    window.setupNotificationEcho = function () {};
}
