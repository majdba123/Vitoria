import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

/**
 * Backend error parsing utilities.
 */
window.ApiErrors = {
    fallbackMessage: 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.',

    parse(error) {
        const responseData = error?.response?.data ?? error?.data ?? error;
        const fieldErrors = {};
        let generalMessage = '';

        const collectFieldErrors = (errors) => {
            if (!errors || typeof errors !== 'object' || Array.isArray(errors)) {
                return;
            }

            Object.entries(errors).forEach(([field, messages]) => {
                const message = this.firstMessage(messages);
                if (message) {
                    fieldErrors[field] = message;
                }
            });
        };

        collectFieldErrors(responseData?.errors);
        collectFieldErrors(responseData?.validationErrors);

        if (!Object.keys(fieldErrors).length && responseData && typeof responseData === 'object') {
            Object.entries(responseData).forEach(([field, value]) => {
                if (['message', 'error', 'exception', 'trace', 'file', 'line'].includes(field)) {
                    return;
                }

                if (Array.isArray(value)) {
                    const message = this.firstMessage(value);
                    if (message) {
                        fieldErrors[field] = message;
                    }
                }
            });
        }

        generalMessage =
            this.cleanMessage(responseData?.message) ||
            this.cleanMessage(responseData?.error) ||
            this.cleanMessage(error?.message) ||
            '';

        if (generalMessage && /^(request failed with status code|network error)$/i.test(generalMessage)) {
            generalMessage = '';
        }

        if (!generalMessage && Object.keys(fieldErrors).length) {
            generalMessage = Object.values(fieldErrors)[0];
        }

        return {
            generalMessage: generalMessage || this.fallbackMessage,
            fieldErrors,
        };
    },

    firstMessage(value) {
        if (Array.isArray(value)) {
            return this.cleanMessage(value[0]);
        }

        if (typeof value === 'object' && value !== null) {
            return this.cleanMessage(Object.values(value).flat()[0]);
        }

        return this.cleanMessage(value);
    },

    cleanMessage(value) {
        if (typeof value !== 'string') {
            return '';
        }

        const message = value.trim();
        if (!message || message.includes('\n#') || message.includes('Stack trace') || /<[^>]+>/.test(message)) {
            return '';
        }

        return message;
    },

    showFieldErrors(fieldErrors, aliases = {}) {
        Object.entries(fieldErrors || {}).forEach(([field, message]) => {
            const candidateFields = [field, field.replace(/\./g, '_'), ...(aliases[field] || [])];
            const errorElement = candidateFields
                .map((name) => document.getElementById(name + '-error'))
                .find(Boolean);

            if (errorElement) {
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
            }
        });
    },
};

window.AppToast = {
    show(message, type = 'error') {
        const safeMessage = window.ApiErrors?.cleanMessage?.(message) || window.ApiErrors?.fallbackMessage || 'حدث خطأ غير متوقع.';
        const toast = document.createElement('div');
        const palette = {
            success: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
            error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200',
            warning: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
            info: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200',
        };

        toast.className = `fixed right-4 top-4 z-[120] max-w-sm rounded-xl border px-4 py-3 text-sm font-semibold shadow-xl backdrop-blur ${palette[type] || palette.error}`;
        toast.setAttribute('role', 'alert');
        toast.textContent = safeMessage;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-6px)';
            toast.style.transition = 'opacity .25s ease, transform .25s ease';
            setTimeout(() => toast.remove(), 260);
        }, 4200);
    },
};

window.showApiError = function (error, fallback = null) {
    const parsed = window.ApiErrors.parse(error);
    window.AppToast.show(parsed.generalMessage || fallback || window.ApiErrors.fallbackMessage, 'error');

    return parsed;
};

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
        return !!this.getToken() || document.body?.dataset?.sessionAuth === '1';
    },

    clearAll() {
        this.removeToken();
        // Clear any other auth-related data
        localStorage.removeItem('auth_user');
        sessionStorage.clear();
    },

    /**
     * Bearer token rejected by API (expired or revoked). Clears client state and redirects shoppers to login.
     */
    handleSessionExpired() {
        if (!localStorage.getItem('auth_token')) {
            return;
        }
        this.clearAll();
        window.dispatchEvent(new CustomEvent('sz:auth:expired'));
        const path = window.location.pathname || '';
        if (!/^\/(login|register)(\/)?$/i.test(path)) {
            window.location.href = '/login';
        }
    },
};

// Apply token on page load if it exists
window.Auth.applyToken();

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const url = String(error.config?.url || '');

        const isAuthAttempt =
            url.includes('/api/auth/login') ||
            url.includes('/api/auth/register');

        if (status !== 401) {
            if (!error.config?.silent && !isAuthAttempt) {
                window.showApiError(error);
            }

            return Promise.reject(error);
        }

        if (isAuthAttempt) {
            return Promise.reject(error);
        }

        if (window.Auth?.getToken?.()) {
            window.Auth.handleSessionExpired();
        }

        return Promise.reject(error);
    },
);

window.addEventListener('sz:auth:expired', () => {
    if (typeof window.updateNavbar === 'function') {
        window.updateNavbar();
    }
});

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
