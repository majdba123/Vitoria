@extends('layouts.app')

@section('title', __('auth.create_account_title') . ' ' . __('site.meta_title_separator') . ' ' . __('site.meta_title_suffix'))

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
            <form id="register-form" class="space-y-5" enctype="multipart/form-data" novalidate data-creating="{{ __('auth.creating_account') }}" data-btn-text="{{ __('nav.register') }}" data-success-msg="{{ __('auth.account_created_redirect') }}">
                <div>
                    <label class="form-label">Account type <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:border-brand-300 hover:bg-brand-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            <input type="radio" name="account_type" value="user" class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500" checked>
                            Normal user
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:border-brand-300 hover:bg-brand-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            <input type="radio" name="account_type" value="vendor" class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500">
                            Merchant / Vendor
                        </label>
                    </div>
                    <p class="form-error" id="account_type-error"></p>
                </div>

                <x-form.input
                    name="name"
                    label="{{ __('auth.full_name') }}"
                    placeholder="{{ __('auth.placeholder_full_name') }}"
                    :required="true"
                    autocomplete="name"
                />

                <x-form.input
                    name="phone_number"
                    label="{{ __('auth.phone_number') }}"
                    type="tel"
                    placeholder="{{ __('auth.placeholder_phone') }}"
                    :required="true"
                    autocomplete="tel"
                />

                <x-form.input
                    name="national_id"
                    label="{{ __('auth.national_id') }}"
                    placeholder="{{ __('auth.placeholder_national_id') }}"
                    :required="true"
                />

                <x-form.input
                    name="age"
                    label="{{ __('auth.age') }}"
                    type="number"
                    placeholder="{{ __('auth.age_placeholder') }}"
                    :required="true"
                    min="1"
                    max="120"
                    inputmode="numeric"
                />

                <x-form.input
                    name="membership_number"
                    label="{{ __('auth.membership_number') }}"
                    placeholder="{{ __('auth.membership_number_placeholder') }}"
                    :required="true"
                    autocomplete="off"
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

                <div id="merchant-fields" class="hidden space-y-5 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Merchant details</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your store will be reviewed by an admin before vendor access is enabled.</p>
                    </div>

                    <x-form.input
                        name="store_name"
                        label="Store name"
                        placeholder="Store display name"
                    />

                    <div>
                        <label for="category_ids" class="form-label">Merchant categories <span class="text-red-500">*</span></label>
                        <select id="category_ids" name="category_ids[]" multiple size="5" class="form-input">
                        </select>
                        <p class="form-error" id="category_ids-error"></p>
                    </div>

                    <div>
                        <label for="address" class="form-label">Store address</label>
                        <input type="text" id="address" name="address" placeholder="Store address (optional)" class="form-input">
                        <p class="form-error" id="address-error"></p>
                    </div>

                    <div>
                        <label for="description" class="form-label">Store description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Briefly describe your store (optional)" class="form-textarea"></textarea>
                        <p class="form-error" id="description-error"></p>
                    </div>

                    <div>
                        <label for="commercial_register_file" class="form-label">Commercial registration document <span class="text-red-500">*</span></label>
                        <input type="file" id="commercial_register_file" name="commercial_register_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png" class="form-input">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, DOC, DOCX, JPG, JPEG, or PNG. Max 5 MB.</p>
                        <p class="form-error" id="commercial_register_file-error"></p>
                    </div>
                </div>

                <x-form.input
                    name="email"
                    label="{{ __('auth.email') }}"
                    type="email"
                    placeholder="{{ __('auth.placeholder_email') }}"
                    :required="true"
                    autocomplete="email"
                />

                <x-form.input
                    name="password"
                    label="{{ __('auth.password') }}"
                    type="password"
                    placeholder="{{ __('auth.placeholder_password_optional') }}"
                    autocomplete="new-password"
                />

                <x-form.input
                    name="password_confirmation"
                    label="{{ __('auth.confirm_password') }}"
                    type="password"
                    placeholder="{{ __('auth.placeholder_password_confirm') }}"
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
@php
    $registerScriptI18n = [
        'could_not_load_cities' => __('auth.js_could_not_load_cities'),
        'searching' => __('auth.js_searching'),
        'found_prefix' => __('auth.js_found_prefix'),
        'no_results' => __('auth.js_no_results_syria'),
        'search_failed' => __('auth.js_search_failed'),
        'unexpected' => __('auth.js_unexpected_error'),
        'account_created' => __('auth.js_account_created_success'),
        'creating_fallback' => __('auth.creating_account'),
        'register_fallback' => __('nav.register'),
    ];
@endphp
<script>
const registerI18n = @json($registerScriptI18n);
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('register-form');
    const merchantFields = document.getElementById('merchant-fields');
    const SYRIA_CENTER = [35.0, 38.5];
    const DEFAULT_ZOOM = 6;

    loadCities();
    loadMerchantCategories();
    initMap();
    syncAccountTypeFields();

    document.querySelectorAll('input[name="account_type"]').forEach(input => {
        input.addEventListener('change', syncAccountTypeFields);
    });

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
            document.getElementById('city_id-error').textContent = registerI18n.could_not_load_cities || '';
            document.getElementById('city_id-error').classList.remove('hidden');
        }
    }

    async function loadMerchantCategories() {
        try {
            const res = await window.axios.get('/api/categories');
            const categories = res.data.data || [];
            const sel = document.getElementById('category_ids');
            categories.forEach(function (category) {
                const opt = document.createElement('option');
                opt.value = category.id;
                opt.textContent = category.name;
                sel.appendChild(opt);
            });
        } catch (e) {
            const parsed = window.ApiErrors?.parse
                ? window.ApiErrors.parse(e)
                : { generalMessage: 'Could not load categories.' };
            document.getElementById('category_ids-error').textContent = parsed.generalMessage || 'Could not load categories.';
            document.getElementById('category_ids-error').classList.remove('hidden');
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
            statusEl.textContent = registerI18n.searching || '';
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
                        statusEl.textContent = (registerI18n.found_prefix || '') + ' ' + (results[0].display_name || '');
                        statusEl.classList.remove('text-red-500', 'text-gray-500');
                        statusEl.classList.add('text-emerald-600');
                    } else {
                        statusEl.textContent = registerI18n.no_results || '';
                        statusEl.classList.remove('text-emerald-600', 'text-gray-500');
                        statusEl.classList.add('text-red-500');
                    }
                }).catch(function () {
                    statusEl.textContent = registerI18n.search_failed || '';
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
        btnText.textContent = form.dataset.creating || registerI18n.creating_fallback || '';

        const accountType = form.querySelector('input[name="account_type"]:checked')?.value || 'user';
        const payload = new FormData();
        payload.append('account_type', accountType);
        payload.append('name', form.name.value.trim());
        payload.append('phone_number', form.phone_number.value.trim());
        payload.append('national_id', form.national_id.value.trim());
        payload.append('age', parseInt(form.age.value, 10) || '');
        payload.append('membership_number', form.membership_number.value.trim());
        payload.append('city_id', parseInt(form.city_id.value, 10) || '');
        payload.append('latitude', parseFloat(form.latitude.value) || '');
        payload.append('longitude', parseFloat(form.longitude.value) || '');
        payload.append('email', form.email.value.trim());
        if (form.password.value) {
            payload.append('password', form.password.value);
            payload.append('password_confirmation', form.password_confirmation.value);
        }
        if (accountType === 'vendor') {
            payload.append('store_name', form.store_name.value.trim());
            Array.from(document.getElementById('category_ids').selectedOptions).forEach(option => {
                payload.append('category_ids[]', parseInt(option.value, 10) || '');
            });
            const address = form.address.value.trim() || await reverseGeocodeAddress(form.latitude.value, form.longitude.value);
            if (address) {
                form.address.value = address;
            }
            payload.append('address', address);
            payload.append('description', form.description.value.trim());
            if (form.commercial_register_file.files[0]) {
                payload.append('commercial_register_file', form.commercial_register_file.files[0]);
            }
        }

        try {
            const response = await window.axios.post('/api/auth/register', payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            window.Auth.setToken(response.data.data.token);
            if (response.data.data.user) {
                window.Auth.setUser(response.data.data.user);
            }
            showAlert('register-success', registerI18n.account_created || '');

            setTimeout(() => {
                window.location.href = '{{ url("/") }}';
            }, 500);
        } catch (error) {
            handleErrors(error);
        } finally {
            btn.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = form.dataset.btnText || registerI18n.register_fallback || '';
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

    function syncAccountTypeFields() {
        const accountType = form.querySelector('input[name="account_type"]:checked')?.value || 'user';
        const isMerchant = accountType === 'vendor';
        const categorySelect = document.getElementById('category_ids');
        merchantFields.classList.toggle('hidden', !isMerchant);
        form.store_name.required = isMerchant;
        categorySelect.required = isMerchant;
        form.commercial_register_file.required = isMerchant;
        if (!isMerchant) {
            form.store_name.value = '';
            Array.from(categorySelect.options).forEach(option => option.selected = false);
            form.address.value = '';
            form.description.value = '';
            form.commercial_register_file.value = '';
        }
    }

    async function reverseGeocodeAddress(lat, lng) {
        if (!lat || !lng) {
            return '';
        }

        try {
            const response = await fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&zoom=18&addressdetails=1', {
                headers: { 'Accept': 'application/json', 'User-Agent': 'SyriaZone/1.0' }
            });
            const data = await response.json();

            return data.display_name || '';
        } catch (e) {
            return '';
        }
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        const msg = document.getElementById(id + '-message');
        msg.textContent = message;
        box.classList.remove('hidden');
    }

    function handleErrors(error) {
        const parsed = window.ApiErrors?.parse
            ? window.ApiErrors.parse(error)
            : { generalMessage: registerI18n.unexpected || '', fieldErrors: {} };

        window.ApiErrors?.showFieldErrors?.(parsed.fieldErrors, {
            commercial_register: ['commercial_register_file'],
            category_id: ['category_ids'],
            'category_ids.0': ['category_ids'],
        });

        showAlert('register-alert', parsed.generalMessage || registerI18n.unexpected || '');

        if (parsed.fieldErrors?.commercial_register_file || parsed.fieldErrors?.commercial_register) {
            form.commercial_register_file.value = '';
        }
    }
});
</script>
@endpush
