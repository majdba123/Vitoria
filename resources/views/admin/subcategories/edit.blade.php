@extends('layouts.admin')

@section('title', 'Edit Subcategory - Vetora Admin')
@section('page-title', 'Edit Subcategory')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.subcategories.index') }}" class="hover:text-gray-700">Subcategories</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Edit</span>
    </nav>

    <div id="subcategory-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading subcategory...</p>
    </div>

    <div id="subcategory-content" class="hidden space-y-6">
        <x-alert type="error" id="subcategory-alert" />

        <form id="subcategory-form" class="space-y-6">
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900">Subcategory Details</h2>
                    <p class="mt-1 text-sm text-gray-500">Update the parent category and names without affecting existing products.</p>
                </div>
                <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="category_id" class="form-label">Parent Category <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">Loading categories...</option>
                        </select>
                        <p class="form-error" id="category_id-error"></p>
                    </div>
                    <div>
                        <label for="name_ar" class="form-label">Arabic Name <span class="text-red-500">*</span></label>
                        <input id="name_ar" name="name_ar" type="text" class="form-input">
                        <p class="form-error" id="name_ar-error"></p>
                    </div>
                    <div>
                        <label for="name_en" class="form-label">English Name <span class="text-red-500">*</span></label>
                        <input id="name_en" name="name_en" type="text" class="form-input">
                        <p class="form-error" id="name_en-error"></p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.subcategories.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" id="save-btn" class="btn-primary">
                    <span id="save-btn-text">Save Changes</span>
                    <svg id="save-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const subcategoryId = {{ $subcategoryId }};
    const form = document.getElementById('subcategory-form');

    await Promise.all([loadCategories(), loadSubcategory()]);

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearErrors();
        toggleLoading(true);

        try {
            await window.axios.put(`/api/admin/subcategories/${subcategoryId}`, {
                category_id: document.getElementById('category_id').value,
                name_ar: document.getElementById('name_ar').value.trim(),
                name_en: document.getElementById('name_en').value.trim(),
            });

            window.location.href = `/admin/subcategories/${subcategoryId}`;
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    async function loadCategories() {
        try {
            const response = await window.axios.get('/api/admin/categories?per_page=100');
            const categories = response.data.data || [];
            document.getElementById('category_id').innerHTML = '<option value="">Select category...</option>' +
                categories.map((category) => `<option value="${category.id}">${escapeHtml(category.name)} (${escapeHtml(category.type || '-')})</option>`).join('');
        } catch (error) {
            document.getElementById('category_id').innerHTML = '<option value="">Failed to load categories</option>';
        }
    }

    async function loadSubcategory() {
        try {
            const response = await window.axios.get(`/api/admin/subcategories/${subcategoryId}`);
            const subcategory = response.data.data;
            document.getElementById('name_ar').value = subcategory.name_ar || '';
            document.getElementById('name_en').value = subcategory.name_en || '';
            document.getElementById('category_id').value = subcategory.category_id || '';
            document.getElementById('subcategory-loading').classList.add('hidden');
            document.getElementById('subcategory-content').classList.remove('hidden');
        } catch (error) {
            document.getElementById('subcategory-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load subcategory.</p>';
        }
    }

    function clearErrors() {
        document.getElementById('subcategory-alert').classList.add('hidden');
        document.querySelectorAll('.form-error').forEach((element) => {
            element.classList.add('hidden');
            element.textContent = '';
        });
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            Object.entries(errors).forEach(([field, messages]) => {
                const errorElement = document.getElementById(`${field}-error`);
                if (errorElement) {
                    errorElement.textContent = Array.isArray(messages) ? messages[0] : messages;
                    errorElement.classList.remove('hidden');
                }
            });
        }

        document.getElementById('subcategory-alert').classList.remove('hidden');
        document.getElementById('subcategory-alert-message').textContent = error.response?.data?.message || 'Failed to update subcategory.';
    }

    function toggleLoading(isLoading) {
        document.getElementById('save-btn').disabled = isLoading;
        document.getElementById('save-spinner').classList.toggle('hidden', !isLoading);
        document.getElementById('save-btn-text').textContent = isLoading ? 'Saving...' : 'Save Changes';
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }
});
</script>
@endpush
