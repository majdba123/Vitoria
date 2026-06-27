@extends('layouts.admin')

@section('title', 'Edit Product â€” Vetora Admin')
@section('page-title', 'Edit Product')

@section('content')
<div class="mx-auto max-w-2xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.products.index') }}" class="hover:text-gray-700">Products</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Edit</span>
    </nav>

    <div id="edit-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading product...</p>
    </div>

    <div id="edit-content" class="hidden space-y-5">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Edit Product</h2>
                <p class="mt-0.5 text-sm text-gray-500">Update product details. Vendor cannot be changed after creation.</p>
            </div>

            <div class="card-body">
                <x-alert type="error" id="edit-alert" />
                <x-alert type="success" id="edit-success" />

                <div class="mb-5 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs font-medium uppercase text-gray-500">Vendor</p>
                    <p id="vendor-info" class="mt-0.5 text-sm font-semibold text-gray-900">â€”</p>
                </div>

                <form id="edit-form" class="space-y-6" novalidate>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-form.input name="name" label="Product Name" placeholder="Enter product name" :required="true" />
                        </div>
                        <x-form.input name="price" label="Price ($)" type="number" placeholder="0.00" :required="true" />
                        <x-form.input name="discount_percentage" label="Discount (%)" type="number" placeholder="Optional" />
                        <x-form.input name="quantity" label="Quantity" type="number" placeholder="0" :required="true" />
                        <div>
                            <label for="discount_starts_at" class="form-label">Discount Start</label>
                            <input id="discount_starts_at" name="discount_starts_at" type="date" class="form-input">
                            <p class="form-error" id="discount_starts_at-error"></p>
                        </div>
                        <div>
                            <label for="discount_ends_at" class="form-label">Discount End</label>
                            <input id="discount_ends_at" name="discount_ends_at" type="date" class="form-input">
                            <p class="form-error" id="discount_ends_at-error"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="category_id" class="form-label">Category <span class="text-red-500">*</span></label>
                            <select id="category_id" name="category_id" class="form-input">
                                <option value="">Select category...</option>
                            </select>
                            <p class="form-error" id="category_id-error"></p>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Product description (optional)" class="form-textarea"></textarea>
                        <p class="form-error" id="description-error"></p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="image" class="form-label">Product Image</label>
                            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input">
                            <p class="form-error" id="image-error"></p>
                        </div>
                        <div>
                            <label for="icon" class="form-label">Product Icon</label>
                            <input id="icon" name="icon" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" class="form-input">
                            <p class="form-error" id="icon-error"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="form-label mb-0">Active</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="is_active">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('admin.products.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" id="edit-btn" class="btn-primary">
                            <span id="edit-btn-text">Save Changes</span>
                            <svg id="edit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Product Photos</h2>
                        <p class="mt-0.5 text-sm text-gray-500">Mark photos to remove or set as primary, then click "Save Photo Changes".</p>
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
    const baseApiPath = '/api/admin';
    let existingPhotos = [];
    let selectedIds = new Set();
    let primaryPhotoId = null;

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
        const hasChanges = selectedIds.size > 0 || primaryPhotoId !== null || (document.getElementById('new-photos')?.files?.length || 0) > 0;
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

    document.getElementById('new-photos')?.addEventListener('change', updateSavePhotosButton);

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
            const newPhotosInput = document.getElementById('new-photos');
            if (newPhotosInput?.files?.length) {
                Array.from(newPhotosInput.files).forEach(f => formData.append('photos[]', f));
            }
            if (primaryPhotoId) formData.append('primary_photo_id', parseInt(primaryPhotoId));
            const res = await window.axios.post(`/api/admin/products/${productId}/photos/update`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const product = res.data.data;
            if (product && product.photos) {
                existingPhotos = product.photos;
                selectedIds.clear();
                primaryPhotoId = null;
                renderExistingPhotos();
                if (newPhotosInput) newPhotosInput.value = '';
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
        form.name.value = p.name || '';
        form.price.value = p.price || '';
        form.discount_percentage.value = p.discount_percentage || '';
        form.quantity.value = p.quantity || 0;
        categorySelect.value = p.category_id || '';
        form.description.value = p.description || '';
        document.getElementById('is_active').checked = p.is_active;
        document.getElementById('discount_starts_at').value = toDateInput(p.discount_starts_at);
        document.getElementById('discount_ends_at').value = toDateInput(p.discount_ends_at);
        existingPhotos = p.photos || [];
        const vendorName = p.vendor?.store_name || 'â€”';
        const ownerName = p.vendor?.user?.name || '';
        document.getElementById('vendor-info').textContent = vendorName + (ownerName ? ' â€” ' + ownerName : '');
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
        formData.append('name', form.name.value.trim());
        formData.append('price', parseFloat(form.price.value));
        if (form.discount_percentage.value !== '') formData.append('discount_percentage', parseFloat(form.discount_percentage.value));
        formData.append('quantity', parseInt(form.quantity.value));
        const desc = form.description.value.trim();
        if (desc) formData.append('description', desc);
        if (form.image?.files?.[0]) formData.append('image', form.image.files[0]);
        if (form.icon?.files?.[0]) formData.append('icon', form.icon.files[0]);
        formData.append('is_active', document.getElementById('is_active').checked ? '1' : '0');
        if (document.getElementById('discount_starts_at').value) formData.append('discount_starts_at', document.getElementById('discount_starts_at').value);
        if (document.getElementById('discount_ends_at').value) formData.append('discount_ends_at', document.getElementById('discount_ends_at').value);
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
        document.querySelectorAll('[id$="-error"]').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            for (const [f, m] of Object.entries(errors)) {
                const fieldName = f.replace(/\./g, '\\.');
                const el = document.getElementById(f + '-error') || document.getElementById(fieldName + '-error');
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
                categories.map(category => `<option value="${category.id}">${esc(category.name)}</option>`).join('');
        } catch (error) {
            categorySelect.innerHTML = '<option value="">Failed to load categories</option>';
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
});
</script>
@endpush
