@extends('layouts.app')

@section('title', __('auth.create_account_title') . ' — SyriaZone')

@section('content')
<div class="flex min-h-[calc(100vh-8rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        {{-- Card --}}
        <div class="rounded-xl bg-white p-8 shadow-xl ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-gray-800">
            {{-- Header --}}
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('auth.create_account_title') }}</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('auth.join_today') }}</p>
            </div>

            {{-- Alert --}}
            <x-alert type="error" id="register-alert" />
            <x-alert type="success" id="register-success" />

            {{-- Form --}}
            <form id="register-form" class="space-y-5" novalidate data-creating="{{ __('auth.creating_account') }}" data-btn-text="{{ __('nav.register') }}" data-success-msg="{{ __('auth.account_created_redirect') }}">
                <x-form.input
                    name="name"
                    label="{{ __('auth.full_name') }}"
                    placeholder="Enter your full name"
                    :required="true"
                    autocomplete="name"
                />

                <x-form.input
                    name="phone_number"
                    label="{{ __('auth.phone_number') }}"
                    type="tel"
                    placeholder="09XXXXXXXX"
                    :required="true"
                    autocomplete="tel"
                />

                <x-form.input
                    name="national_id"
                    label="{{ __('auth.national_id') }}"
                    placeholder="Enter your national ID"
                    :required="true"
                />

                <div>
                    <label for="city_id" class="form-label">{{ __('auth.city') }} <span class="text-red-500">*</span></label>
                    <select id="city_id" name="city_id" required class="form-input">
                        <option value="">{{ __('auth.select_city') }}</option>
                    </select>
                    <p class="form-error" id="city_id-error"></p>
                </div>

                <div>
                    <label class="form-label">{{ __('auth.location_on_map') }} <span class="text-red-500">*</span></label>
                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ __('auth.search_location_placeholder') }}</p>
                    <div class="mb-2 flex gap-2">
                        <input type="text" id="location-search" placeholder="{{ __('auth.search_location_placeholder') }}" class="form-input flex-1" autocomplete="off">
                        <button type="button" id="location-search-btn" class="btn-primary btn-sm shrink-0">{{ __('auth.search') }}</button>
                    </div>
                    <p id="location-search-status" class="mb-2 hidden text-xs"></p>
                    <div id="register-map" class="h-56 w-full rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800"></div>
                    <input type="hidden" id="latitude" name="latitude" value="">
                    <input type="hidden" id="longitude" name="longitude" value="">
                    <p class="form-error" id="latitude-error"></p>
                    <p class="form-error" id="longitude-error"></p>
                </div>

                <x-form.input
                    name="email"
                    label="{{ __('auth.email') }}"
                    type="email"
                    placeholder="you@example.com (optional)"
                    autocomplete="email"
                />

                <x-form.input
                    name="password"
                    label="{{ __('auth.password') }}"
                    type="password"
                    placeholder="Min 6 characters (optional)"
                    autocomplete="new-password"
                />

                <x-form.input
                    name="password_confirmation"
                    label="{{ __('auth.confirm_password') }}"
                    type="password"
                    placeholder="Repeat your password"
                    autocomplete="new-password"
                />

                <x-form.button type="submit" id="register-btn">
                    <span id="register-btn-text">{{ __('nav.register') }}</span>
                    <svg id="register-spinner" class="ml-2 hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </x-form.button>
            </form>

            {{-- Footer --}}
            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('auth.has_account') }}
                <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-500">{{ __('nav.sign_in') }}</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('register-form');
    const SYRIA_CENTER = [35.0, 38.5];
    const DEFAULT_ZOOM = 6;

    loadCities();
    initMap();

    async function loadCities() {
        try {
            const res = await window.axios.get('/api/cities');
            const cities = res.data.data || [];
            const sel = document.getElementById('city_id');
            cities.forEach(function (c) {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                sel.appendChild(opt);
            });
        } catch (e) {
            document.getElementById('city_id-error').textContent = 'Could not load cities.';
            document.getElementById('city_id-error').classList.remove('hidden');
        }
    }

    function initMap() {
        const mapEl = document.getElementById('register-map');
        if (!mapEl || typeof L === 'undefined') return;
        const map = L.map('register-map').setView(SYRIA_CENTER, DEFAULT_ZOOM);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        const marker = L.marker(SYRIA_CENTER, { draggable: true }).addTo(map);
        document.getElementById('latitude').value = SYRIA_CENTER[0];
        document.getElementById('longitude').value = SYRIA_CENTER[1];

        function updateFromLatLng(lat, lng) {
            marker.setLatLng([lat, lng]);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        }
        map.on('click', function (e) {
            updateFromLatLng(e.latlng.lat, e.latlng.lng);
        });
        marker.on('dragend', function () {
            const latlng = marker.getLatLng();
            document.getElementById('latitude').value = latlng.lat;
            document.getElementById('longitude').value = latlng.lng;
        });

        var lastSearchAt = 0;
        document.getElementById('location-search-btn').addEventListener('click', doSearch);
        document.getElementById('location-search').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
        });
        function doSearch() {
            var q = document.getElementById('location-search').value.trim();
            if (!q) return;
            var statusEl = document.getElementById('location-search-status');
            statusEl.textContent = 'Searching...';
            statusEl.classList.remove('hidden', 'text-red-500', 'text-emerald-600');
            statusEl.classList.add('text-gray-500');
            var now = Date.now();
            var wait = Math.max(0, 1000 - (now - lastSearchAt));
            setTimeout(function () {
                lastSearchAt = Date.now();
                fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(q) + '&format=json&limit=1&countrycodes=sy', {
                    headers: { 'Accept': 'application/json', 'User-Agent': 'SyriaZone/1.0' }
                }).then(function (r) { return r.json(); }).then(function (results) {
                    if (results && results[0]) {
                        var lat = parseFloat(results[0].lat);
                        var lon = parseFloat(results[0].lon);
                        map.setView([lat, lon], 17);
                        updateFromLatLng(lat, lon);
                        statusEl.textContent = 'Found: ' + (results[0].display_name || '');
                        statusEl.classList.remove('text-red-500', 'text-gray-500');
                        statusEl.classList.add('text-emerald-600');
                    } else {
                        statusEl.textContent = 'No results found in Syria. Try another search or click on the map.';
                        statusEl.classList.remove('text-emerald-600', 'text-gray-500');
                        statusEl.classList.add('text-red-500');
                    }
                }).catch(function () {
                    statusEl.textContent = 'Search failed. Try again or set location on the map.';
                    statusEl.classList.remove('text-emerald-600', 'text-gray-500');
                    statusEl.classList.add('text-red-500');
                });
            }, wait);
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();

        const btn = document.getElementById('register-btn');
        const spinner = document.getElementById('register-spinner');
        const btnText = document.getElementById('register-btn-text');

        btn.disabled = true;
        spinner.classList.remove('hidden');
        btnText.textContent = form.dataset.creating || 'Creating account...';

        const payload = {
            name: form.name.value.trim(),
            phone_number: form.phone_number.value.trim(),
            national_id: form.national_id.value.trim(),
            city_id: parseInt(form.city_id.value, 10) || null,
            latitude: parseFloat(form.latitude.value) || null,
            longitude: parseFloat(form.longitude.value) || null,
        };

        if (form.email.value.trim()) {
            payload.email = form.email.value.trim();
        }
        if (form.password.value) {
            payload.password = form.password.value;
            payload.password_confirmation = form.password_confirmation.value;
        }

        try {
            const response = await window.axios.post('/api/auth/register', payload);

            window.Auth.setToken(response.data.data.token);
            if (response.data.data.user) {
                window.Auth.setUser(response.data.data.user);
            }
            showAlert('register-success', 'Account created successfully! Redirecting...');

            setTimeout(() => {
                window.location.href = '{{ url("/") }}';
            }, 500);
        } catch (error) {
            handleErrors(error);
        } finally {
            btn.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = form.dataset.btnText || 'Create Account';
        }
    });

    function clearErrors() {
        document.getElementById('register-alert').classList.add('hidden');
        document.getElementById('register-success').classList.add('hidden');
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
            showAlert('register-alert', error.response?.data?.message || 'An unexpected error occurred.');
        }
    }
});
</script>
@endpush

