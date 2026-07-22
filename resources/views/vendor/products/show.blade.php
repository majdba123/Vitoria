@extends('layouts.vendor')

@section('title', 'Product Details')
@section('page-title', 'Product Details')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div id="product-loading" class="rounded-[28px] border border-slate-200/80 bg-white/90 px-6 py-20 text-center shadow-sm shadow-slate-200/40 backdrop-blur">
        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-brand-500"></div>
        <p class="mt-4 text-sm font-medium text-slate-500">Loading product details...</p>
    </div>

    <div id="product-error" class="hidden rounded-[28px] border border-rose-200 bg-rose-50 px-6 py-14 text-center shadow-sm">
        <svg class="mx-auto h-12 w-12 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008zm9-3.758a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="mt-4 text-base font-bold text-rose-700">Failed to load product details.</p>
        <p class="mt-1 text-sm text-rose-600">Please refresh the page or try again later.</p>
    </div>

    <div id="product-content" class="hidden space-y-6">
        <section class="overflow-hidden rounded-[32px] border border-slate-200/80 bg-gradient-to-br from-slate-900 via-slate-800 to-brand-700 text-white shadow-xl shadow-slate-300/30">
            <div class="grid gap-8 px-6 py-7 lg:grid-cols-[minmax(0,1fr)_300px] lg:px-8">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-white/60">Product Overview</p>
                            <h1 id="product-name" class="text-3xl font-black tracking-tight sm:text-4xl">—</h1>
                            <p id="product-commercial-name" class="max-w-2xl text-sm text-white/70">—</p>
                        </div>

                        <a id="edit-link" href="#" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                            Edit Product
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span id="product-category-badge" class="inline-flex items-center rounded-full bg-white/12 px-4 py-2 text-xs font-semibold text-white/90 ring-1 ring-white/15">Category —</span>
                        <span id="product-subcategory-badge" class="inline-flex items-center rounded-full bg-white/12 px-4 py-2 text-xs font-semibold text-white/90 ring-1 ring-white/15">Subcategory —</span>
                        <span id="product-type-badge" class="inline-flex items-center rounded-full bg-brand-400/20 px-4 py-2 text-xs font-semibold text-brand-100 ring-1 ring-brand-200/25">Type —</span>
                        <span id="product-status-badge" class="inline-flex items-center rounded-full bg-amber-400/20 px-4 py-2 text-xs font-semibold text-amber-100 ring-1 ring-amber-200/30">Pending</span>
                        <span id="product-active-badge" class="inline-flex items-center rounded-full bg-emerald-400/20 px-4 py-2 text-xs font-semibold text-emerald-100 ring-1 ring-emerald-200/30">Active</span>
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
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/50">Registration</p>
                            <p id="product-registration" class="mt-2 text-sm font-semibold text-white/90">—</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/50">Barcodes</p>
                            <p id="product-barcodes" class="mt-2 text-sm font-semibold text-white/90">—</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/10 bg-white/10 p-5 backdrop-blur">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-white/55">Store Snapshot</p>
                    <h2 class="mt-2 text-lg font-bold text-white">Your product positioning</h2>

                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Category</p>
                            <p id="product-category" class="mt-2 text-sm font-semibold text-white/90">—</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Subcategory</p>
                            <p id="product-subcategory" class="mt-2 text-sm font-semibold text-white/90">—</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Product Type</p>
                            <p id="product-type" class="mt-2 text-sm font-semibold text-white/90">—</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/45">Status</p>
                            <p id="product-status" class="mt-2 text-sm font-semibold text-white/90">—</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

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
                        <h2 class="mt-2 text-xl font-black text-slate-900">What customers will read</h2>
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
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Commercial Name</p>
                            <p id="product-commercial-name-secondary" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Price</p>
                            <p id="product-price-secondary" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Quantity</p>
                            <p id="product-quantity-secondary" class="mt-2 text-sm font-semibold text-slate-900">—</p>
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
        const response = await window.axios.get('/api/vendor/products/{{ $productId }}');
        const product = response.data.data;

        fillSummary(product);
        renderPhotos(product);
        renderParameterSection('shared-section', 'shared-grid', product.shared_detail || {}, sectionConfigs.shared);
        renderParameterSection('agriculture-section', 'agriculture-grid', product.agricultural_detail || {}, sectionConfigs.agriculture);
        renderParameterSection('veterinary-section', 'veterinary-grid', product.veterinary_detail || {}, sectionConfigs.veterinary);

        element('edit-link').href = `/vendor/products/${product.id}/edit`;

        element('product-loading').classList.add('hidden');
        element('product-content').classList.remove('hidden');
    } catch (error) {
        console.error(error);
        element('product-loading').classList.add('hidden');
        element('product-error').classList.remove('hidden');
    }

    function fillSummary(product) {
        const categoryName = product.category?.name || 'Unassigned';
        const subcategoryName = product.subcategory?.name_ar || product.subcategory?.name_en || 'No subcategory';
        const productType = humanize(product.product_type) || 'Not specified';
        const commercialName = product.shared_detail?.commercial_name || 'No commercial name';
        const barcodes = Array.isArray(product.shared_detail?.barcodes) && product.shared_detail.barcodes.length
            ? product.shared_detail.barcodes.join(', ')
            : '—';
        const registration = product.shared_detail?.registration_number || product.shared_detail?.registration_status || '—';

        element('product-name').textContent = product.name || 'Unnamed product';
        element('product-name-secondary').textContent = product.name || 'Unnamed product';
        element('product-commercial-name').textContent = commercialName;
        element('product-commercial-name-secondary').textContent = commercialName;
        element('product-category').textContent = categoryName;
        element('product-subcategory').textContent = subcategoryName;
        element('product-type').textContent = productType;
        element('product-description').textContent = product.description || 'No description provided.';
        element('product-price').textContent = formatCurrency(product.price);
        element('product-price-secondary').textContent = formatCurrency(product.price);
        element('product-quantity').textContent = `${Number(product.quantity || 0).toLocaleString()} units`;
        element('product-quantity-secondary').textContent = `${Number(product.quantity || 0).toLocaleString()} units`;
        element('product-registration').textContent = registration;
        element('product-barcodes').textContent = barcodes;
        element('product-status').textContent = humanize(product.status || (product.is_active ? 'active' : 'inactive'));

        element('product-category-badge').textContent = `Category: ${categoryName}`;
        element('product-subcategory-badge').textContent = `Subcategory: ${subcategoryName}`;
        element('product-type-badge').textContent = `Type: ${productType}`;

        updateStatusBadge(product.status || 'pending');
        updateActiveBadge(product.is_active);
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
                    onclick="window.showVendorProductPhoto(${JSON.stringify(photo.url)}, ${JSON.stringify(label)}, ${photo.sort_order || 1})"
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
            <img src="${escapeHtml(url)}" alt="${escapeHtml(label)}" class="h-full w-full object-contain p-5 transition duration-500 group-hover:scale-[1.02] cursor-zoom-in" onclick="window.openVendorProductPhoto(${JSON.stringify(url)})">
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

    function updateStatusBadge(status) {
        const classes = {
            approved: 'bg-emerald-400/20 text-emerald-100 ring-emerald-200/30',
            rejected: 'bg-rose-400/20 text-rose-100 ring-rose-200/30',
            pending: 'bg-amber-400/20 text-amber-100 ring-amber-200/30',
        };

        element('product-status-badge').className = `inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold ring-1 ${classes[status] || classes.pending}`;
        element('product-status-badge').textContent = humanize(status || 'pending');
    }

    function updateActiveBadge(isActive) {
        element('product-active-badge').className = isActive
            ? 'inline-flex items-center rounded-full bg-emerald-400/20 px-4 py-2 text-xs font-semibold text-emerald-100 ring-1 ring-emerald-200/30'
            : 'inline-flex items-center rounded-full bg-rose-400/20 px-4 py-2 text-xs font-semibold text-rose-100 ring-1 ring-rose-200/30';
        element('product-active-badge').textContent = isActive ? 'Active' : 'Inactive';
    }

    function formatCurrency(value) {
        return `$${Number(value || 0).toFixed(2)}`;
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

    window.showVendorProductPhoto = function (url, label, order) {
        setPrimaryPhoto(url, label, order);
    };

    window.openVendorProductPhoto = function (url) {
        const existing = document.getElementById('vendor-product-photo-modal');
        if (existing) {
            existing.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'vendor-product-photo-modal';
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
