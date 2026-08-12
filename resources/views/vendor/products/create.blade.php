@extends('layouts.vendor')

@section('title', 'Add Product')
@section('page-title', __('vendor.create_product_title'))

@section('content')
<div class="mx-auto max-w-3xl">
    <x-alert type="error" id="create-alert" />
    <x-alert type="success" id="create-success" />

    <form id="create-form" class="space-y-6" novalidate enctype="multipart/form-data">
        <x-products.form-fields />
        <x-products.photo-upload color="brand" />

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('vendor.products.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
            <button type="submit" id="create-btn" class="btn-primary">
                <span id="create-btn-text">{{ __('vendor.create_product_btn') }}</span>
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
    const i18n = {!! json_encode([
        'selectCategory' => __('vendor.select_category'),
        'selectSubcategory' => __('vendor.select_subcategory'),
        'selectProductType' => __('vendor.select_product_type'),
        'failedLoadCategories' => __('vendor.failed_load_categories'),
        'notSelectedYet' => __('vendor.not_selected_yet'),
        'chooseCategoryHint' => __('vendor.choose_category_hint'),
        'agricultureFieldsHint' => __('vendor.agriculture_fields_hint'),
        'veterinaryFieldsHint' => __('vendor.veterinary_fields_hint'),
        'creatingProduct' => __('vendor.creating_product'),
        'createProductBtn' => __('vendor.create_product_btn'),
        'productCreatedSuccess' => __('vendor.product_created_success'),
        'validationFailed' => __('vendor.validation_failed'),
        'unexpectedError' => __('vendor.unexpected_error'),
        'remove' => __('common.remove'),
    ]) !!};
    const form = document.getElementById('create-form');
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const subcategoryFieldWrap = document.getElementById('subcategory-field-wrap');
    const categoryTypeFieldWrap = document.getElementById('category-type-field-wrap');
    const categoryTypeDisplay = document.getElementById('category_type_display');
    const productTypeProxyWrap = document.getElementById('product-type-proxy-wrap');
    const productTypeProxy = document.getElementById('product_type_proxy');
    const productSelectionState = document.getElementById('product-selection-state');
    const productSelectionStateText = document.getElementById('product-selection-state-text');
    const productSelectionStateBadge = document.getElementById('product-selection-state-badge');
    const agricultureSection = document.querySelector('[data-detail-section="agriculture"]');
    const veterinarySection = document.querySelector('[data-detail-section="veterinary"]');
    const STORAGE_KEY = 'vendor_product_create_form';
    const baseApiPath = '/api/vendor';
    let savedCategoryId = '';
    let savedSubcategoryId = '';
    let savedProductType = '';
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

    initArrayLists();

    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const data = JSON.parse(saved);
            savedCategoryId = data.category_id || '';
            savedSubcategoryId = data.subcategory_id || '';
            savedProductType = data.product_type || '';
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
    } catch (e) {}

    function saveFormData() {
        const data = {
            category_id: categorySelect?.value || '',
            subcategory_id: subcategorySelect?.value || '',
            product_type: productTypeProxy?.value || '',
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
        updateSelectionSummary(categorySelect?.selectedOptions?.[0]?.dataset?.type || '', this.value || '');
        saveFormData();
    });
    subcategorySelect?.addEventListener('change', saveFormData);
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
        const res = await window.axios.get(`${baseApiPath}/allowed-categories`);
        const categories = res.data.data || [];
        categorySelect.innerHTML = `<option value="">${esc(i18n.selectCategory)}</option>` +
            categories.map(category => `<option value="${category.id}" data-type="${esc(category.type || '')}" data-subcategories='${JSON.stringify(category.subcategories || []).replace(/'/g, '&#39;')}'>${esc(category.name)}</option>`).join('');
        if (savedCategoryId) {
            categorySelect.value = savedCategoryId;
        }
        syncSubcategoryOptions();
        if (savedSubcategoryId) {
            subcategorySelect.value = savedSubcategoryId;
        }
        syncProductTypeSections();
    } catch (e) {
        categorySelect.innerHTML = `<option value="">${esc(i18n.failedLoadCategories)}</option>`;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);
        const formData = new FormData();
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
            await window.axios.post('/api/vendor/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            localStorage.removeItem(STORAGE_KEY);
            showAlert('create-success', i18n.productCreatedSuccess);
            setTimeout(() => { window.location.href = '{{ route("vendor.products.index") }}'; }, 800);
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(l) {
        document.getElementById('create-btn').disabled = l;
        document.getElementById('create-spinner').classList.toggle('hidden', !l);
        document.getElementById('create-btn-text').textContent = l ? i18n.creatingProduct : i18n.createProductBtn;
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
            showAlert('create-alert', i18n.validationFailed + ' ' + Object.values(errors).flat().join(', '));
            return;
        }
        showAlert('create-alert', error.response?.data?.message || error.message || i18n.unexpectedError);
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

    function updateSelectionSummary(categoryType, productType) {
        if (!productSelectionState || !productSelectionStateText || !productSelectionStateBadge) {
            return;
        }

        const hasCategory = categoryType !== '';
        productSelectionState.classList.toggle('hidden', !hasCategory);

        if (!hasCategory) {
            productSelectionStateBadge.textContent = i18n.notSelectedYet;
            productSelectionStateText.textContent = i18n.chooseCategoryHint;
            return;
        }

        const normalizedCategoryType = categoryType.charAt(0).toUpperCase() + categoryType.slice(1);
        const normalizedProductType = productType
            ? productType.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase())
            : 'Pending product type';

        productSelectionStateBadge.textContent = `${normalizedCategoryType} • ${normalizedProductType}`;
        productSelectionStateText.textContent = categoryType === 'agriculture'
            ? i18n.agricultureFieldsHint
            : i18n.veterinaryFieldsHint;
    }

    function syncSubcategoryOptions() {
        if (!subcategorySelect || !subcategoryFieldWrap) {
            return;
        }

        const selectedOption = categorySelect?.selectedOptions?.[0];
        const subcategories = selectedOption?.dataset?.subcategories
            ? JSON.parse(selectedOption.dataset.subcategories)
            : [];
        const currentValue = subcategorySelect.value || savedSubcategoryId;

        if (!Array.isArray(subcategories) || subcategories.length === 0) {
            subcategoryFieldWrap.classList.add('hidden');
            subcategorySelect.innerHTML = `<option value="">${esc(i18n.selectSubcategory)}</option>`;
            subcategorySelect.value = '';
            subcategorySelect.disabled = true;
            return;
        }

        subcategoryFieldWrap.classList.remove('hidden');
        subcategorySelect.innerHTML = `<option value="">${esc(i18n.selectSubcategory)}</option>` +
            subcategories.map((subcategory) => `<option value="${subcategory.id}">${esc(subcategory.name_ar || subcategory.name_en || '')}</option>`).join('');
        subcategorySelect.disabled = false;
        if (subcategories.some((subcategory) => String(subcategory.id) === String(currentValue))) {
            subcategorySelect.value = currentValue;
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
            const currentValue = productTypeProxy.value || savedProductType;
            productTypeProxyWrap.classList.toggle('hidden', options.length === 0);
            productTypeProxy.innerHTML = `<option value="">${esc(i18n.selectProductType)}</option>` +
                options.map((option) => `<option value="${option.value}">${option.label}</option>`).join('');
            productTypeProxy.disabled = options.length === 0;
            productTypeProxy.value = options.some((option) => option.value === currentValue)
                ? currentValue
                : (type !== 'agriculture' ? (options[0]?.value || '') : '');
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

        updateSelectionSummary(type, productTypeProxy?.value || '');
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
            <button type="button" class="btn-danger btn-xs shrink-0" data-array-remove>${escapeHtml(i18n.remove)}</button>
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
