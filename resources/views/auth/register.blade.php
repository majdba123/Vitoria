@extends('layouts.app')

@section('title', __('auth.create_account_title') . ' ' . __('site.meta_title_separator') . ' ' . __('site.meta_title_suffix'))

@section('content')
<div class="bg-gray-50 px-4 py-10 dark:bg-gray-950 sm:px-6 lg:px-8">
    <div class="mx-auto grid min-h-[calc(100vh-8rem)] max-w-6xl items-center gap-8 lg:grid-cols-[0.8fr_1.2fr]">
        <aside class="hidden lg:block">
            <div class="max-w-md">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72L4.318 3.44A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72"/>
                    </svg>
                </div>
                <h1 class="mt-6 text-4xl font-black leading-tight text-gray-950 dark:text-white">{{ __('auth.create_account_title') }}</h1>
                <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-400">{{ __('auth.join_today') }}</p>
                <div class="mt-8 grid gap-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Customer account</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Shop products and manage orders from one place.</p>
                    </div>
                    <div class="rounded-lg border border-brand-200 bg-brand-50 p-4 shadow-sm dark:border-brand-500/20 dark:bg-brand-500/10">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Merchant account</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Create a vendor profile with every category your store serves.</p>
                    </div>
                </div>
            </div>
        </aside>

        <section class="rounded-lg border border-gray-200 bg-white shadow-xl shadow-gray-200/60 dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20">
            <div class="border-b border-gray-100 px-5 py-5 dark:border-gray-800 sm:px-8">
                <h1 class="text-2xl font-bold text-gray-950 dark:text-white lg:hidden">{{ __('auth.create_account_title') }}</h1>
                <div class="hidden items-center justify-between gap-4 lg:flex">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">SyriaZone</p>
                        <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">Account details</h2>
                    </div>
                    <a href="{{ route('login') }}" class="btn-secondary btn-sm">{{ __('nav.sign_in') }}</a>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 lg:hidden">{{ __('auth.join_today') }}</p>
            </div>

            <div class="p-5 sm:p-8">
                <x-alert type="error" id="register-alert" />
                <x-alert type="success" id="register-success" />

                <form id="register-form" class="space-y-6" enctype="multipart/form-data" novalidate data-creating="{{ __('auth.creating_account') }}" data-btn-text="{{ __('nav.register') }}" data-success-msg="{{ __('auth.account_created_redirect') }}">
                    <div>
                        <label class="form-label">Account type <span class="text-red-500">*</span></label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="account-type-option relative flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 transition-all hover:border-brand-300 hover:bg-brand-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                                <input type="radio" name="account_type" value="user" class="mt-1 h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500" checked>
                                <span>
                                    <span class="block text-sm font-bold text-gray-900 dark:text-white">Normal user</span>
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Buy and track orders</span>
                                </span>
                            </label>
                            <label class="account-type-option relative flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 transition-all hover:border-brand-300 hover:bg-brand-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                                <input type="radio" name="account_type" value="vendor" class="mt-1 h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span>
                                    <span class="block text-sm font-bold text-gray-900 dark:text-white">Merchant / Vendor</span>
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Sell across multiple categories</span>
                                </span>
                            </label>
                        </div>
                        <p class="form-error" id="account_type-error"></p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-form.input name="name" label="{{ __('auth.full_name') }}" placeholder="{{ __('auth.placeholder_full_name') }}" :required="true" autocomplete="name" />
                        <x-form.input name="phone_number" label="{{ __('auth.phone_number') }}" type="tel" placeholder="{{ __('auth.placeholder_phone') }}" :required="true" autocomplete="tel" />
                        <x-form.input name="national_id" label="{{ __('auth.national_id') }}" placeholder="{{ __('auth.placeholder_national_id') }}" :required="true" />
                        <x-form.input name="age" label="{{ __('auth.age') }}" type="number" placeholder="{{ __('auth.age_placeholder') }}" :required="true" min="1" max="120" inputmode="numeric" />
                        <x-form.input name="membership_number" label="{{ __('auth.membership_number') }}" placeholder="{{ __('auth.membership_number_placeholder') }}" :required="true" autocomplete="off" />

                        <div>
                            <label for="city_id" class="form-label">{{ __('auth.city') }} <span class="text-red-500">*</span></label>
                            <select id="city_id" name="city_id" required class="form-select">
                                <option value="">{{ __('auth.select_city') }}</option>
                            </select>
                            <p class="form-error" id="city_id-error"></p>
                        </div>
                    </div>

                    <div id="merchant-fields" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Merchant details</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Admin approval is required before vendor access is enabled.</p>
                            </div>
                            <span class="badge badge-warning shrink-0">Review</span>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-form.input name="store_name" label="Store name" placeholder="Store display name" />

                            <div>
                                <label for="business_type" class="form-label">Business type <span class="text-red-500">*</span></label>
                                <select id="business_type" name="business_type" class="form-select">
                                    <option value="">Select business type</option>
                                    <option value="agriculture">Agriculture</option>
                                    <option value="veterinary">Veterinary</option>
                                    <option value="both">Both</option>
                                </select>
                                <p class="form-error" id="business_type-error"></p>
                            </div>

                            <div>
                                <label for="commercial_register_file" class="form-label">Commercial registration document <span class="text-red-500">*</span></label>
                                <input type="file" id="commercial_register_file" name="commercial_register_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png" class="form-input">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, DOC, DOCX, JPG, JPEG, or PNG. Max 5 MB.</p>
                                <p class="form-error" id="commercial_register_file-error"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="form-label">Merchant categories <span class="text-red-500">*</span></label>
                                <div id="category-options" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"></div>
                                <p id="category-loading" class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-3 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">Loading categories...</p>
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
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-form.input name="email" label="{{ __('auth.email') }}" type="email" placeholder="{{ __('auth.placeholder_email') }}" :required="true" autocomplete="email" />
                        <x-form.input name="password" label="{{ __('auth.password') }}" type="password" placeholder="{{ __('auth.placeholder_password_optional') }}" autocomplete="new-password" />
                        <x-form.input name="password_confirmation" label="{{ __('auth.confirm_password') }}" type="password" placeholder="{{ __('auth.placeholder_password_confirm') }}" autocomplete="new-password" />
                    </div>

                    <div class="flex flex-col gap-3 border-t border-gray-100 pt-6 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('auth.has_account') }}
                            <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-500">{{ __('nav.sign_in') }}</a>
                        </p>
                        <x-form.button type="submit" id="register-btn" class="sm:w-auto">
                            <span id="register-btn-text">{{ __('nav.register') }}</span>
                            <svg id="register-spinner" class="ml-2 hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </x-form.button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
@php
    $registerScriptI18n = [
        'could_not_load_cities' => __('auth.js_could_not_load_cities'),
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
    const categoryOptions = document.getElementById('category-options');
    const categoryLoading = document.getElementById('category-loading');
    const businessTypeSelect = document.getElementById('business_type');
    let allCategories = [];

    loadCities();
    loadMerchantCategories();
    syncAccountTypeFields();

    document.querySelectorAll('input[name="account_type"]').forEach(input => {
        input.addEventListener('change', syncAccountTypeFields);
    });
    businessTypeSelect.addEventListener('change', renderMerchantCategories);

    async function loadCities() {
        try {
            const res = await window.axios.get('/api/cities');
            const cities = res.data.data || [];
            const sel = document.getElementById('city_id');

            cities.forEach(function (city) {
                const opt = document.createElement('option');
                opt.value = city.id;
                opt.textContent = city.name;
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
            allCategories = res.data.data || [];

            renderMerchantCategories();
            categoryLoading.classList.add('hidden');
        } catch (e) {
            const parsed = window.ApiErrors?.parse
                ? window.ApiErrors.parse(e)
                : { generalMessage: 'Could not load categories.' };

            categoryLoading.textContent = parsed.generalMessage || 'Could not load categories.';
            document.getElementById('category_ids-error').classList.remove('hidden');
        }
    }

    function renderMerchantCategories() {
        const selectedType = businessTypeSelect.value;
        categoryOptions.innerHTML = '';

        if (!selectedType) {
            categoryOptions.innerHTML = '<p class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-3 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 sm:col-span-2 lg:col-span-3">Select a business type to choose categories.</p>';

            return;
        }

        if (selectedType === 'both') {
            categoryOptions.appendChild(createCategoryGroup('Agriculture Categories', allCategories.filter(category => category.type === 'agriculture')));
            categoryOptions.appendChild(createCategoryGroup('Veterinary Categories', allCategories.filter(category => category.type === 'veterinary')));

            return;
        }

        allCategories
            .filter(category => category.type === selectedType)
            .forEach(category => categoryOptions.appendChild(createCategoryOption(category)));
    }

    function createCategoryGroup(title, categories) {
        const section = document.createElement('section');
        section.className = 'sm:col-span-2 lg:col-span-3';

        const heading = document.createElement('h3');
        heading.className = 'mb-3 text-sm font-bold text-gray-900 dark:text-white';
        heading.textContent = title;

        const grid = document.createElement('div');
        grid.className = 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3';
        categories.forEach(category => grid.appendChild(createCategoryOption(category)));

        section.append(heading, grid);

        return section;
    }

    function createCategoryOption(category) {
        const label = document.createElement('label');
        label.className = 'flex min-h-20 cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm transition-all hover:border-brand-300 hover:bg-brand-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800';

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.name = 'category_ids[]';
        input.value = category.id;
        input.className = 'mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500';
        input.addEventListener('change', function () {
            label.classList.toggle('border-brand-400', this.checked);
            label.classList.toggle('bg-brand-50', this.checked);
            label.classList.toggle('dark:bg-brand-500/10', this.checked);
        });

        const content = document.createElement('span');
        content.className = 'min-w-0';

        const name = document.createElement('span');
        name.className = 'block font-bold text-gray-900 dark:text-white';
        name.textContent = category.name;

        const meta = document.createElement('span');
        meta.className = 'mt-1 block text-xs text-gray-500 dark:text-gray-400';
        meta.textContent = `${category.subcategories?.length || 0} subcategories`;

        content.append(name, meta);
        label.append(input, content);

        return label;
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
        payload.append('email', form.email.value.trim());

        if (form.password.value) {
            payload.append('password', form.password.value);
            payload.append('password_confirmation', form.password_confirmation.value);
        }

        if (accountType === 'vendor') {
            payload.append('store_name', form.store_name.value.trim());
            payload.append('business_type', form.business_type.value);
            document.querySelectorAll('input[name="category_ids[]"]:checked').forEach(input => {
                payload.append('category_ids[]', parseInt(input.value, 10) || '');
            });
            payload.append('address', form.address.value.trim());
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

        merchantFields.classList.toggle('hidden', !isMerchant);
        form.store_name.required = isMerchant;
        form.business_type.required = isMerchant;
        form.commercial_register_file.required = isMerchant;

        document.querySelectorAll('.account-type-option').forEach(label => {
            const input = label.querySelector('input[type="radio"]');
            label.classList.toggle('border-brand-400', input.checked);
            label.classList.toggle('bg-brand-50', input.checked);
            label.classList.toggle('dark:bg-brand-500/10', input.checked);
        });

        if (!isMerchant) {
            form.store_name.value = '';
            form.business_type.value = '';
            document.querySelectorAll('input[name="category_ids[]"]').forEach(input => {
                input.checked = false;
                input.closest('label')?.classList.remove('border-brand-400', 'bg-brand-50', 'dark:bg-brand-500/10');
            });
            renderMerchantCategories();
            form.address.value = '';
            form.description.value = '';
            form.commercial_register_file.value = '';
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
            business_type: ['business_type'],
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
