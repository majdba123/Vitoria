@extends('layouts.vendor')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
<div class="mx-auto max-w-3xl">
    <div id="edit-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading product...</p>
    </div>

    <div id="edit-content" class="hidden space-y-6">
        <x-alert type="error" id="edit-alert" />
        <x-alert type="success" id="edit-success" />

        <form id="edit-form" class="space-y-6" novalidate enctype="multipart/form-data">
            <x-products.form-fields />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('vendor.products.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" id="edit-btn" class="btn-primary">
                    <span id="edit-btn-text">Save Changes</span>
                    <svg id="edit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const productId = '{{ $productId }}';
    const form = document.getElementById('edit-form');
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const subcategoryFieldWrap = document.getElementById('subcategory-field-wrap');
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

    initArrayLists();
    categorySelect?.addEventListener('change', function () {
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

    try {
        const [categoriesResponse, productResponse] = await Promise.all([
            window.axios.get('/api/vendor/allowed-categories'),
            window.axios.get('/api/vendor/products/' + productId),
        ]);
        const categories = categoriesResponse.data.data || [];
        const p = productResponse.data.data;
        categorySelect.innerHTML = '<option value="">Select category...</option>' +
            categories.map(category => `<option value="${category.id}" data-type="${escapeHtml(category.type || '')}" data-subcategories='${JSON.stringify(category.subcategories || []).replace(/'/g, '&#39;')}'>${escapeHtml(category.name)}</option>`).join('');
        form.name_ar.value = p.name_ar || '';
        form.name_en.value = p.name_en || '';
        form.price.value = p.price || '';
        form.discount_percentage.value = p.discount_percentage || '';
        form.quantity.value = p.quantity || 0;
        categorySelect.value = p.category_id || '';
        syncSubcategoryOptions();
        subcategorySelect.value = p.subcategory_id || '';
        syncProductTypeSections();
        form.description.value = p.description || '';
        document.getElementById('is_active').checked = !!p.is_active;
        document.getElementById('discount_starts_at').value = toDateInput(p.discount_starts_at);
        document.getElementById('discount_ends_at').value = toDateInput(p.discount_ends_at);
        populateDetailFields(p);
        if (productTypeProxy) {
            productTypeProxy.value = p.product_type || p.agricultural_detail?.agricultural_product_type || '';
        }
        document.getElementById('agricultural_agricultural_product_type')?.dispatchEvent(new Event('change'));
        document.getElementById('edit-loading').classList.add('hidden');
        document.getElementById('edit-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('edit-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load product.</p>';
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
        try {
            await window.axios.post('/api/vendor/products/' + productId, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                params: { _method: 'PUT' },
            });
            document.getElementById('edit-success-message').textContent = 'Product updated successfully.';
            document.getElementById('edit-success').classList.remove('hidden');
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(l) {
        document.getElementById('edit-btn').disabled = l;
        document.getElementById('edit-spinner').classList.toggle('hidden', !l);
        document.getElementById('edit-btn-text').textContent = l ? 'Saving...' : 'Save Changes';
    }

    function clearErrors() {
        document.getElementById('edit-alert').classList.add('hidden');
        document.getElementById('edit-success').classList.add('hidden');
        document.querySelectorAll('.form-error').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
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
            document.getElementById('edit-alert-message').textContent = 'Validation failed: ' + Object.values(errors).flat().join(', ');
            document.getElementById('edit-alert').classList.remove('hidden');
            return;
        }

        document.getElementById('edit-alert-message').textContent = error.response?.data?.message || 'Failed to update product.';
        document.getElementById('edit-alert').classList.remove('hidden');
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

    function populateDetailFields(product) {
        const sections = {
            shared_detail: product.shared_detail || {},
            agricultural_detail: product.agricultural_detail || {},
            veterinary_detail: product.veterinary_detail || {},
        };

        Object.entries(sections).forEach(([section, values]) => {
            Object.entries(values).forEach(([key, value]) => {
                const arrayList = document.querySelector(`[data-array-list="${section}[${key}]"]`);

                if (arrayList && Array.isArray(value)) {
                    setArrayListValues(arrayList, value);
                    return;
                }

                const input = document.querySelector(`[data-request-key="${section}[${key}]"]`);

                if (!input || value === null || value === undefined) {
                    return;
                }

                input.value = value;
            });
        });
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
            subcategories.map((subcategory) => `<option value="${subcategory.id}">${escapeHtml(subcategory.name_ar || subcategory.name_en || '')}</option>`).join('');
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

        if (productTypeProxyWrap && productTypeProxy) {
            const options = productTypeOptions[type] || [];
            const currentValue = productTypeProxy.value;
            productTypeProxyWrap.classList.toggle('hidden', options.length === 0);
            productTypeProxy.innerHTML = '<option value="">Select product type from the list</option>' +
                options.map((option) => `<option value="${option.value}">${option.label}</option>`).join('');
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
    }

    function toDateInput(value) {
        if (!value) return '';
        if (typeof value === 'string') {
            const matched = value.match(/^\d{4}-\d{2}-\d{2}/);
            if (matched) return matched[0];
        }
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const pad = n => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
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
        });
    }

    function setArrayListValues(list, values) {
        const items = list.querySelector('[data-array-items]');
        items.innerHTML = '';

        if (!Array.isArray(values) || values.length === 0) {
            appendArrayItem(list, '');
            return;
        }

        values.forEach((value) => appendArrayItem(list, typeof value === 'string' ? value : ''));
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
