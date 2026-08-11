@extends('layouts.app')

@section('title', __('auth.sign_in') . ' ' . __('site.meta_title_separator') . ' ' . __('site.meta_title_suffix'))

@section('content')
<section class="workspace-shell workspace-section">
    <div class="grid min-h-[calc(100vh-11rem)] items-center gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="border-y-2 border-ink-900 py-10 text-ink-900 dark:border-white dark:text-white sm:py-14">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-600 dark:text-brand-300">{{ __('auth.vetora_access') }}</p>
            <h1 class="mt-5 max-w-2xl font-display text-4xl font-bold leading-tight sm:text-5xl">{{ __('auth.workspace_title') }}</h1>
            <p class="mt-5 max-w-xl text-base leading-8 text-ink-500 dark:text-slate-400">{{ __('auth.workspace_copy') }}</p>
            <dl class="mt-9 grid gap-5 border-t border-gray-200 pt-6 sm:grid-cols-3 dark:border-gray-800">
                <div><dt class="text-sm font-semibold">{{ __('auth.workspace_customers') }}</dt><dd class="mt-1 text-sm leading-6 text-ink-500 dark:text-slate-400">{{ __('auth.workspace_customers_copy') }}</dd></div>
                <div><dt class="text-sm font-semibold">{{ __('auth.workspace_vendors') }}</dt><dd class="mt-1 text-sm leading-6 text-ink-500 dark:text-slate-400">{{ __('auth.workspace_vendors_copy') }}</dd></div>
                <div><dt class="text-sm font-semibold">{{ __('auth.workspace_syndicates') }}</dt><dd class="mt-1 text-sm leading-6 text-ink-500 dark:text-slate-400">{{ __('auth.workspace_syndicates_copy') }}</dd></div>
            </dl>
        </div>

        <div class="auth-shell">
            <div class="relative">
                <span class="eyebrow">{{ __('auth.secure_sign_in') }}</span>
                <h2 class="mt-5 font-display text-3xl font-extrabold tracking-tight text-ink-900 dark:text-white">{{ __('auth.welcome_back') }}</h2>
                <p class="mt-2 text-sm leading-7 text-ink-500 dark:text-slate-400">{{ __('auth.sign_in_to_account') }}</p>
            </div>

            <div class="mt-8">
                <x-alert type="error" id="login-alert" />
                <x-alert type="success" id="login-success" />

                <form id="login-form" class="space-y-5" novalidate>
                    <x-form.input
                        name="phone_number"
                        label="{{ __('auth.phone_number') }}"
                        type="tel"
                        placeholder="{{ __('auth.placeholder_phone') }}"
                        :required="true"
                        autocomplete="tel"
                    />

                    <x-form.input
                        name="password"
                        label="{{ __('auth.password') }}"
                        type="password"
                        placeholder="{{ __('auth.placeholder_password') }}"
                        :required="true"
                        autocomplete="current-password"
                    />

                    <x-form.button type="submit" id="login-btn">
                        <span id="login-btn-text">{{ __('nav.sign_in') }}</span>
                        <svg id="login-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </x-form.button>
                </form>
            </div>

            <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-5 text-sm dark:border-gray-800">
                <p class="text-ink-500 dark:text-slate-400">{{ __('auth.dont_have_account') }}</p>
                <a href="{{ route('register') }}" class="font-bold text-brand-600 hover:text-brand-500">{{ __('auth.create_one') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const redirectByUser = function (user, fallbackUrl = '{{ url("/") }}') {
        const redirectUrl = user?.redirect_url || fallbackUrl;
        const type = Number(user?.type);

        if (type === 1) {
            window.location.href = '{{ route("admin.dashboard") }}';
            return;
        }

        if (type === 2) {
            window.location.href = '{{ route("vendor.dashboard") }}';
            return;
        }

        if (type === 3) {
            window.location.href = '{{ route("syndicate.dashboard") }}';
            return;
        }

        if (type === 4) {
            window.location.href = '{{ route("employee.dashboard") }}';
            return;
        }

        window.location.href = redirectUrl;
    };

    if (window.Auth && window.Auth.getToken && window.Auth.getToken()) {
        try {
            const res = await window.axios.get('/api/user');
            const user = res.data.data || res.data;
            if (user && typeof user === 'object') {
                window.Auth.setUser(user);
                redirectByUser(user, user.preferred_product_type ? '{{ url("/") }}' : '{{ route("product-type.select") }}');
                return;
            }
        } catch (e) {}
    }

    const form = document.getElementById('login-form');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();

        const btn = document.getElementById('login-btn');
        const spinner = document.getElementById('login-spinner');
        const btnText = document.getElementById('login-btn-text');

        btn.disabled = true;
        spinner.classList.remove('hidden');
        btnText.textContent = '{{ __("auth.signing_in") }}';

        try {
            const response = await window.axios.post('/api/auth/login', {
                phone_number: form.phone_number.value.trim(),
                password: form.password.value,
            });

            window.Auth.setToken(response.data.data.token);
            if (response.data.data.user) {
                window.Auth.setUser(response.data.data.user);
            }

            const redirectUrl = response.data.data.redirect_url;

            showAlert('login-success', @json(__('auth.signed_in_success')));

            setTimeout(() => {
                redirectByUser(response.data.data.user, redirectUrl || '{{ url("/") }}');
            }, 500);
        } catch (error) {
            handleErrors(error);
        } finally {
            btn.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = '{{ __("nav.sign_in") }}';
        }
    });

    function clearErrors() {
        document.getElementById('login-alert').classList.add('hidden');
        document.getElementById('login-success').classList.add('hidden');
        document.querySelectorAll('[id$="-error"]').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        const msg = document.getElementById(id + '-message');
        msg.textContent = message;
        box.classList.remove('hidden');
        box.classList.add('flex');
    }

    function handleErrors(error) {
        if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors;
            for (const [field, messages] of Object.entries(errors)) {
                const errorEl = document.getElementById(field + '-error');
                if (errorEl) {
                    errorEl.textContent = messages[0];
                    errorEl.classList.remove('hidden');
                }
            }
        } else {
            showAlert('login-alert', error.response?.data?.message || @json(__('auth.js_unexpected_error')));
        }
    }
});
</script>
@endpush
