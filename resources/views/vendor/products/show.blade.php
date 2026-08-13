@extends('layouts.vendor')

@section('title', __('products.details_title'))
@section('page-title', __('products.details_title'))

@php
    $ui = [
        'loading' => __('products.loading_details'),
        'load_failed' => __('products.load_failed'),
        'refresh_try_again' => __('products.refresh_try_again'),
        'edit_product' => __('products.edit_product'),
        'no_primary_photo' => __('products.no_primary_photo'),
        'no_additional_photos' => __('products.no_additional_photos'),
        'no_description' => __('products.no_description'),
        'no_commercial_name' => __('products.no_commercial_name'),
        'no_subcategory' => __('products.no_subcategory'),
        'unnamed_product' => __('products.unnamed_product'),
        'photo' => __('products.photo'),
        'photo_single' => __('products.photo_single'),
        'photos' => __('products.photos'),
        'order' => __('products.order'),
        'sort' => __('products.sort'),
        'units' => __('products.units'),
        'badge_category' => __('products.badge_category'),
        'badge_subcategory' => __('products.badge_subcategory'),
        'badge_type' => __('products.badge_type'),
        'active' => __('common.active'),
        'inactive' => __('common.inactive'),
        'approved' => __('common.approved'),
        'pending' => __('common.pending'),
        'rejected' => __('common.rejected'),
        'yes' => __('common.yes'),
        'no' => __('common.no'),
        'not_available' => __('common.not_available'),
        'not_specified' => __('common.not_specified'),
    ];

    $sectionLabels = __('products.detail_labels');
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div id="product-loading" class="border border-slate-200 bg-white px-6 py-20 text-center dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-brand-500"></div>
        <p class="mt-4 text-sm font-medium text-slate-500">{{ __('products.loading_details') }}</p>
    </div>

    <div id="product-error" class="hidden border border-rose-200 bg-rose-50 px-6 py-14 text-center">
        <svg class="mx-auto h-12 w-12 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008zm9-3.758a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="mt-4 text-base font-bold text-rose-700">{{ __('products.load_failed') }}</p>
        <p class="mt-1 text-sm text-rose-600">{{ __('products.refresh_try_again') }}</p>
    </div>

    <div id="product-content" class="hidden space-y-6">
        <section class="card overflow-hidden">
            <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">{{ __('products.overview_badge') }}</p>
                            <h1 id="product-name" class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">—</h1>
                            <p id="product-commercial-name" class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">—</p>
                        </div>

                        <a id="edit-link" href="#" class="btn-secondary btn-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                            {{ __('products.edit_product') }}
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span id="product-category-badge" class="badge">{{ __('products.badge_category') }} —</span>
                        <span id="product-subcategory-badge" class="badge">{{ __('products.badge_subcategory') }} —</span>
                        <span id="product-type-badge" class="badge badge-brand">{{ __('products.badge_type') }} —</span>
                        <span id="product-status-badge" class="badge badge-warning">{{ __('common.pending') }}</span>
                        <span id="product-active-badge" class="badge badge-success">{{ __('common.active') }}</span>
                    </div>

                    <div class="metrics-row border-t border-gray-100 pt-4 dark:border-gray-800">
                        <div class="metrics-row-item">
                            <span class="metrics-row-label">{{ __('products.fields.price') }}</span>
                            <span id="product-price" class="metrics-row-value">—</span>
                        </div>
                        <div class="metrics-row-item">
                            <span class="metrics-row-label">{{ __('products.fields.quantity') }}</span>
                            <span id="product-quantity" class="metrics-row-value">—</span>
                        </div>
                        <div class="metrics-row-item">
                            <span class="metrics-row-label">{{ __('products.fields.registration') }}</span>
                            <span id="product-registration" class="metrics-row-value text-sm">—</span>
                        </div>
                        <div class="metrics-row-item">
                            <span class="metrics-row-label">{{ __('products.fields.barcodes') }}</span>
                            <span id="product-barcodes" class="metrics-row-value text-sm">—</span>
                        </div>
                    </div>
                </div>

                <div class="border-gray-100 dark:border-gray-800 lg:border-s lg:ps-6">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-gray-400 dark:text-gray-500">{{ __('products.vendor_snapshot_badge') }}</p>
                    <h2 class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ __('products.vendor_snapshot_title') }}</h2>

                    <div class="mt-4 space-y-2.5">
                        <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.category') }}</p>
                            <p id="product-category" class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">—</p>
                        </div>
                        <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.subcategory') }}</p>
                            <p id="product-subcategory" class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">—</p>
                        </div>
                        <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.product_type') }}</p>
                            <p id="product-type" class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">—</p>
                        </div>
                        <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.status') }}</p>
                            <p id="product-status" class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">—</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.85fr)]">
            <div class="space-y-6">
                <section class="overflow-hidden border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">{{ __('products.media_badge') }}</p>
                                <h2 class="mt-2 text-xl font-bold text-slate-900">{{ __('products.gallery_title') }}</h2>
                            </div>
                            <span id="photo-count" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">0</span>
                        </div>
                    </div>
                    <div class="space-y-5 p-6">
                        <div id="primary-photo-container" class="group relative flex aspect-[16/11] items-center justify-center overflow-hidden border border-slate-200 bg-slate-50">
                            <p class="text-sm font-medium text-slate-400">{{ __('products.no_primary_photo') }}</p>
                        </div>
                        <div id="product-photos" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"></div>
                    </div>
                </section>

                <section class="overflow-hidden border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">{{ __('products.description_badge') }}</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">{{ __('products.vendor_description_title') }}</h2>
                    </div>
                    <div class="px-6 py-5">
                        <p id="product-description" class="whitespace-pre-wrap text-sm leading-7 text-slate-600">—</p>
                    </div>
                </section>

                <section id="shared-section" class="overflow-hidden border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">{{ __('products.shared_badge') }}</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">{{ __('products.shared_title') }}</h2>
                    </div>
                    <div id="shared-grid" class="grid gap-4 p-6 md:grid-cols-2"></div>
                </section>

                <section id="agriculture-section" class="hidden overflow-hidden border border-emerald-200 bg-white">
                    <div class="border-b border-emerald-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-emerald-500">{{ __('products.agriculture_badge') }}</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">{{ __('products.agriculture_title') }}</h2>
                    </div>
                    <div id="agriculture-grid" class="grid gap-4 p-6 md:grid-cols-2"></div>
                </section>

                <section id="veterinary-section" class="hidden overflow-hidden border border-sky-200 bg-white">
                    <div class="border-b border-sky-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-sky-500">{{ __('products.veterinary_badge') }}</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">{{ __('products.veterinary_title') }}</h2>
                    </div>
                    <div id="veterinary-grid" class="grid gap-4 p-6 md:grid-cols-2"></div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="overflow-hidden border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">{{ __('products.summary_badge') }}</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">{{ __('products.summary_title') }}</h2>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">{{ __('products.fields.localized_name') }}</p>
                            <p id="product-name-secondary" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">{{ __('products.fields.commercial_name') }}</p>
                            <p id="product-commercial-name-secondary" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">{{ __('products.fields.price') }}</p>
                            <p id="product-price-secondary" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">{{ __('products.fields.quantity') }}</p>
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
    const ui = @json($ui);
    const labels = @json($sectionLabels);
    const statusLabels = {
        approved: @json(__('common.approved')),
        rejected: @json(__('common.rejected')),
        pending: @json(__('common.pending')),
        active: @json(__('common.active')),
        inactive: @json(__('common.inactive')),
    };

    const sectionConfigs = {
        shared: [
            [labels.commercial_name, 'commercial_name'],
            [labels.aliases, 'aliases'],
            [labels.barcodes, 'barcodes'],
            [labels.sku, 'sku'],
            [labels.manufacturer_name_ar, 'manufacturer_name_ar'],
            [labels.manufacturer_name_en, 'manufacturer_name_en'],
            [labels.brand_name_ar, 'brand_name_ar'],
            [labels.brand_name_en, 'brand_name_en'],
            [labels.country_of_origin, 'country_of_origin'],
            [labels.registration_number, 'registration_number'],
            [labels.registration_status, 'registration_status'],
            [labels.package_size, 'package_size'],
            [labels.package_unit, 'package_unit'],
            [labels.short_description, 'short_description'],
            [labels.approved_description, 'approved_description'],
            [labels.keywords, 'keywords'],
        ],
        agriculture: [
            [labels.agricultural_product_type, 'agricultural_product_type'],
            [labels.formulation, 'formulation'],
            [labels.pesticide_type, 'pesticide_type'],
            [labels.chemical_group, 'chemical_group'],
            [labels.target_pests, 'target_pests'],
            [labels.crop_name_ar, 'crop_name_ar'],
            [labels.crop_name_en, 'crop_name_en'],
            [labels.fertilizer_type, 'fertilizer_type'],
            [labels.micronutrients, 'micronutrients'],
            [labels.application_methods, 'application_methods'],
            [labels.application_rates, 'application_rates'],
            [labels.approved_uses, 'approved_uses'],
            [labels.storage_conditions, 'storage_conditions'],
            [labels.warnings, 'warnings'],
            [labels.ppe_requirements, 'ppe_requirements'],
            [labels.first_aid, 'first_aid'],
            [labels.compatibility, 'compatibility'],
            [labels.growth_stages, 'growth_stages'],
            [labels.fertilization_methods, 'fertilization_methods'],
            [labels.seed_treatment, 'seed_treatment'],
            [labels.disease_resistance, 'disease_resistance'],
            [labels.planting_windows, 'planting_windows'],
            [labels.seeding_rate, 'seeding_rate'],
            [labels.planting_depth, 'planting_depth'],
            [labels.plant_spacing, 'plant_spacing'],
            [labels.expected_yield, 'expected_yield'],
            [labels.target_crops, 'target_crops'],
            [labels.variety_name, 'variety_name'],
            [labels.variety_type, 'variety_type'],
            [labels.active_ingredients, 'active_ingredients'],
            [labels.environmental_hazards, 'environmental_hazards'],
            [labels.pre_harvest_intervals, 'pre_harvest_intervals'],
        ],
        veterinary: [
            [labels.concentration, 'concentration'],
            [labels.dosage_form, 'dosage_form'],
            [labels.treatment_duration, 'treatment_duration'],
            [labels.withdrawal_meat_days, 'withdrawal_meat_days'],
            [labels.withdrawal_milk_days, 'withdrawal_milk_days'],
            [labels.withdrawal_eggs_days, 'withdrawal_eggs_days'],
            [labels.routes_of_administration, 'routes_of_administration'],
            [labels.target_species, 'target_species'],
            [labels.indications, 'indications'],
            [labels.dosage_instructions, 'dosage_instructions'],
            [labels.contraindications, 'contraindications'],
            [labels.warnings, 'warnings'],
            [labels.adverse_reactions, 'adverse_reactions'],
            [labels.drug_interactions, 'drug_interactions'],
            [labels.storage_conditions, 'storage_conditions'],
            [labels.active_ingredients, 'active_ingredients'],
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
        const categoryName = product.category?.name || ui.not_available;
        const subcategoryName = product.subcategory?.name_ar || product.subcategory?.name_en || ui.no_subcategory;
        const productType = humanize(product.product_type) || ui.not_specified;
        const commercialName = product.shared_detail?.commercial_name || ui.no_commercial_name;
        const barcodes = Array.isArray(product.shared_detail?.barcodes) && product.shared_detail.barcodes.length
            ? product.shared_detail.barcodes.join(', ')
            : '—';
        const registration = product.shared_detail?.registration_number || product.shared_detail?.registration_status || '—';

        element('product-name').textContent = product.name || ui.unnamed_product;
        element('product-name-secondary').textContent = product.name || ui.unnamed_product;
        element('product-commercial-name').textContent = commercialName;
        element('product-commercial-name-secondary').textContent = commercialName;
        element('product-category').textContent = categoryName;
        element('product-subcategory').textContent = subcategoryName;
        element('product-type').textContent = productType;
        element('product-description').textContent = product.description || ui.no_description;
        const priceText = product.has_active_discount
            ? `${formatCurrency(product.discounted_price)} (${formatCurrency(product.price)})`
            : formatCurrency(product.price);
        element('product-price').textContent = priceText;
        element('product-price-secondary').textContent = priceText;
        element('product-quantity').textContent = `${Number(product.quantity || 0).toLocaleString()} ${ui.units}`;
        element('product-quantity-secondary').textContent = `${Number(product.quantity || 0).toLocaleString()} ${ui.units}`;
        element('product-registration').textContent = registration;
        element('product-barcodes').textContent = barcodes;
        element('product-status').textContent = statusLabels[product.status] || (product.is_active ? statusLabels.active : statusLabels.inactive);

        element('product-category-badge').textContent = `${ui.badge_category}: ${categoryName}`;
        element('product-subcategory-badge').textContent = `${ui.badge_subcategory}: ${subcategoryName}`;
        element('product-type-badge').textContent = `${ui.badge_type}: ${productType}`;

        updateStatusBadge(product.status || 'pending');
        updateActiveBadge(product.is_active);
    }

    function renderPhotos(product) {
        const photos = Array.isArray(product.photos) ? product.photos : [];
        const displayPhoto = photos.find((photo) => photo.image_type === 'primary')
            || photos.find((photo) => photo.is_primary)
            || photos[0];

        element('photo-count').textContent = `${photos.length} ${photos.length === 1 ? ui.photo_single : ui.photos}`;

        if (displayPhoto) {
            setPrimaryPhoto(displayPhoto.url, displayPhoto.image_type || 'primary', displayPhoto.sort_order || 1);
        } else if (product.first_photo_url) {
            setPrimaryPhoto(product.first_photo_url, 'primary', 1);
        } else {
            element('primary-photo-container').innerHTML = `<p class="text-sm font-medium text-slate-400">${escapeHtml(ui.no_primary_photo)}</p>`;
        }

        if (!photos.length) {
            element('product-photos').innerHTML = `<p class="col-span-full rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm font-medium text-slate-400">${escapeHtml(ui.no_additional_photos)}</p>`;
            return;
        }

        element('product-photos').innerHTML = photos.map((photo) => {
            const label = formatPhotoType(photo.image_type);

            return `
                <button
                    type="button"
                    class="overflow-hidden border border-slate-200 bg-white text-left transition-colors hover:border-brand-300 dark:border-gray-800 dark:bg-gray-900"
                    style="border-radius: var(--radius-card)"
                    onclick="window.showVendorProductPhoto(${JSON.stringify(photo.url)}, ${JSON.stringify(label)}, ${photo.sort_order || 1})"
                >
                    <div class="aspect-[4/3] overflow-hidden bg-gray-50 p-3 dark:bg-gray-800">
                        <img src="${escapeHtml(photo.url)}" alt="${escapeHtml(label)}" class="h-full w-full object-contain" style="border-radius: var(--radius-control)">
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-3 py-3 dark:border-gray-800">
                        <span class="badge ${photo.image_type === 'primary' ? 'badge-brand' : ''}">${escapeHtml(label)}</span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">${escapeHtml(ui.order)} ${photo.sort_order || 1}</span>
                    </div>
                </button>
            `;
        }).join('');
    }

    function setPrimaryPhoto(url, label, order) {
        element('primary-photo-container').innerHTML = `
            <button type="button" class="h-full w-full cursor-zoom-in" aria-label="${escapeHtml(ui.photo)}">
                <img src="${escapeHtml(url)}" alt="${escapeHtml(label)}" class="h-full w-full object-contain p-5">
            </button>
            <div class="badge" style="position: absolute; top: 1rem; inset-inline-start: 1rem;">${escapeHtml(label)} ${escapeHtml(ui.photo)}</div>
            <div class="badge" style="position: absolute; top: 1rem; inset-inline-end: 1rem;">${escapeHtml(ui.sort)} ${order}</div>
        `;

        element('primary-photo-container').querySelector('button').addEventListener('click', function () {
            window.openVendorProductPhoto(url);
        });
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
            <article class="border border-slate-200 bg-slate-50/80 p-4 dark:border-gray-800 dark:bg-gray-800/40" style="border-radius: var(--radius-card)">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">${escapeHtml(label)}</p>
                <div class="mt-2">${renderValue(value)}</div>
            </article>
        `).join('');
    }

    function renderValue(value) {
        if (Array.isArray(value)) {
            return value.length
                ? `<div class="flex flex-wrap gap-2">${value.map((item) => `<span class="badge">${formatInlineValue(item)}</span>`).join('')}</div>`
                : '<p class="text-sm text-slate-400 dark:text-gray-500">—</p>';
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
            return value ? ui.yes : ui.no;
        }

        return String(value);
    }

    function updateStatusBadge(status) {
        const classes = {
            approved: 'badge-success',
            rejected: 'badge-danger',
            pending: 'badge-warning',
        };

        element('product-status-badge').className = `badge ${classes[status] || classes.pending}`;
        element('product-status-badge').textContent = statusLabels[status] || statusLabels.pending;
    }

    function updateActiveBadge(isActive) {
        element('product-active-badge').className = `badge ${isActive ? 'badge-success' : 'badge-danger'}`;
        element('product-active-badge').textContent = isActive ? statusLabels.active : statusLabels.inactive;
    }

    function formatCurrency(value) {
        return `${Number(value || 0).toLocaleString()} SYP`;
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
            return ui.photo;
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

        const previouslyFocused = document.activeElement;

        const modal = document.createElement('div');
        modal.id = 'vendor-product-photo-modal';
        modal.className = 'fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/85 px-4 py-6 backdrop-blur-sm';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', ui.photo);
        modal.innerHTML = `
            <div class="relative max-h-[92vh] max-w-[92vw]">
                <img src="${escapeHtml(url)}" alt="${escapeHtml(ui.photo)}" class="max-h-[92vh] max-w-[92vw] bg-white object-contain" style="border-radius: var(--radius-feature); box-shadow: var(--shadow-3);">
                <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-900 transition-colors hover:bg-gray-100" style="position: absolute; top: 0.75rem; inset-inline-end: 0.75rem;" aria-label="Close photo preview">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        `;

        function close() {
            modal.remove();
            document.removeEventListener('keydown', onKeydown);
            if (previouslyFocused && previouslyFocused.focus) {
                previouslyFocused.focus();
            }
        }

        function onKeydown(event) {
            if (event.key === 'Escape') {
                close();
            }
        }

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('button')) {
                close();
            }
        });

        document.addEventListener('keydown', onKeydown);
        document.body.appendChild(modal);
        modal.querySelector('button').focus();
    };
});
</script>
@endpush
