@extends('layouts.admin')

@section('title', 'Add Product - Vetora Admin')
@section('page-title', 'Add Product')

@section('content')
<div class="mx-auto max-w-3xl">
    <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.products.index') }}" class="transition-colors hover:text-brand-600">Products</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="font-medium text-gray-900">Create</span>
    </nav>

    <x-alert type="error" id="create-alert" />
    <x-alert type="success" id="create-success" />

    <form id="create-form" class="space-y-6" novalidate enctype="multipart/form-data">
        <x-products.form-fields :showVendorSelect="true" :showLegacyMediaFields="false" />
        <x-products.photo-upload color="brand" />

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.products.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" id="create-btn" class="btn-primary">
                <span id="create-btn-text">Create Product</span>
                <svg id="create-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<x-products.photo-upload-script color="brand" alertId="create-alert" />
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const form = document.getElementById('create-form');
    const vendorSelect = document.getElementById('vendor_id');
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const subcategoryFieldWrap = document.getElementById('subcategory-field-wrap');
    const categoryTypeFieldWrap = document.getElementById('category-type-field-wrap');
    const categoryTypeDisplay = document.getElementById('category_type_display');
    const productTypeProxyWrap = document.getElementById('product-type-proxy-wrap');
    const productTypeProxy = document.getElementById('product_type_proxy');
    const agricultureSection = document.querySelector('[data-detail-section="agriculture"]');
    const veterinarySection = document.querySelector('[data-detail-section="veterinary"]');
    const productTypeOptions = {
        agriculture: [
            { value: 'pesticide', label: 'Pesticide' },
            { value: 'fertilizer', label: 'Fertilizer' },
            { value: 'seed', label: 'Seed' },
            { value: 'soil_amendment', label: 'Soil Amendment' },
            { value: 'growth_regulator', label: 'Growth Regulator' },
            { value: 'other', label: 'Other' },
        ],
        veterinary: [
            { value: 'veterinary_medicine', label: 'Veterinary Medicine' },
        ],
    };
    const STORAGE_KEY = 'admin_product_create_form';
    const baseApiPath = '/api/admin';
    let savedVendorId = '';
    let savedCategoryId = '';

    initArrayLists();

    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const data = JSON.parse(saved);
            savedVendorId = data.vendor_id || '';
            savedCategoryId = data.category_id || '';
            if (form.name_ar) form.name_ar.value = data.name_ar || '';
            if (form.name_en) form.name_en.value = data.name_en || '';
            if (form.price) form.price.value = data.price || '';
            if (form.quantity) form.quantity.value = data.quantity || '';
            if (form.description) form.description.value = data.description || '';
            if (form.discount_percentage) form.discount_percentage.value = data.discount_percentage || '';
            if (document.getElementById('discount_starts_at')) document.getElementById('discount_starts_at').value = data.discount_starts_at || '';
            if (document.getElementById('discount_ends_at')) document.getElementById('discount_ends_at').value = data.discount_ends_at || '';
            if (document.getElementById('is_active')) document.getElementById('is_active').checked = data.is_active || false;
        }
    } catch (e) {
        console.error('Error restoring form:', e);
    }

    function saveFormData() {
        try {
            const data = {
                vendor_id: vendorSelect?.value || '',
                category_id: categorySelect?.value || '',
                name_ar: form.name_ar?.value || '',
                name_en: form.name_en?.value || '',
                price: form.price?.value || '',
                quantity: form.quantity?.value || '',
                description: form.description?.value || '',
                discount_percentage: form.discount_percentage?.value || '',
                discount_starts_at: document.getElementById('discount_starts_at')?.value || '',
                discount_ends_at: document.getElementById('discount_ends_at')?.value || '',
                is_active: document.getElementById('is_active')?.checked || false,
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (e) {
            console.error('Error saving form:', e);
        }
    }

    if (vendorSelect) {
        vendorSelect.addEventListener('change', async function () {
            await loadCategoriesByVendor(this.value);
            saveFormData();
        });
    }
    categorySelect?.addEventListener('change', function () {
        saveFormData();
        syncSubcategoryOptions();
        syncProductTypeSections();
    });
    productTypeProxy?.addEventListener('change', function () {
        const agriculturalTypeSelect = document.getElementById('agricultural_agricultural_product_type');
        if (agriculturalTypeSelect) {
            agriculturalTypeSelect.value = this.value;
            agriculturalTypeSelect.dispatchEvent(new Event('change'));
        }
    });
    form.name_ar?.addEventListener('input', saveFormData);
    form.name_en?.addEventListener('input', saveFormData);
    form.price?.addEventListener('input', saveFormData);
    form.quantity?.addEventListener('input', saveFormData);
    form.description?.addEventListener('input', saveFormData);
    form.discount_percentage?.addEventListener('input', saveFormData);
    document.getElementById('discount_starts_at')?.addEventListener('change', saveFormData);
    document.getElementById('discount_ends_at')?.addEventListener('change', saveFormData);
    document.getElementById('is_active')?.addEventListener('change', saveFormData);

    try {
        const res = await window.axios.get('/api/admin/vendors?per_page=100');
        const vendors = res.data.data;
        vendorSelect.innerHTML = '<option value="">Select a vendor...</option>' +
            vendors.filter(v => v.is_active).map(v => `<option value="${v.id}">${esc(v.store_name)} - ${esc(v.user?.name || 'N/A')}</option>`).join('');
        if (savedVendorId && vendorSelect.querySelector(`option[value="${savedVendorId}"]`)) {
            vendorSelect.value = savedVendorId;
        }
    } catch (e) {
        vendorSelect.innerHTML = '<option value="">Failed to load vendors</option>';
        console.error('Failed to load vendors:', e);
    }

    await loadCategoriesByVendor(vendorSelect?.value || '');
    if (savedCategoryId) {
        categorySelect.value = savedCategoryId;
    }
    syncSubcategoryOptions();
    syncProductTypeSections();

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);

        const formData = new FormData();
        formData.append('vendor_id', vendorSelect.value);
        formData.append('category_id', categorySelect.value);
        if (subcategorySelect?.value) formData.append('subcategory_id', subcategorySelect.value);
        formData.append('name_ar', form.name_ar.value.trim());
        formData.append('name_en', form.name_en.value.trim());
        formData.append('price', parseFloat(form.price.value) || 0);
        if (form.discount_percentage.value !== '') formData.append('discount_percentage', parseFloat(form.discount_percentage.value) || 0);
        formData.append('quantity', parseInt(form.quantity.value) || 0);
        formData.append('is_active', document.getElementById('is_active').checked ? '1' : '0');
        const desc = form.description.value.trim();
        if (desc) formData.append('description', desc);
        if (document.getElementById('discount_starts_at').value) formData.append('discount_starts_at', document.getElementById('discount_starts_at').value);
        if (document.getElementById('discount_ends_at').value) formData.append('discount_ends_at', document.getElementById('discount_ends_at').value);
        appendDetailFields(formData);

        const selectedPhotos = window.getSelectedPhotoPayload ? window.getSelectedPhotoPayload() : [];
        selectedPhotos.forEach((item) => {
            formData.append('photos[]', item.file);
            formData.append('photo_types[]', item.image_type);
            formData.append('photo_sort_orders[]', item.sort_order);
        });

        try {
            await window.axios.post('/api/admin/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            localStorage.removeItem(STORAGE_KEY);
            showAlert('create-success', 'Product created successfully! Redirecting...');
            setTimeout(() => { window.location.href = '{{ route("admin.products.index") }}'; }, 800);
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(l) {
        document.getElementById('create-btn').disabled = l;
        document.getElementById('create-spinner').classList.toggle('hidden', !l);
        document.getElementById('create-btn-text').textContent = l ? 'Creating...' : 'Create Product';
    }

    function clearErrors() {
        document.getElementById('create-alert').classList.add('hidden');
        document.getElementById('create-success').classList.add('hidden');
        document.querySelectorAll('.form-error').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }

    function showAlert(id, msg) {
        const el = document.getElementById(id);
        document.getElementById(id + '-message').textContent = msg;
        el.classList.remove('hidden');
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            for (const [f, m] of Object.entries(errors)) {
                const el = document.querySelector(`[data-error-key="${f}"]`) || document.getElementById(f + '-error');
                if (el) {
                    el.textContent = Array.isArray(m) ? m[0] : m;
                    el.classList.remove('hidden');
                }
            }
            showAlert('create-alert', 'Validation failed: ' + Object.values(errors).flat().join(', '));
            return;
        }
        showAlert('create-alert', error.response?.data?.message || error.message || 'An unexpected error occurred.');
    }

    function appendDetailFields(formData) {
        document.querySelectorAll('[data-array-list]').forEach((list) => {
            const key = list.getAttribute('data-array-list');
            const values = Array.from(list.querySelectorAll('[data-array-item-input]'))
                .map((input) => input.value.trim())
                .filter(Boolean);

            values.forEach((value) => formData.append(`${key}[]`, value));
        });

        document.querySelectorAll('[data-request-key]').forEach((field) => {
            if (field.disabled) {
                return;
            }

            const key = field.getAttribute('data-request-key');
            const value = field.value;

            if (typeof value === 'string' && value.trim() !== '') {
                formData.append(key, value.trim());
            }
        });
    }

    function esc(t) {
        if (!t) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    async function loadCategoriesByVendor(vendorId) {
        if (!categorySelect) return;
        savedCategoryId = '';
        categorySelect.innerHTML = '<option value="">Select category...</option>';
        if (!vendorId) {
            return;
        }
        try {
            const res = await window.axios.get(`${baseApiPath}/vendors/${vendorId}`);
            const categories = res.data?.data?.categories || [];
            categorySelect.innerHTML = '<option value="">Select category...</option>' +
                categories.map(category => `<option value="${category.id}" data-type="${esc(category.type || '')}" data-subcategories='${JSON.stringify(category.subcategories || []).replace(/'/g, '&#39;')}'>${esc(category.name)}</option>`).join('');
            syncSubcategoryOptions();
            syncProductTypeSections();
        } catch (error) {
            categorySelect.innerHTML = '<option value="">Failed to load categories</option>';
            console.error('Failed to load categories:', error);
        }
    }

    function setSectionState(section, visible) {
        if (!section) {
            return;
        }

        section.classList.toggle('hidden', !visible);
        section.querySelectorAll('input, textarea, select').forEach((field) => {
            field.disabled = !visible;
        });
    }

    function syncProductTypeSections() {
        const selectedOption = categorySelect?.selectedOptions?.[0];
        const type = selectedOption?.dataset?.type || '';

        if (categoryTypeFieldWrap && categoryTypeDisplay) {
            categoryTypeFieldWrap.classList.toggle('hidden', type === '');
            categoryTypeDisplay.value = type ? type.charAt(0).toUpperCase() + type.slice(1) : '';
        }

        if (productTypeProxyWrap && productTypeProxy) {
            const options = productTypeOptions[type] || [];
            productTypeProxyWrap.classList.toggle('hidden', options.length === 0);
            productTypeProxy.innerHTML = '<option value="">Select product type from the list</option>' +
                options.map((option) => `<option value="${option.value}">${option.label}</option>`).join('');
            if (type !== 'agriculture') {
                productTypeProxy.value = options[0]?.value || '';
            }
        }

        setSectionState(agricultureSection, type === 'agriculture');
        setSectionState(veterinarySection, type === 'veterinary');
        const agriculturalTypeSelect = document.getElementById('agricultural_agricultural_product_type');
        if (agriculturalTypeSelect) {
            if (type !== 'agriculture') {
                agriculturalTypeSelect.value = '';
            } else if (productTypeProxy) {
                agriculturalTypeSelect.value = productTypeProxy.value;
            }
            agriculturalTypeSelect.dispatchEvent(new Event('change'));
        }
    }

    function syncSubcategoryOptions() {
        if (!subcategorySelect || !subcategoryFieldWrap) {
            return;
        }

        const selectedOption = categorySelect?.selectedOptions?.[0];
        const subcategories = selectedOption?.dataset?.subcategories
            ? JSON.parse(selectedOption.dataset.subcategories)
            : [];

        if (!Array.isArray(subcategories) || subcategories.length === 0) {
            subcategoryFieldWrap.classList.add('hidden');
            subcategorySelect.innerHTML = '<option value="">Select subcategory...</option>';
            subcategorySelect.value = '';
            return;
        }

        subcategoryFieldWrap.classList.remove('hidden');
        subcategorySelect.innerHTML = '<option value="">Select subcategory...</option>' +
            subcategories.map((subcategory) => `<option value="${subcategory.id}">${esc(subcategory.name_ar || subcategory.name_en || '')}</option>`).join('');
    }

    function initArrayLists() {
        document.querySelectorAll('[data-array-list]').forEach((list) => {
            if (list.dataset.initialized === 'true') {
                return;
            }

            list.dataset.initialized = 'true';
            list.querySelector('[data-array-add]')?.addEventListener('click', () => {
                appendArrayItem(list, '');
            });

            appendArrayItem(list, '');
        });
    }

    function appendArrayItem(list, value) {
        const items = list.querySelector('[data-array-items]');
        const placeholder = list.getAttribute('data-array-placeholder') || '';
        const item = document.createElement('div');
        item.className = 'flex items-center gap-2';
        item.innerHTML = `
            <input type="text" class="form-input" data-array-item-input placeholder="${escapeHtml(placeholder)}">
            <button type="button" class="btn-danger btn-xs shrink-0" data-array-remove>Remove</button>
        `;
        item.querySelector('[data-array-item-input]').value = value || '';
        item.querySelector('[data-array-remove]').addEventListener('click', () => item.remove());
        items.appendChild(item);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }
});
</script>
@endpush
