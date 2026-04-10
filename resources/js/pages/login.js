/**
 * Login page module.
 * Handles form submission via Axios → API, token storage, and error display.
 */
(function () {
    'use strict';

    const form = document.getElementById('login-form');
    if (!form) return;

    const btn       = document.getElementById('login-btn');
    const btnText   = document.getElementById('login-btn-text');
    const spinner   = document.getElementById('login-spinner');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();
        setLoading(true);

        try {
            const res = await window.axios.post('/api/auth/login', {
                phone_number: form.phone_number.value.trim(),
                national_id:  form.national_id.value.trim(),
                password:     form.password.value || undefined,
            });

            window.Auth.setToken(res.data.data.token);
            window.Auth.setUser(res.data.data.user);
            showAlert('login-success', 'Signed in successfully! Redirecting…');

            // Redirect based on user type
            const user = res.data.data.user;
            let redirectUrl = '/';
            if (user.type === 1) { // Admin
                redirectUrl = '/admin/dashboard';
            } else if (user.type === 2) { // Vendor
                redirectUrl = '/vendor/dashboard';
            } else { // User/Client
                redirectUrl = '/';
            }

            setTimeout(() => { window.location.href = redirectUrl; }, 600);
        } catch (err) {
            handleValidationErrors(err, 'login-alert');
        } finally {
            setLoading(false);
        }
    });

    /* ─── helpers ──────────────────────────────────── */

    function setLoading(on) {
        btn.disabled = on;
        spinner.classList.toggle('hidden', !on);
        btnText.textContent = on ? 'Signing in…' : 'Sign In';
    }

    function clearErrors() {
        document.getElementById('login-alert')?.classList.add('hidden');
        document.getElementById('login-success')?.classList.add('hidden');
        document.querySelectorAll('[id$="-error"]').forEach((el) => {
            el.classList.add('hidden');
            el.textContent = '';
        });
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        const msg = document.getElementById(id + '-message');
        if (box && msg) {
            msg.textContent = message;
            box.classList.remove('hidden');
        }
    }

    function handleValidationErrors(error, alertId) {
        if (error.response?.status === 422 && error.response.data.errors) {
            const errors = error.response.data.errors;
            for (const [field, messages] of Object.entries(errors)) {
                const el = document.getElementById(field + '-error');
                if (el) {
                    el.textContent = messages[0];
                    el.classList.remove('hidden');
                }
            }
        } else {
            showAlert(alertId, error.response?.data?.message || 'An unexpected error occurred.');
        }
    }
})();

