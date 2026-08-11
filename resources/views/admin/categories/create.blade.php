@extends('layouts.admin')

@section('title', 'Create Category — Vetora Admin')
@section('page-title', 'Create Category')

@section('content')
<div class="space-y-4">
    <div class="card">
        <div class="card-body">
            <form id="category-form" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="form-label">Category Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" class="form-input" required>
                        <p class="form-error" id="name-error"></p>
                    </div>

                    <div>
                        <label for="type" class="form-label">Category Type <span class="text-red-500">*</span></label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="">Select category type</option>
                            <option value="agriculture">Agriculture</option>
                            <option value="veterinary">Veterinary</option>
                        </select>
                        <p class="form-error" id="type-error"></p>
                    </div>

                    <div>
                        <label for="commission" class="form-label">Commission (%)</label>
                        <input type="number" id="commission" name="commission" class="form-input" step="0.01" min="0" max="100" placeholder="e.g. 10.00" value="0">
                        <p class="mt-1 text-xs text-gray-500">Percentage commission charged on products in this category (0–100).</p>
                        <p class="form-error" id="commission-error"></p>
                    </div>

                    <div>
                        <label for="logo" class="form-label">Category Image</label>
                        <input type="file" id="logo" name="logo" accept="image/*" class="form-input">
                        <p class="mt-1 text-xs text-gray-500">Upload one image that will be used everywhere for this category. Max size: 4MB.</p>
                        <p class="form-error" id="logo-error"></p>
                    </div>

                    <div class="flex gap-2 pt-4">
                        <a href="{{ route('admin.categories.index') }}" class="btn-secondary btn-sm flex-1">Cancel</a>
                        <button type="submit" id="create-btn" class="btn-primary btn-sm flex-1">
                            <span id="create-btn-text">Create Category</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<x-alert type="error" id="category-alert" />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('category-form');
    const alert = document.getElementById('category-alert');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);
        const formData = new FormData(form);

        try {
            const res = await window.axios.post('/api/admin/categories', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            window.location.href = '/admin/categories';
        } catch (e) {
            handleErrors(e);
        } finally {
            toggleLoading(false);
        }
    });

    function clearErrors() {
        alert.classList.add('hidden');
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

        document.getElementById('category-alert-message').textContent = error.response?.data?.message || 'Failed to create category.';
        alert.classList.remove('hidden');
    }

    function toggleLoading(isLoading) {
        document.getElementById('create-btn').disabled = isLoading;
        document.getElementById('create-btn-text').textContent = isLoading ? 'Creating...' : 'Create Category';
    }
});
</script>
@endpush
