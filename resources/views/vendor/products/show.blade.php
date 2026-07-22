@extends('layouts.vendor')

@section('title', 'Product Details')
@section('page-title', 'Product Details')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div id="product-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading product...</p>
    </div>

    <div id="product-content" class="hidden space-y-5">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 id="product-name" class="text-2xl font-bold text-gray-900">-</h2>
                        <p id="product-commercial-name" class="mt-1 text-sm text-gray-500">-</p>
                    </div>
                    <a id="edit-link" href="#" class="btn-primary btn-sm">Edit</a>
                </div>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-400">Category</p>
                    <p id="product-category" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-400">Subcategory</p>
                    <p id="product-subcategory" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-400">Product Type</p>
                    <p id="product-type" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-400">Status</p>
                    <p id="product-status" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-400">Price</p>
                    <p id="product-price" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-400">Quantity</p>
                    <p id="product-quantity" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-400">Barcodes</p>
                    <p id="product-barcodes" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-400">Registration</p>
                    <p id="product-registration" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
            </div>
            <div class="card-body border-t border-gray-100">
                <p class="text-xs uppercase text-gray-400">Description</p>
                <p id="product-description" class="mt-2 text-sm text-gray-700">-</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Shared Parameters</h3>
            </div>
            <div id="shared-parameters" class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
        </div>

        <div id="agriculture-card" class="hidden card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Agricultural Parameters</h3>
            </div>
            <div id="agriculture-parameters" class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
        </div>

        <div id="veterinary-card" class="hidden card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Veterinary Parameters</h3>
            </div>
            <div id="veterinary-parameters" class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    try {
        const response = await window.axios.get('/api/vendor/products/{{ $productId }}');
        const p = response.data.data;

        document.getElementById('edit-link').href = `/vendor/products/${p.id}/edit`;
        document.getElementById('product-name').textContent = p.name || 'Product';
        document.getElementById('product-commercial-name').textContent = p.shared_detail?.commercial_name || '-';
        document.getElementById('product-category').textContent = p.category?.name || 'Unassigned';
        document.getElementById('product-subcategory').textContent = p.subcategory?.name_ar || p.subcategory?.name_en || '-';
        document.getElementById('product-type').textContent = p.product_type || '-';
        document.getElementById('product-description').textContent = p.description || 'No description.';
        document.getElementById('product-price').textContent = `$${parseFloat(p.price || 0).toFixed(2)}`;
        document.getElementById('product-quantity').textContent = p.quantity || 0;
        document.getElementById('product-status').textContent = p.status ? p.status.charAt(0).toUpperCase() + p.status.slice(1) : (p.is_active ? 'Active' : 'Inactive');
        document.getElementById('product-barcodes').textContent = Array.isArray(p.shared_detail?.barcodes) && p.shared_detail.barcodes.length
            ? p.shared_detail.barcodes.join(', ')
            : '-';
        document.getElementById('product-registration').textContent = p.shared_detail?.registration_number || p.shared_detail?.registration_status || '-';

        renderParameterCards('shared-parameters', {
            'Commercial Name': p.shared_detail?.commercial_name,
            'Manufacturer AR': p.shared_detail?.manufacturer_name_ar,
            'Manufacturer EN': p.shared_detail?.manufacturer_name_en,
            'Brand AR': p.shared_detail?.brand_name_ar,
            'Brand EN': p.shared_detail?.brand_name_en,
            'Country Of Origin': p.shared_detail?.country_of_origin,
            'Package Size': p.shared_detail?.package_size,
            'Package Unit': p.shared_detail?.package_unit,
            'Keywords': p.shared_detail?.keywords,
            'Aliases': p.shared_detail?.aliases,
            'Approved Description': p.shared_detail?.approved_description,
            'Short Description': p.shared_detail?.short_description,
        });

        const agriDetail = p.agricultural_detail || {};
        const vetDetail = p.veterinary_detail || {};

        if (Object.keys(agriDetail).length > 0) {
            document.getElementById('agriculture-card').classList.remove('hidden');
            renderParameterCards('agriculture-parameters', {
                'Agricultural Product Type': agriDetail.agricultural_product_type,
                'Formulation': agriDetail.formulation,
                'Pesticide Type': agriDetail.pesticide_type,
                'Chemical Group': agriDetail.chemical_group,
                'Fertilizer Type': agriDetail.fertilizer_type,
                'Variety Name': agriDetail.variety_name,
                'Variety Type': agriDetail.variety_type,
                'Active Ingredients': agriDetail.active_ingredients,
                'Target Crops': agriDetail.target_crops,
                'Approved Uses': agriDetail.approved_uses,
                'Application Methods': agriDetail.application_methods,
                'Application Rates': agriDetail.application_rates,
                'Warnings': agriDetail.warnings,
                'Growth Stages': agriDetail.growth_stages,
            });
        }

        if (Object.keys(vetDetail).length > 0) {
            document.getElementById('veterinary-card').classList.remove('hidden');
            renderParameterCards('veterinary-parameters', {
                'Concentration': vetDetail.concentration,
                'Dosage Form': vetDetail.dosage_form,
                'Treatment Duration': vetDetail.treatment_duration,
                'Target Species': vetDetail.target_species,
                'Routes Of Administration': vetDetail.routes_of_administration,
                'Indications': vetDetail.indications,
                'Warnings': vetDetail.warnings,
                'Withdrawal Meat Days': vetDetail.withdrawal_meat_days,
                'Withdrawal Milk Days': vetDetail.withdrawal_milk_days,
                'Withdrawal Eggs Days': vetDetail.withdrawal_eggs_days,
            });
        }

        document.getElementById('product-loading').classList.add('hidden');
        document.getElementById('product-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('product-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load product.</p>';
    }

    function renderParameterCards(containerId, entries) {
        const container = document.getElementById(containerId);
        const items = Object.entries(entries).filter(([, value]) => {
            if (value === null || value === undefined) {
                return false;
            }
            if (typeof value === 'string' && value.trim() === '') {
                return false;
            }
            if (Array.isArray(value) && value.length === 0) {
                return false;
            }

            return true;
        });

        if (items.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-400">No parameters available.</p>';
            return;
        }

        container.innerHTML = items.map(([label, value]) => `
            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-xs uppercase text-gray-400">${escapeHtml(label)}</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">${formatValue(value)}</p>
            </div>
        `).join('');
    }

    function formatValue(value) {
        if (Array.isArray(value)) {
            return escapeHtml(value.map((item) => typeof item === 'object' ? JSON.stringify(item) : String(item)).join(', '));
        }

        if (typeof value === 'object' && value !== null) {
            return escapeHtml(JSON.stringify(value));
        }

        return escapeHtml(String(value));
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }
});
</script>
@endpush
