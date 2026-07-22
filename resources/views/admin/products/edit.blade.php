@extends('layouts.admin')

@section('title', 'Edit Product - Vetora Admin')
@section('page-title', 'Edit Product')

@section('content')
<div class="mx-auto max-w-3xl">
    <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.products.index') }}" class="hover:text-gray-700">Products</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Edit</span>
    </nav>

    <div id="edit-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading product...</p>
    </div>

    <div id="edit-content" class="hidden space-y-5">
        <x-alert type="error" id="edit-alert" />
        <x-alert type="success" id="edit-success" />

        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vendor</p>
            <p id="vendor-info" class="mt-1 text-sm font-semibold text-slate-900">-</p>
        </div>

        <form id="edit-form" class="space-y-6" novalidate>
            <x-products.form-fields :showVendorSelect="false" :showLegacyMediaFields="false" />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.products.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" id="edit-btn" class="btn-primary">
                    <span id="edit-btn-text">Save Changes</span>
                    <svg id="edit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Product Photos</h2>
                        <p class="mt-0.5 text-sm text-gray-500">Update image type, order, primary image, and add more photos from here.</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <x-alert type="error" id="photo-alert" />
                <x-alert type="success" id="photo-success" />
                <div id="existing-photos" class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5"></div>
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <label class="form-label">Add More Photos</label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <input type="file" id="new-photos" multiple accept="image/jpeg,image/png,image/gif,image/webp" class="form-input flex-1 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div id="new-photo-preview" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
                </div>
                <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                    <button type="button" id="save-photos-btn" class="btn-primary">
                        <span id="save-photos-btn-text">Save Photo Changes</span>
                        <svg id="save-photos-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </button>
                </div>
            </div>
        </div>
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
    const baseApiPath = '/api/admin';
    let existingPhotos = [];
    let newPhotoFiles = [];
    let selectedIds = new Set();
    let primaryPhotoId = null;

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

    function esc(t) {
        if (!t) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    function showAlert(id, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        const msgEl = document.getElementById(id + '-message');
        if (msgEl) msgEl.textContent = msg;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 5000);
    }

    function renderExistingPhotos() {
        const container = document.getElementById('existing-photos');
        if (existingPhotos.length === 0) {
            container.innerHTML = '<p class="col-span-full text-sm text-gray-400">No photos yet.</p>';
            return;
        }
        container.innerHTML = existingPhotos.map(photo => {
            const isPrimary = photo.is_primary === true;
            const isMarkedPrimary = primaryPhotoId === photo.id;
            const isSelected = selectedIds.has(photo.id);
            const photoUrl = photo.url.replace(/"/g, '&quot;');
            return `<div class="space-y-2">
                <div class="relative aspect-square overflow-hidden rounded-lg border-2 transition-all duration-200 ${isSelected ? 'border-red-500 ring-4 ring-red-200 shadow-lg' : isMarkedPrimary ? 'border-emerald-500 ring-4 ring-emerald-200 shadow-lg' : isPrimary ? 'border-blue-400 ring-2 ring-blue-200' : 'border-gray-200 hover:border-gray-300'}" data-photo-id="${photo.id}" data-photo-url="${photoUrl}">
                    <img src="${photoUrl}" class="h-full w-full object-cover transition-transform duration-200 ${isSelected || isMarkedPrimary ? 'opacity-60' : 'group-hover:scale-105'}" alt="">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-gray-500">Type</label>
                        <select data-existing-photo-type="${photo.id}" class="form-select w-full !py-1 text-xs">
                            <option value="front" ${(photo.image_type || 'front') === 'front' ? 'selected' : ''}>Front</option>
                            <option value="back" ${(photo.image_type || 'front') === 'back' ? 'selected' : ''}>Back</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-gray-500">Order</label>
                        <input type="number" min="1" value="${photo.sort_order || 1}" data-existing-photo-sort="${photo.id}" class="form-input !py-1 text-xs">
                    </div>
                </div>
                <div class="flex items-center justify-center gap-1.5">
                    <button type="button" data-action="remove" data-photo-id="${photo.id}" class="group flex h-10 w-10 items-center justify-center rounded-lg ${isSelected ? 'bg-red-50 text-red-600 border-2 border-red-400 shadow-md' : 'bg-white text-gray-600 border border-gray-300 hover:bg-red-50 hover:text-red-600 hover:border-red-400'} transition-all duration-200 shadow-sm hover:shadow-md">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    </button>
                    <button type="button" data-action="view" data-photo-url="${photoUrl}" class="group flex h-10 w-10 items-center justify-center rounded-lg bg-white text-gray-600 border border-gray-300 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-400 transition-all duration-200 shadow-sm hover:shadow-md">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                    </button>
                    <button type="button" data-action="primary" data-photo-id="${photo.id}" class="group flex h-10 w-10 items-center justify-center rounded-lg ${isMarkedPrimary ? 'bg-emerald-50 text-emerald-600 border-2 border-emerald-400 shadow-md' : isPrimary ? 'bg-green-50 text-green-600 border border-green-300' : 'bg-white text-gray-600 border border-gray-300 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-400'} transition-all duration-200 shadow-sm hover:shadow-md">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                </div>
            </div>`;
        }).join('');
    }

    function updateSavePhotosButton() {
        const btn = document.getElementById('save-photos-btn');
        const existingMetadataChanged = existingPhotos.some((photo) => {
            const currentType = document.querySelector(`[data-existing-photo-type="${photo.id}"]`)?.value || photo.image_type || 'front';
            const currentSort = Number.parseInt(document.querySelector(`[data-existing-photo-sort="${photo.id}"]`)?.value || `${photo.sort_order || 1}`, 10) || (photo.sort_order || 1);

            return currentType !== (photo.image_type || 'front') || currentSort !== (photo.sort_order || 1);
        });
        const hasChanges = selectedIds.size > 0 || primaryPhotoId !== null || newPhotoFiles.length > 0 || existingMetadataChanged;
        if (!btn) return;
        btn.disabled = !hasChanges;
        btn.classList.toggle('opacity-50', !hasChanges);
        btn.classList.toggle('cursor-not-allowed', !hasChanges);
    }

    window.togglePhotoSelect = function (id) {
        const photoId = parseInt(id);
        if (selectedIds.has(photoId)) selectedIds.delete(photoId); else selectedIds.add(photoId);
        if (primaryPhotoId === photoId && selectedIds.has(photoId)) primaryPhotoId = null;
        renderExistingPhotos();
        updateSavePhotosButton();
    };

    window.togglePrimaryMark = function (id) {
        const photoId = parseInt(id);
        primaryPhotoId = primaryPhotoId === photoId ? null : photoId;
        if (selectedIds.has(photoId)) selectedIds.delete(photoId);
        renderExistingPhotos();
        updateSavePhotosButton();
    };

    window.viewPhotoLarge = function (url) {
        const existingModal = document.getElementById('photo-modal');
        if (existingModal) existingModal.remove();
        const modal = document.createElement('div');
        modal.id = 'photo-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4';
        modal.innerHTML = `<div class="relative max-h-[90vh] max-w-[90vw]"><img src="${url}" class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain shadow-2xl" alt="Product photo"><button type="button" onclick="document.getElementById('photo-modal')?.remove()" class="absolute right-2 top-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-gray-900 shadow-lg backdrop-blur-sm transition-all hover:bg-white hover:scale-110" title="Close"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button></div>`;
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
        document.body.appendChild(modal);
    };

    document.getElementById('existing-photos')?.addEventListener('click', function(e) {
        const button = e.target.closest('button[data-action]');
        if (!button) return;
        const action = button.getAttribute('data-action');
        const photoId = button.getAttribute('data-photo-id');
        const photoUrl = button.getAttribute('data-photo-url');
        if (action === 'remove' && photoId) window.togglePhotoSelect(parseInt(photoId));
        if (action === 'view' && photoUrl) window.viewPhotoLarge(photoUrl);
        if (action === 'primary' && photoId) window.togglePrimaryMark(parseInt(photoId));
    });
    document.getElementById('existing-photos')?.addEventListener('change', updateSavePhotosButton);

    document.getElementById('new-photos')?.addEventListener('change', function () {
        newPhotoFiles = Array.from(this.files || []).map((file, index) => ({
            file,
            image_type: index === 0 ? 'front' : 'back',
            sort_order: existingPhotos.length + index + 1,
        }));

        renderNewPhotoPreviews();
        updateSavePhotosButton();
    });

    function renderNewPhotoPreviews() {
        const container = document.getElementById('new-photo-preview');
        if (!container) {
            return;
        }

        if (newPhotoFiles.length === 0) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = newPhotoFiles.map((item, index) => {
            const url = URL.createObjectURL(item.file);

            return `<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="aspect-square overflow-hidden">
                    <img src="${url}" class="h-full w-full object-cover" alt="">
                </div>
                <div class="space-y-2 p-2">
                    <p class="truncate text-xs font-medium text-gray-700">${esc(item.file.name)}</p>
                    <div>
                        <label class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-gray-500">Type</label>
                        <select data-new-photo-type="${index}" class="form-select w-full !py-1 text-xs">
                            <option value="front" ${item.image_type === 'front' ? 'selected' : ''}>Front</option>
                            <option value="back" ${item.image_type === 'back' ? 'selected' : ''}>Back</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-gray-500">Order</label>
                        <input type="number" min="1" value="${item.sort_order}" data-new-photo-sort="${index}" class="form-input !py-1 text-xs">
                    </div>
                </div>
            </div>`;
        }).join('');

        container.querySelectorAll('[data-new-photo-type]').forEach((select) => {
            select.addEventListener('change', function () {
                const index = Number.parseInt(this.getAttribute('data-new-photo-type'), 10);
                if (!Number.isNaN(index) && newPhotoFiles[index]) {
                    newPhotoFiles[index].image_type = this.value;
                }
            });
        });
    }

    document.getElementById('save-photos-btn')?.addEventListener('click', async function() {
        const btn = this;
        const btnText = document.getElementById('save-photos-btn-text');
        const spinner = document.getElementById('save-photos-spinner');
        btn.disabled = true;
        btnText.textContent = 'Saving...';
        spinner.classList.remove('hidden');
        try {
            const formData = new FormData();
            Array.from(selectedIds).forEach(id => formData.append('photo_ids_to_remove[]', parseInt(id)));
            existingPhotos.forEach((photo) => {
                formData.append('photo_ids[]', photo.id);
                formData.append('existing_photo_types[]', document.querySelector(`[data-existing-photo-type="${photo.id}"]`)?.value || photo.image_type || 'front');
                formData.append('existing_photo_sort_orders[]', document.querySelector(`[data-existing-photo-sort="${photo.id}"]`)?.value || photo.sort_order || 1);
            });
            newPhotoFiles.forEach((item, index) => {
                formData.append('photos[]', item.file);
                formData.append('photo_types[]', item.image_type || 'front');
                formData.append('photo_sort_orders[]', document.querySelector(`[data-new-photo-sort="${index}"]`)?.value || item.sort_order || index + 1);
            });
            if (primaryPhotoId) formData.append('primary_photo_id', parseInt(primaryPhotoId));
            const res = await window.axios.post(`/api/admin/products/${productId}/photos/update`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const product = res.data.data;
            if (product && product.photos) {
                existingPhotos = product.photos;
                selectedIds.clear();
                primaryPhotoId = null;
                newPhotoFiles = [];
                renderExistingPhotos();
                document.getElementById('new-photos').value = '';
                renderNewPhotoPreviews();
                updateSavePhotosButton();
                showAlert('photo-success', 'Photo changes saved successfully!');
            }
        } catch (e) {
            showAlert('photo-alert', e.response?.data?.message || 'Failed to save photo changes.');
        } finally {
            btn.disabled = false;
            btnText.textContent = 'Save Photo Changes';
            spinner.classList.add('hidden');
        }
    });

    try {
        await loadCategories();
        const res = await window.axios.get('/api/admin/products/' + productId);
        const p = res.data.data;
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
        document.getElementById('is_active').checked = p.is_active;
        document.getElementById('discount_starts_at').value = toDateInput(p.discount_starts_at);
        document.getElementById('discount_ends_at').value = toDateInput(p.discount_ends_at);
        populateDetailFields(p);
        if (productTypeProxy) {
            productTypeProxy.value = p.product_type || p.agricultural_detail?.agricultural_product_type || '';
        }
        document.getElementById('agricultural_agricultural_product_type')?.dispatchEvent(new Event('change'));
        existingPhotos = p.photos || [];
        const vendorName = p.vendor?.store_name || '-';
        const ownerName = p.vendor?.user?.name || '';
        document.getElementById('vendor-info').textContent = vendorName + (ownerName ? ' - ' + ownerName : '');
        renderExistingPhotos();
        document.getElementById('edit-loading').classList.add('hidden');
        document.getElementById('edit-content').classList.remove('hidden');
    } catch (e) {
        document.getElementById('edit-loading').innerHTML = '<p class="text-red-500">Failed to load product.</p>';
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
        formData.append('price', parseFloat(form.price.value));
        if (form.discount_percentage.value !== '') formData.append('discount_percentage', parseFloat(form.discount_percentage.value));
        formData.append('quantity', parseInt(form.quantity.value));
        const desc = form.description.value.trim();
        if (desc) formData.append('description', desc);
        formData.append('is_active', document.getElementById('is_active').checked ? '1' : '0');
        if (document.getElementById('discount_starts_at').value) formData.append('discount_starts_at', document.getElementById('discount_starts_at').value);
        if (document.getElementById('discount_ends_at').value) formData.append('discount_ends_at', document.getElementById('discount_ends_at').value);
        appendDetailFields(formData);
        try {
            await window.axios.post('/api/admin/products/' + productId, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                params: { _method: 'PUT' },
            });
            showAlert('edit-success', 'Product updated! Redirecting...');
            setTimeout(() => { window.location.href = '{{ route("admin.products.index") }}'; }, 800);
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
            showAlert('edit-alert', 'Validation failed: ' + Object.values(errors).flat().join(', '));
            return;
        }
        showAlert('edit-alert', error.response?.data?.message || error.message || 'An unexpected error occurred.');
    }

    async function loadCategories() {
        try {
            const res = await window.axios.get(`${baseApiPath}/categories`);
            const categories = res.data.data || [];
            categorySelect.innerHTML = '<option value="">Select category...</option>' +
                categories.map(category => `<option value="${category.id}" data-type="${esc(category.type || '')}" data-subcategories='${JSON.stringify(category.subcategories || []).replace(/'/g, '&#39;')}'>${esc(category.name)}</option>`).join('');
        } catch (error) {
            categorySelect.innerHTML = '<option value="">Failed to load categories</option>';
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

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
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
});
</script>
@endpush
