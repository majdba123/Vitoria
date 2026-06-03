@extends('layouts.admin')

@section('title', 'Edit Subcategory — Vetora Admin')
@section('page-title', 'Edit Subcategory')

@section('content')
<div class="space-y-4">
    <div id="subcategory-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading subcategory...</p>
    </div>

    <div id="subcategory-form-wrapper" class="hidden">
        <div class="card">
            <div class="card-body">
                <form id="subcategory-form" enctype="multipart/form-data">
                    <div class="space-y-4">
                        <div>
                            <label for="category_id" class="form-label">Category <span class="text-red-500">*</span></label>
                            <select id="category_id" name="category_id" class="form-input" required>
                                <option value="">Select a category</option>
                            </select>
                        </div>

                        <div>
                            <label for="name" class="form-label">Subcategory Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" class="form-input" required>
                        </div>

                        <div>
                            <label for="image" class="form-label">Image</label>
                            <div id="current-image" class="mb-2"></div>
                            <input type="file" id="image" name="image" accept="image/*" class="form-input">
                            <p class="mt-1 text-xs text-gray-500">Accepted formats: JPEG, PNG, GIF, WebP. Max size: 2MB</p>
                        </div>

                        <div>
                            <label for="icon_class" class="form-label">Font Awesome icon classes</label>
                            <input type="text" id="icon_class" name="icon_class" class="form-input" placeholder="e.g. fa-solid fa-leaf" maxlength="191">
                            <p class="mt-1 text-xs text-gray-500">Optional. Storefront menus prefer this over the image when set.</p>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <a href="{{ route('admin.subcategories.index') }}" class="btn-secondary btn-sm flex-1">Cancel</a>
                            <button type="submit" class="btn-primary btn-sm flex-1">Update Subcategory</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<x-alert type="error" id="subcategory-alert" />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const subcategoryId = {{ $subcategoryId }};
    const form = document.getElementById('subcategory-form');
    const formWrapper = document.getElementById('subcategory-form-wrapper');
    const loading = document.getElementById('subcategory-loading');
    const alert = document.getElementById('subcategory-alert');
    const currentImage = document.getElementById('current-image');
    const categorySelect = document.getElementById('category_id');

    loadCategories();
    loadSubcategory();

    async function loadCategories() {
        try {
            const res = await window.axios.get('/api/admin/categories');
            const categories = res.data.data || [];
            categorySelect.innerHTML = '<option value="">Select a category</option>' +
                categories.map(cat => `<option value="${cat.id}">${esc(cat.name)}</option>`).join('');
        } catch (e) {
            console.error('Failed to load categories:', e);
        }
    }

    async function loadSubcategory() {
        try {
            const res = await window.axios.get(`/api/admin/subcategories/${subcategoryId}`);
            const subcategory = res.data.data;

            document.getElementById('name').value = subcategory.name || '';
            document.getElementById('icon_class').value = subcategory.icon_class || '';
            categorySelect.value = subcategory.category_id || '';
            currentImage.innerHTML = '';
            if (subcategory.image) {
                currentImage.innerHTML = `<img src="/storage/${subcategory.image}" alt="${subcategory.name}" class="h-20 w-20 rounded-lg object-cover">`;
            }
            if (subcategory.icon_class) {
                const wrap = document.createElement('div');
                wrap.className = 'mt-2 flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 ring-1 ring-gray-100';
                wrap.innerHTML = '<span class="text-xs font-medium text-gray-500">Preview</span><i class="' + subcategory.icon_class + '" aria-hidden="true"></i>';
                currentImage.appendChild(wrap);
            }

            loading.classList.add('hidden');
            formWrapper.classList.remove('hidden');
        } catch (e) {
            alert.textContent = 'Failed to load subcategory.';
            alert.classList.remove('hidden');
            loading.classList.add('hidden');
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        try {
            await window.axios.post(`/api/admin/subcategories/${subcategoryId}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                params: { _method: 'PUT' }
            });
            window.location.href = '/admin/subcategories';
        } catch (e) {
            alert.textContent = e.response?.data?.message || 'Failed to update subcategory.';
            alert.classList.remove('hidden');
        }
    });

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
@endpush

