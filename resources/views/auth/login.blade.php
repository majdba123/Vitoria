@extends('layouts.app')

@section('title', __('auth.sign_in') . ' — SyriaZone')

@section('content')
<div class="flex min-h-[calc(100vh-8rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        {{-- Card --}}
        <div class="rounded-xl bg-white p-8 shadow-xl ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-gray-800">
            {{-- Header --}}
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('auth.welcome_back') }}</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('auth.sign_in_to_account') }}</p>
            </div>

            {{-- Alert --}}
            <x-alert type="error" id="login-alert" />
            <x-alert type="success" id="login-success" />

            {{-- Form --}}
            <form id="login-form" class="space-y-5" novalidate>
                <x-form.input
                    name="phone_number"
                    label="{{ __('auth.phone_number') }}"
                    type="tel"
                    placeholder="09XXXXXXXX"
                    :required="true"
                    autocomplete="tel"
                />

                <x-form.input
                    name="password"
                    label="{{ __('auth.password') }}"
                    type="password"
                    placeholder="Enter your password"
                    :required="true"
                    autocomplete="current-password"
                />

                <x-form.button type="submit" id="login-btn">
                    <span id="login-btn-text">{{ __('nav.sign_in') }}</span>
                    <svg id="login-spinner" class="ml-2 hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </x-form.button>
            </form>

            {{-- Footer --}}
            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('auth.dont_have_account') }}
                <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-500">{{ __('auth.create_one') }}</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Clear any remaining auth data when landing on login page
    try {
        if (window.Auth) {
            if (window.Auth.clearAll) {
                window.Auth.clearAll();
            } else if (window.Auth.removeToken) {
                window.Auth.removeToken();
            }
        }
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        delete window.axios.defaults.headers.common['Authorization'];
    } catch (e) {
        console.error('Error clearing auth data:', e);
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

            showAlert('login-success', 'Signed in successfully! Redirecting...');

            setTimeout(() => {
                if (userType === 1) {
                    window.location.href = '{{ url("/admin/dashboard") }}';
                } else if (userType === 2) {
                    window.location.href = '{{ route("vendor.dashboard") }}';
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
            showAlert('login-alert', error.response?.data?.message || 'An unexpected error occurred.');
        }
    }
});
</script>
@endpush

