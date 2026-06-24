@extends('layouts.admin')

@section('title', __('admin.vendors_create') . ' — ' . __('Vetora') . ' ' . __('admin.dashboard'))
@section('page-title', __('admin.vendors_create'))

@section('content')
<div class="mx-auto max-w-2xl">
    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.vendors.index') }}" class="hover:text-gray-700">{{ __('admin.vendors_breadcrumb') }}</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">{{ __('admin.create') }}</span>
    </nav>

    <div class="card">
        <div class="card-body border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">{{ __('admin.vendors_create_new') }}</h2>
            <p class="mt-0.5 text-sm text-gray-500">{{ __('admin.vendors_create_desc') }}</p>
        </div>

        <div class="card-body">
            <x-alert type="error" id="create-alert" />
            <x-alert type="success" id="create-success" />

            <form id="create-vendor-form" class="mt-1 space-y-6" novalidate>
                {{-- User Account --}}
                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900">{{ __('admin.vendors_user_account') }}</legend>
                    <p class="mb-4 text-xs text-gray-500">{{ __('admin.vendors_credentials') }}</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="name" label="Full Name" placeholder="Vendor's full name" :required="true" />
                        <x-form.input name="phone_number" label="Phone Number" type="tel" placeholder="09XXXXXXXX" :required="true" />
                        <x-form.input name="national_id" label="National ID" placeholder="National ID number" :required="true" />
                        <x-form.input name="email" label="Email" type="email" placeholder="vendor@example.com (optional)" />
                        <div class="sm:col-span-2">
                            <x-form.input name="password" label="Password" type="password" placeholder="Min 6 characters" :required="true" />
                        </div>
                    </div>
                </fieldset>

                <div class="border-t border-gray-100"></div>

                {{-- Store Details --}}
                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900 dark:text-white">Store Details</legend>
                    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Information about the vendor's store.</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="store_name" label="Store Name" placeholder="Store display name" :required="true" />
                        <x-form.input name="address" label="Address" placeholder="Store address (optional)" />
                        <div class="sm:col-span-2">
                            <label for="business_type" class="form-label">Business Type <span class="text-red-500">*</span></label>
                            <select id="business_type" name="business_type" required class="form-select">
                                <option value="">Select business type</option>
                                <option value="agriculture">Agriculture</option>
                                <option value="veterinary">Veterinary</option>
                                <option value="both">Both</option>
                            </select>
                            <p class="form-error" id="business_type-error"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="city_id" class="form-label">City <span class="text-red-500">*</span></label>
                            <select id="city_id" name="city_id" required class="form-input">
                                <option value="">Select store city</option>
                            </select>
                            <p class="form-error" id="city_id-error"></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="description" class="form-label">{{ __('admin.vendors_description') }}</label>
                        <textarea id="description" name="description" rows="3" placeholder="Brief store description (optional)" class="form-textarea"></textarea>
                        <p class="form-error" id="description-error"></p>
                    </div>
                </fieldset>

                <div class="border-t border-gray-100"></div>

                {{-- Allowed Categories --}}
                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900">Allowed Categories</legend>
                    <p class="mb-4 text-xs text-gray-500">Select which categories this vendor is allowed to sell products in.</p>
                    <div id="categories-loading" class="py-4 text-center text-sm text-gray-400">Loading categories...</div>
                    <div id="categories-checkboxes" class="hidden grid grid-cols-1 gap-3 sm:grid-cols-2"></div>
                    <p class="form-error" id="category_ids-error"></p>
                </fieldset>

                {{-- Actions --}}
                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.vendors.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
                    <button type="submit" id="create-btn" class="btn-primary">
                        <span id="create-btn-text">{{ __('admin.vendors_create_btn') }}</span>
                        <svg id="create-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('create-vendor-form');
    let allCategories = [];

    loadCities();

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

    loadAllCategories();
    document.getElementById('business_type').addEventListener('change', renderCategories);

    async function loadAllCategories() {
        try {
            const res = await window.axios.get('/api/admin/categories');
            allCategories = res.data.data || [];
            renderCategories();
            document.getElementById('categories-loading').classList.add('hidden');
            document.getElementById('categories-checkboxes').classList.remove('hidden');
        } catch (e) {
            document.getElementById('categories-loading').textContent = 'Failed to load categories.';
        }
    }

    function renderCategories() {
        const businessType = document.getElementById('business_type').value;
        const container = document.getElementById('categories-checkboxes');

        if (!businessType) {
            container.innerHTML = '<p class="rounded-lg border border-dashed border-gray-300 px-3 py-3 text-sm text-gray-500 sm:col-span-2">Select a business type first.</p>';
            return;
        }

        if (businessType === 'both') {
            container.innerHTML = renderCategoryGroup('Agriculture Categories', allCategories.filter(cat => cat.type === 'agriculture'))
                + renderCategoryGroup('Veterinary Categories', allCategories.filter(cat => cat.type === 'veterinary'));
            return;
        }

        container.innerHTML = allCategories.filter(cat => cat.type === businessType).map(categoryCheckbox).join('');
    }

    function renderCategoryGroup(title, categories) {
        return `
            <div class="sm:col-span-2">
                <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">${title}</h4>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">${categories.map(categoryCheckbox).join('')}</div>
            </div>
        `;
    }

    function categoryCheckbox(cat) {
        return `
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-brand-300 hover:bg-brand-50/50 transition-colors">
                    <input type="checkbox" name="category_ids[]" value="${cat.id}" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 category-checkbox">
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-900">${esc(cat.name)}</span>
                        <span class="ml-2 text-xs font-semibold ${cat.type === 'veterinary' ? 'text-blue-600' : 'text-emerald-600'}">${cat.type === 'veterinary' ? 'Veterinary' : 'Agriculture'}</span>
                    </div>
                </label>
            `;
    }

    function esc(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);

        const selectedCategories = [...document.querySelectorAll('.category-checkbox:checked')].map(cb => parseInt(cb.value));

        const payload = {
            name: form.name.value.trim(),
            phone_number: form.phone_number.value.trim(),
            national_id: form.national_id.value.trim(),
            password: form.password.value,
            store_name: form.store_name.value.trim(),
            business_type: form.business_type.value,
            city_id: parseInt(form.city_id.value, 10) || null,
            category_ids: selectedCategories,
        };
        if (form.email.value.trim()) payload.email = form.email.value.trim();
        if (form.address.value.trim()) payload.address = form.address.value.trim();
        if (form.description.value.trim()) payload.description = form.description.value.trim();

        try {
            await window.axios.post('/api/admin/vendors', payload);
            showAlert('create-success', 'Vendor created successfully! Redirecting...');
            setTimeout(() => { window.location.href = '{{ route("admin.vendors.index") }}'; }, 800);
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(loading) {
        document.getElementById('create-btn').disabled = loading;
        document.getElementById('create-spinner').classList.toggle('hidden', !loading);
        document.getElementById('create-btn-text').textContent = loading ? 'Creating...' : 'Create Vendor';
    }

    function clearErrors() {
        document.getElementById('create-alert').classList.add('hidden');
        document.getElementById('create-success').classList.add('hidden');
        document.querySelectorAll('[id$="-error"]').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        document.getElementById(id + '-message').textContent = message;
        box.classList.remove('hidden');
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            for (const [field, messages] of Object.entries(error.response.data.errors)) {
                const el = document.getElementById(field + '-error');
                if (el) { el.textContent = messages[0]; el.classList.remove('hidden'); }
            }
        } else {
            showAlert('create-alert', error.response?.data?.message || 'An unexpected error occurred.');
        }
    }
});
</script>
@endpush
