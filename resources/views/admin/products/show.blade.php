@extends('layouts.admin')

@section('title', 'Product Details — Vetora Admin')
@section('page-title', 'Product Details')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.products.index') }}" class="transition-colors hover:text-brand-600">Products</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="font-medium text-gray-900">Details</span>
    </nav>

    <x-alert type="error" id="edit-alert" />
    <x-alert type="success" id="edit-success" />

    <div id="show-loading" class="rounded-[28px] border border-slate-200/80 bg-white/90 px-6 py-20 text-center shadow-sm shadow-slate-200/40 backdrop-blur">
        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-brand-500"></div>
        <p class="mt-4 text-sm font-medium text-slate-500">Loading product details...</p>
    </div>

    <div id="show-error" class="hidden rounded-[28px] border border-rose-200 bg-rose-50 px-6 py-14 text-center shadow-sm">
        <svg class="mx-auto h-12 w-12 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008zm9-3.758a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="mt-4 text-base font-bold text-rose-700">Failed to load product details.</p>
        <p class="mt-1 text-sm text-rose-600">Please refresh the page or try again later.</p>
    </div>

    <div id="show-content" class="hidden space-y-6">
        <section class="overflow-hidden rounded-[32px] border border-slate-200/80 bg-gradient-to-br from-slate-900 via-slate-800 to-brand-700 text-white shadow-xl shadow-slate-300/30">
            <div class="grid gap-8 px-6 py-7 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-8">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-white/60">Product Overview</p>
                            <h1 id="product-name" class="text-3xl font-black tracking-tight sm:text-4xl">—</h1>
                            <p id="product-commercial-name" class="max-w-2xl text-sm text-white/70">—</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a id="reviews-link" href="#" class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                Reviews
                            </a>
                            <a id="edit-link" href="#" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                Edit Product
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span id="product-category-badge" class="inline-flex items-center rounded-full bg-white/12 px-4 py-2 text-xs font-semibold text-white/90 ring-1 ring-white/15">Category —</span>
                        <span id="product-subcategory-badge" class="inline-flex items-center rounded-full bg-white/12 px-4 py-2 text-xs font-semibold text-white/90 ring-1 ring-white/15">Subcategory —</span>
                        <span id="product-type-badge" class="inline-flex items-center rounded-full bg-brand-400/20 px-4 py-2 text-xs font-semibold text-brand-100 ring-1 ring-brand-200/25">Type —</span>
                        <span id="product-approval-status" class="inline-flex items-center rounded-full bg-amber-400/20 px-4 py-2 text-xs font-semibold text-amber-100 ring-1 ring-amber-200/30">Pending</span>
                        <span id="product-active-status" class="inline-flex items-center rounded-full bg-emerald-400/20 px-4 py-2 text-xs font-semibold text-emerald-100 ring-1 ring-emerald-200/30">Active</span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/50">Price</p>
                            <p id="product-price" class="mt-2 text-2xl font-black text-white">—</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/50">Quantity</p>
                            <p id="product-quantity" class="mt-2 text-2xl font-black text-white">—</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/50">Commission</p>
                            <p id="product-commission" class="mt-2 text-2xl font-black text-white">—</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/50">Created</p>
                            <p id="product-created" class="mt-2 text-sm font-semibold text-white/90">—</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/10 bg-white/10 p-5 backdrop-blur">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-white/55">Admin Controls</p>
                            <h2 class="mt-2 text-lg font-bold text-white">Approval and discount status</h2>
                        </div>
                        <span id="product-discount-status" class="inline-flex items-center rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/80 ring-1 ring-white/10">—</span>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div class="rounded-2xl bg-slate-950/20 p-4 ring-1 ring-white/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Approval Status</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <select id="product-status-select" class="hidden min-w-[160px] rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-sm text-white focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/15">
                                    <option value="pending" class="text-slate-900">Pending</option>
                                    <option value="approved" class="text-slate-900">Approved</option>
                                    <option value="rejected" class="text-slate-900">Rejected</option>
                                </select>
                                <button type="button" id="edit-status-btn" class="inline-flex items-center gap-2 rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                    Change
                                </button>
                                <button type="button" id="save-status-btn" class="hidden inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    Save
                                </button>
                                <button type="button" id="cancel-status-btn" class="hidden inline-flex items-center rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Discount Value</p>
                                <p id="product-discount-value" class="mt-2 text-base font-bold text-white">—</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Vendor</p>
                                <p id="product-vendor" class="mt-2 text-sm font-semibold text-white/90">—</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Discount Starts</p>
                                <p id="product-discount-start" class="mt-2 text-sm font-semibold text-white/90">—</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Discount Ends</p>
                                <p id="product-discount-end" class="mt-2 text-sm font-semibold text-white/90">—</p>
                            </div>
                        </div>

                        <a id="view-vendor-link" href="#" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View Vendor Profile
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div id="product-rejection-wrap" class="hidden rounded-[28px] border border-rose-200 bg-rose-50 px-6 py-5 shadow-sm shadow-rose-100/60">
            <p class="text-xs font-bold uppercase tracking-[0.3em] text-rose-500">Rejection Reason</p>
            <p id="product-rejection-reason" class="mt-3 text-sm font-semibold leading-7 text-rose-700">—</p>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.85fr)]">
            <div class="space-y-6">
                <section class="overflow-hidden rounded-[30px] border border-slate-200/80 bg-white shadow-sm shadow-slate-200/50">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">Media</p>
                                <h2 class="mt-2 text-xl font-black text-slate-900">Product gallery</h2>
                            </div>
                            <span id="photo-count" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">0 photos</span>
                        </div>
                    </div>
                    <div class="space-y-5 p-6">
                        <div id="primary-photo-container" class="group relative flex aspect-[16/11] items-center justify-center overflow-hidden rounded-[24px] border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-slate-100">
                            <p class="text-sm font-medium text-slate-400">No primary photo available.</p>
                        </div>
                        <div id="product-photos" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"></div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[30px] border border-slate-200/80 bg-white shadow-sm shadow-slate-200/50">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">Description</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900">What this product is about</h2>
                    </div>
                    <div class="px-6 py-5">
                        <p id="product-description" class="whitespace-pre-wrap text-sm leading-7 text-slate-600">—</p>
                    </div>
                </section>

                <section id="shared-section" class="overflow-hidden rounded-[30px] border border-slate-200/80 bg-white shadow-sm shadow-slate-200/50">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">Shared Profile</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900">Core product parameters</h2>
                    </div>
                    <div id="shared-grid" class="grid gap-4 p-6 md:grid-cols-2"></div>
                </section>

                <section id="agriculture-section" class="hidden overflow-hidden rounded-[30px] border border-emerald-200/80 bg-white shadow-sm shadow-emerald-100/60">
                    <div class="border-b border-emerald-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-emerald-500">Agricultural Profile</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900">Agriculture-specific details</h2>
                    </div>
                    <div id="agriculture-grid" class="grid gap-4 p-6 md:grid-cols-2"></div>
                </section>

                <section id="veterinary-section" class="hidden overflow-hidden rounded-[30px] border border-sky-200/80 bg-white shadow-sm shadow-sky-100/60">
                    <div class="border-b border-sky-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-sky-500">Veterinary Profile</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900">Veterinary-specific details</h2>
                    </div>
                    <div id="veterinary-grid" class="grid gap-4 p-6 md:grid-cols-2"></div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="overflow-hidden rounded-[30px] border border-slate-200/80 bg-white shadow-sm shadow-slate-200/50">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">Quick Summary</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900">Snapshot</h2>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Localized Name</p>
                            <p id="product-name-secondary" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Category</p>
                            <p id="product-category" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Subcategory</p>
                            <p id="product-subcategory" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Product Type</p>
                            <p id="product-type" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const productId = '{{ $productId }}';
    const element = (id) => document.getElementById(id);

    const sectionConfigs = {
        shared: [
            ['Commercial Name', 'commercial_name'],
            ['Aliases', 'aliases'],
            ['Barcodes', 'barcodes'],
            ['SKU', 'sku'],
            ['Manufacturer (AR)', 'manufacturer_name_ar'],
            ['Manufacturer (EN)', 'manufacturer_name_en'],
            ['Brand (AR)', 'brand_name_ar'],
            ['Brand (EN)', 'brand_name_en'],
            ['Country Of Origin', 'country_of_origin'],
            ['Registration Number', 'registration_number'],
            ['Registration Status', 'registration_status'],
            ['Package Size', 'package_size'],
            ['Package Unit', 'package_unit'],
            ['Short Description', 'short_description'],
            ['Approved Description', 'approved_description'],
            ['Keywords', 'keywords'],
        ],
        agriculture: [
            ['Agricultural Product Type', 'agricultural_product_type'],
            ['Formulation', 'formulation'],
            ['Pesticide Type', 'pesticide_type'],
            ['Chemical Group', 'chemical_group'],
            ['Target Pests', 'target_pests'],
            ['Crop Name (AR)', 'crop_name_ar'],
            ['Crop Name (EN)', 'crop_name_en'],
            ['Crop Variety', 'crop_variety'],
            ['Fertilizer Type', 'fertilizer_type'],
            ['NPK Ratio', 'npk_ratio'],
            ['Micronutrients', 'micronutrients'],
            ['Application Methods', 'application_methods'],
            ['Application Rates', 'application_rates'],
            ['Approved Uses', 'approved_uses'],
            ['Storage Conditions', 'storage_conditions'],
            ['Warnings', 'warnings'],
            ['PPE Requirements', 'ppe_requirements'],
            ['First Aid', 'first_aid'],
            ['Compatibility', 'compatibility'],
            ['Growth Stages', 'growth_stages'],
            ['Fertilization Methods', 'fertilization_methods'],
            ['Seed Treatment', 'seed_treatment'],
            ['Disease Resistance', 'disease_resistance'],
            ['Planting Windows', 'planting_windows'],
            ['Seeding Rate', 'seeding_rate'],
            ['Planting Depth', 'planting_depth'],
            ['Plant Spacing', 'plant_spacing'],
            ['Expected Yield', 'expected_yield'],
            ['Target Crops', 'target_crops'],
            ['Variety Name', 'variety_name'],
            ['Variety Type', 'variety_type'],
            ['Active Ingredients', 'active_ingredients'],
            ['Environmental Hazards', 'environmental_hazards'],
            ['Pre-Harvest Intervals', 'pre_harvest_intervals'],
        ],
        veterinary: [
            ['Concentration', 'concentration'],
            ['Dosage Form', 'dosage_form'],
            ['Treatment Duration', 'treatment_duration'],
            ['Withdrawal Meat Days', 'withdrawal_meat_days'],
            ['Withdrawal Milk Days', 'withdrawal_milk_days'],
            ['Withdrawal Eggs Days', 'withdrawal_eggs_days'],
            ['Routes Of Administration', 'routes_of_administration'],
            ['Target Species', 'target_species'],
            ['Indications', 'indications'],
            ['Dosage Instructions', 'dosage_instructions'],
            ['Contraindications', 'contraindications'],
            ['Warnings', 'warnings'],
            ['Adverse Reactions', 'adverse_reactions'],
            ['Drug Interactions', 'drug_interactions'],
            ['Storage Conditions', 'storage_conditions'],
            ['Active Ingredients', 'active_ingredients'],
        ],
    };

    try {
        const response = await window.axios.get(`/api/admin/products/${productId}`);
        const product = response.data.data;
        let currentStatus = product.status || 'pending';

        fillSummary(product);
        renderPhotos(product);
        renderParameterSection('shared-section', 'shared-grid', product.shared_detail || {}, sectionConfigs.shared);
        renderParameterSection('agriculture-section', 'agriculture-grid', product.agricultural_detail || {}, sectionConfigs.agriculture);
        renderParameterSection('veterinary-section', 'veterinary-grid', product.veterinary_detail || {}, sectionConfigs.veterinary);

        element('edit-link').href = `/admin/products/${productId}/edit`;
        element('reviews-link').href = `/admin/products/${productId}/reviews`;

        if (product.vendor?.id) {
            element('view-vendor-link').href = `/admin/vendors/${product.vendor.id}`;
            element('view-vendor-link').classList.remove('hidden');
        } else {
            element('view-vendor-link').classList.add('hidden');
        }

        element('edit-status-btn').addEventListener('click', function () {
            element('product-status-select').value = currentStatus;
            element('product-status-select').classList.remove('hidden');
            element('edit-status-btn').classList.add('hidden');
            element('save-status-btn').classList.remove('hidden');
            element('cancel-status-btn').classList.remove('hidden');
        });

        element('cancel-status-btn').addEventListener('click', function () {
            element('product-status-select').classList.add('hidden');
            element('edit-status-btn').classList.remove('hidden');
            element('save-status-btn').classList.add('hidden');
            element('cancel-status-btn').classList.add('hidden');
        });

        element('save-status-btn').addEventListener('click', async function () {
            const button = this;
            const nextStatus = element('product-status-select').value;
            const originalContent = button.innerHTML;

            button.disabled = true;
            button.innerHTML = '<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Saving';

            try {
                const updateResponse = await window.axios.patch(`/api/admin/products/${productId}/status`, {
                    status: nextStatus,
                });

                currentStatus = nextStatus;
                updateStatusBadge(nextStatus);
                syncRejectionReason(nextStatus, updateResponse.data.data?.rejection_reason || product.rejection_reason || '');
                element('cancel-status-btn').click();
                showAlert('edit-success', updateResponse.data.message || 'Status updated successfully.');
            } catch (error) {
                showAlert('edit-alert', error.response?.data?.message || 'Failed to update status.');
            } finally {
                button.disabled = false;
                button.innerHTML = originalContent;
            }
        });

        element('show-loading').classList.add('hidden');
        element('show-content').classList.remove('hidden');
    } catch (error) {
        console.error(error);
        element('show-loading').classList.add('hidden');
        element('show-error').classList.remove('hidden');
    }

    function fillSummary(product) {
        const commercialName = product.shared_detail?.commercial_name || 'No commercial name';
        const categoryName = product.category?.name || 'Unassigned';
        const subcategoryName = product.subcategory?.name_ar || product.subcategory?.name_en || 'No subcategory';
        const productType = humanize(product.product_type) || 'Not specified';
        const vendorName = product.vendor?.store_name || 'No vendor';
        const ownerName = product.vendor?.user?.name ? ` / ${product.vendor.user.name}` : '';

        element('product-name').textContent = product.name || 'Unnamed product';
        element('product-name-secondary').textContent = product.name || 'Unnamed product';
        element('product-commercial-name').textContent = commercialName;
        element('product-category').textContent = categoryName;
        element('product-subcategory').textContent = subcategoryName;
        element('product-type').textContent = productType;
        element('product-description').textContent = product.description || 'No description provided.';
        element('product-price').textContent = formatCurrency(product.price);
        element('product-quantity').textContent = `${Number(product.quantity || 0).toLocaleString()} units`;
        element('product-created').textContent = formatDateOnly(product.created_at);
        element('product-commission').textContent = product.category?.commission ? `${Number(product.category.commission).toFixed(2)}%` : '—';
        element('product-vendor').textContent = `${vendorName}${ownerName}`;
        element('product-discount-value').textContent = product.discount_percentage ? `${Number(product.discount_percentage).toFixed(2)}%` : 'No discount';
        element('product-discount-start').textContent = formatDateOnly(product.discount_starts_at);
        element('product-discount-end').textContent = formatDateOnly(product.discount_ends_at);

        element('product-category-badge').textContent = `Category: ${categoryName}`;
        element('product-subcategory-badge').textContent = `Subcategory: ${subcategoryName}`;
        element('product-type-badge').textContent = `Type: ${productType}`;

        updateActiveBadge(product.is_active);
        updateStatusBadge(product.status || 'pending');
        updateDiscountStatusBadge(product.discount_status);
        syncRejectionReason(product.status, product.rejection_reason);
    }

    function renderPhotos(product) {
        const photos = Array.isArray(product.photos) ? product.photos : [];
        const displayPhoto = photos.find((photo) => photo.image_type === 'primary')
            || photos.find((photo) => photo.is_primary)
            || photos[0];

        element('photo-count').textContent = `${photos.length} photo${photos.length === 1 ? '' : 's'}`;

        if (displayPhoto) {
            setPrimaryPhoto(displayPhoto.url, displayPhoto.image_type || 'primary', displayPhoto.sort_order || 1);
        } else if (product.first_photo_url) {
            setPrimaryPhoto(product.first_photo_url, 'primary', 1);
        }

        if (!photos.length) {
            element('product-photos').innerHTML = '<p class="col-span-full rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm font-medium text-slate-400">No additional photos available.</p>';
            return;
        }

        element('product-photos').innerHTML = photos.map((photo) => {
            const label = formatPhotoType(photo.image_type);
            const badgeClass = photo.image_type === 'primary'
                ? 'border-brand-200 bg-brand-50 text-brand-700'
                : 'border-slate-200 bg-white text-slate-600';

            return `
                <button
                    type="button"
                    class="group overflow-hidden rounded-[22px] border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    onclick="window.showProductPhoto(${JSON.stringify(photo.url)}, ${JSON.stringify(label)}, ${photo.sort_order || 1})"
                >
                    <div class="aspect-[4/3] overflow-hidden bg-gradient-to-br from-slate-50 via-white to-slate-100 p-3">
                        <img src="${escapeHtml(photo.url)}" alt="${escapeHtml(label)}" class="h-full w-full rounded-2xl object-contain transition duration-300 group-hover:scale-105">
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-3 py-3">
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.18em] ${badgeClass}">${escapeHtml(label)}</span>
                        <span class="text-xs font-semibold text-slate-500">Order ${photo.sort_order || 1}</span>
                    </div>
                </button>
            `;
        }).join('');
    }

    function setPrimaryPhoto(url, label, order) {
        element('primary-photo-container').innerHTML = `
            <img src="${escapeHtml(url)}" alt="${escapeHtml(label)}" class="h-full w-full object-contain p-5 transition duration-500 group-hover:scale-[1.02] cursor-zoom-in" onclick="window.openProductPhoto(${JSON.stringify(url)})">
            <div class="absolute left-4 top-4 inline-flex items-center rounded-full bg-slate-950/80 px-3 py-1.5 text-xs font-semibold text-white shadow-lg">${escapeHtml(label)} photo</div>
            <div class="absolute right-4 top-4 inline-flex items-center rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-lg">Sort ${order}</div>
        `;
    }

    function renderParameterSection(sectionId, gridId, payload, config) {
        const entries = config
            .map(([label, key]) => [label, payload?.[key]])
            .filter(([, value]) => hasValue(value));

        const section = element(sectionId);
        const grid = element(gridId);

        if (!entries.length) {
            section.classList.add('hidden');
            grid.innerHTML = '';
            return;
        }

        section.classList.remove('hidden');
        grid.innerHTML = entries.map(([label, value]) => `
            <article class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4 shadow-sm shadow-slate-100/50">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">${escapeHtml(label)}</p>
                <div class="mt-3">${renderValue(value)}</div>
            </article>
        `).join('');
    }

    function renderValue(value) {
        if (Array.isArray(value)) {
            return value.length
                ? `<div class="flex flex-wrap gap-2">${value.map((item) => `<span class="inline-flex items-center rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">${formatInlineValue(item)}</span>`).join('')}</div>`
                : '<p class="text-sm text-slate-400">—</p>';
        }

        if (typeof value === 'object' && value !== null) {
            return `
                <div class="space-y-2 rounded-2xl bg-white p-3 ring-1 ring-slate-200">
                    ${Object.entries(value)
                        .filter(([, nestedValue]) => hasValue(nestedValue))
                        .map(([nestedKey, nestedValue]) => `
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-2 last:border-b-0 last:pb-0">
                                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">${escapeHtml(humanize(nestedKey))}</span>
                                <span class="text-sm font-semibold text-slate-700 text-right">${formatInlineValue(nestedValue)}</span>
                            </div>
                        `).join('')}
                </div>
            `;
        }

        return `<p class="text-sm font-semibold leading-7 text-slate-700">${escapeHtml(String(value))}</p>`;
    }

    function formatInlineValue(value) {
        if (Array.isArray(value)) {
            return escapeHtml(value.map(normalizeValue).join(', '));
        }

        if (typeof value === 'object' && value !== null) {
            const pairs = Object.entries(value)
                .filter(([, nestedValue]) => hasValue(nestedValue))
                .map(([nestedKey, nestedValue]) => `${humanize(nestedKey)}: ${normalizeValue(nestedValue)}`);

            return escapeHtml(pairs.join(' | '));
        }

        return escapeHtml(normalizeValue(value));
    }

    function normalizeValue(value) {
        if (value === null || value === undefined) {
            return '';
        }

        if (typeof value === 'boolean') {
            return value ? 'Yes' : 'No';
        }

        return String(value);
    }

    function updateActiveBadge(isActive) {
        element('product-active-status').className = isActive
            ? 'inline-flex items-center rounded-full bg-emerald-400/20 px-4 py-2 text-xs font-semibold text-emerald-100 ring-1 ring-emerald-200/30'
            : 'inline-flex items-center rounded-full bg-rose-400/20 px-4 py-2 text-xs font-semibold text-rose-100 ring-1 ring-rose-200/30';
        element('product-active-status').textContent = isActive ? 'Active' : 'Inactive';
    }

    function updateStatusBadge(status) {
        const classes = {
            approved: 'bg-emerald-400/20 text-emerald-100 ring-emerald-200/30',
            rejected: 'bg-rose-400/20 text-rose-100 ring-rose-200/30',
            pending: 'bg-amber-400/20 text-amber-100 ring-amber-200/30',
        };

        element('product-approval-status').className = `inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold ring-1 ${classes[status] || classes.pending}`;
        element('product-approval-status').textContent = humanize(status || 'pending');
    }

    function updateDiscountStatusBadge(status) {
        const classMap = {
            active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            expired: 'bg-rose-50 text-rose-700 ring-rose-200',
            pending: 'bg-amber-50 text-amber-700 ring-amber-200',
        };

        element('product-discount-status').className = `inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ${classMap[status] || 'bg-slate-100 text-slate-600 ring-slate-200'}`;
        element('product-discount-status').textContent = humanize(status || 'none');
    }

    function syncRejectionReason(status, reason) {
        if (status === 'rejected') {
            element('product-rejection-reason').textContent = reason || 'No rejection reason was provided.';
            element('product-rejection-wrap').classList.remove('hidden');
            return;
        }

        element('product-rejection-wrap').classList.add('hidden');
        element('product-rejection-reason').textContent = '—';
    }

    function showAlert(id, message) {
        const wrapper = element(id);
        const messageElement = element(`${id}-message`);

        if (!wrapper || !messageElement) {
            return;
        }

        messageElement.textContent = message;
        wrapper.classList.remove('hidden');
        setTimeout(() => wrapper.classList.add('hidden'), 5000);
    }

    function formatCurrency(value) {
        return `$${Number(value || 0).toFixed(2)}`;
    }

    function formatDateOnly(value) {
        if (!value) {
            return '—';
        }

        const normalized = typeof value === 'string' ? value.replace(' ', 'T') : value;
        const date = new Date(normalized);

        if (Number.isNaN(date.getTime())) {
            return String(value).slice(0, 10);
        }

        return date.toLocaleDateString();
    }

    function hasValue(value) {
        if (value === null || value === undefined) {
            return false;
        }

        if (typeof value === 'string') {
            return value.trim() !== '';
        }

        if (Array.isArray(value)) {
            return value.length > 0;
        }

        if (typeof value === 'object') {
            return Object.values(value).some((nestedValue) => hasValue(nestedValue));
        }

        return true;
    }

    function humanize(value) {
        return String(value || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (character) => character.toUpperCase());
    }

    function formatPhotoType(type) {
        if (!type) {
            return 'Photo';
        }

        return humanize(type);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    window.showProductPhoto = function (url, label, order) {
        setPrimaryPhoto(url, label, order);
    };

    window.openProductPhoto = function (url) {
        const existing = document.getElementById('product-photo-modal');
        if (existing) {
            existing.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'product-photo-modal';
        modal.className = 'fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/85 px-4 py-6 backdrop-blur-sm';
        modal.innerHTML = `
            <div class="relative max-h-[92vh] max-w-[92vw]">
                <img src="${escapeHtml(url)}" alt="Product photo" class="max-h-[92vh] max-w-[92vw] rounded-[28px] bg-white object-contain shadow-2xl">
                <button type="button" class="absolute right-3 top-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-900 shadow-lg transition hover:scale-105" aria-label="Close photo preview">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        `;

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('button')) {
                modal.remove();
            }
        });

        document.body.appendChild(modal);
    };
});
</script>
@endpush
