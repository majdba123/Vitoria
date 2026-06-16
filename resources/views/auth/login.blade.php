@extends('layouts.app')

@section('title', __('auth.sign_in') . ' ' . __('site.meta_title_separator') . ' ' . __('site.meta_title_suffix'))

@section('content')
<section class="workspace-shell workspace-section">
    <div class="grid min-h-[calc(100vh-11rem)] items-center gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="relative overflow-hidden rounded-[34px] border border-white/40 bg-gradient-to-br from-ink-900 via-ink-800 to-ink-700 p-8 text-white shadow-2xl shadow-ink-900/30 dark:border-white/10 sm:p-10">
            <div class="absolute inset-0 soft-grid opacity-20"></div>
            <div class="absolute -left-10 top-10 h-36 w-36 rounded-full bg-brand-400/25 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-44 w-44 rounded-full bg-blue-400/15 blur-3xl"></div>
            <div class="relative">
                <span class="eyebrow bg-white/10 text-brand-100">Vetora access</span>
                <h1 class="mt-6 font-display text-4xl font-extrabold leading-tight sm:text-5xl">A refined workspace for every buyer, seller, and partner.</h1>
                <p class="mt-5 max-w-xl text-base leading-8 text-white/72">Sign in to move from discovery into action with a smoother storefront, cleaner dashboards, and a single product language across the platform.</p>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-[24px] border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-white/45">Customers</p>
                        <p class="mt-2 text-sm text-white/75">Track favorites, orders, and saved preferences from one calm interface.</p>
                    </div>
                    <div class="rounded-[24px] border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-white/45">Vendors</p>
                        <p class="mt-2 text-sm text-white/75">Manage listings, pricing, and store operations with clarity.</p>
                    </div>
                    <div class="rounded-[24px] border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-white/45">Syndicates</p>
                        <p class="mt-2 text-sm text-white/75">Monitor network performance and category health in real time.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-shell">
            <div class="relative">
                <span class="eyebrow">Secure sign in</span>
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

            <div class="mt-8 flex items-center justify-between rounded-[24px] border border-gray-200/80 bg-gray-50/70 px-5 py-4 text-sm dark:border-gray-800 dark:bg-gray-900/50">
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
    if (window.Auth && window.Auth.getToken && window.Auth.getToken()) {
        try {
            const res = await window.axios.get('/api/user');
            const user = res.data.data || res.data;
            if (user && typeof user === 'object') {
                window.Auth.setUser(user);
                const t = user.type;
                if (t === 1) {
                    window.location.href = '{{ url("/admin/dashboard") }}';
                } else if (t === 2) {
                    window.location.href = '{{ route("vendor.dashboard") }}';
                } else if (t === 3) {
                    window.location.href = '{{ route("syndicate.dashboard") }}';
                } else if (!user.preferred_product_type) {
                    window.location.href = '{{ route("product-type.select") }}';
                } else {
                    window.location.href = '{{ url("/") }}';
                }

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

            const userType = response.data.data.user?.type;
            const redirectUrl = response.data.data.redirect_url;

            showAlert('login-success', @json(__('auth.signed_in_success')));

            setTimeout(() => {
                if (userType === 1) {
                    window.location.href = '{{ url("/admin/dashboard") }}';
                } else if (userType === 2) {
                    window.location.href = '{{ route("vendor.dashboard") }}';
                } else if (userType === 3) {
                    window.location.href = '{{ route("syndicate.dashboard") }}';
                } else if (redirectUrl) {
                    window.location.href = redirectUrl;
                } else {
                    window.location.href = '{{ url("/") }}';
                }
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
