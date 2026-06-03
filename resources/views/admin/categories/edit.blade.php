@extends('layouts.admin')

@section('title', 'Edit Category — Vetora Admin')
@section('page-title', 'Edit Category')

@section('content')
<div class="space-y-4">
    <div id="category-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading category...</p>
    </div>

    <div id="category-form-wrapper" class="hidden">
        <div class="card">
            <div class="card-body">
                <form id="category-form" enctype="multipart/form-data">
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="form-label">Category Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" class="form-input" required>
                        </div>

                        <div>
                            <label for="type" class="form-label">Category Type <span class="text-red-500">*</span></label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="">Select category type</option>
                                <option value="agriculture">Agriculture</option>
                                <option value="veterinary">Veterinary</option>
                            </select>
                        </div>

                        <div>
                            <label for="commission" class="form-label">Commission (%) <span class="text-red-500">*</span></label>
                            <input type="number" id="commission" name="commission" class="form-input" step="0.01" min="0" max="100" placeholder="e.g. 10.00" value="0">
                            <p class="mt-1 text-xs text-gray-500">Percentage commission charged on products in this category (0–100).</p>
                        </div>

                        <div>
                            <label for="logo" class="form-label">Logo (banner)</label>
                            <div id="current-logo" class="mb-2"></div>
                            <input type="file" id="logo" name="logo" accept="image/*" class="form-input">
                            <p class="mt-1 text-xs text-gray-500">Accepted formats: JPEG, PNG, GIF, WebP. Max size: 2MB</p>
                        </div>

                        <div>
                            <label for="icon" class="form-label">Icon (thumbnail)</label>
                            <div id="current-icon" class="mb-2"></div>
                            <input type="file" id="icon" name="icon" accept="image/*" class="form-input">
                            <p class="mt-1 text-xs text-gray-500">Smaller image for menus and lists. Same formats, max 2MB.</p>
                        </div>

                        <div>
                            <label for="icon_class" class="form-label">Font Awesome icon classes</label>
                            <input type="text" id="icon_class" name="icon_class" class="form-input" placeholder="e.g. fa-solid fa-seedling" maxlength="191">
                            <p class="mt-1 text-xs text-gray-500">Optional. When set, storefront menus prefer this over image thumbnails.</p>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <a href="{{ route('admin.categories.index') }}" class="btn-secondary btn-sm flex-1">Cancel</a>
                            <button type="submit" class="btn-primary btn-sm flex-1">Update Category</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<x-alert type="error" id="category-alert" />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoryId = {{ $categoryId }};
    const form = document.getElementById('category-form');
    const formWrapper = document.getElementById('category-form-wrapper');
    const loading = document.getElementById('category-loading');
    const alert = document.getElementById('category-alert');
    const currentLogo = document.getElementById('current-logo');
    const currentIcon = document.getElementById('current-icon');

    loadCategory();

    async function loadCategory() {
        try {
            const res = await window.axios.get(`/api/admin/categories/${categoryId}`);
            const category = res.data.data;

            document.getElementById('name').value = category.name || '';
            document.getElementById('type').value = category.type || '';
            document.getElementById('commission').value = category.commission ?? 0;
            document.getElementById('icon_class').value = category.icon_class || '';
            if (category.logo) {
                currentLogo.innerHTML = `<img src="/storage/${category.logo}" alt="${category.name}" class="h-20 w-20 rounded-lg object-cover">`;
            }
            currentIcon.innerHTML = '';
            if (category.icon) {
                currentIcon.innerHTML = `<img src="/storage/${category.icon}" alt="" class="h-16 w-16 rounded-lg object-cover ring-1 ring-gray-200">`;
            }
            if (category.icon_class) {
                const wrap = document.createElement('div');
                wrap.className = 'mt-2 flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 ring-1 ring-gray-100';
                wrap.innerHTML = '<span class="text-xs font-medium text-gray-500">Preview</span><i class="' + category.icon_class + '" aria-hidden="true"></i>';
                currentIcon.appendChild(wrap);
            }

            loading.classList.add('hidden');
            formWrapper.classList.remove('hidden');
        } catch (e) {
            alert.textContent = 'Failed to load category.';
            alert.classList.remove('hidden');
            loading.classList.add('hidden');
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        try {
            await window.axios.post(`/api/admin/categories/${categoryId}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                params: { _method: 'PUT' }
            });
            window.location.href = '/admin/categories';
        } catch (e) {
            alert.textContent = e.response?.data?.message || 'Failed to update category.';
            alert.classList.remove('hidden');
        }
    });
});
</script>
@endpush
