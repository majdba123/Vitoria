@extends('layouts.admin')

@section('title', __('products.details_title').' — Vetora Admin')
@section('page-title', __('products.details_title'))

@php
    $ui = [
        'loading' => __('products.loading_details'),
        'load_failed' => __('products.load_failed'),
        'refresh_try_again' => __('products.refresh_try_again'),
        'reviews' => __('products.reviews'),
        'edit_product' => __('products.edit_product'),
        'view_vendor_profile' => __('products.view_vendor_profile'),
        'change_status' => __('products.change_status'),
        'save_status' => __('products.save_status'),
        'saving_status' => __('products.saving_status'),
        'status_updated' => __('products.status_updated'),
        'status_update_failed' => __('products.status_update_failed'),
        'no_primary_photo' => __('products.no_primary_photo'),
        'no_additional_photos' => __('products.no_additional_photos'),
        'no_description' => __('products.no_description'),
        'no_commercial_name' => __('products.no_commercial_name'),
        'no_subcategory' => __('products.no_subcategory'),
        'no_vendor' => __('products.no_vendor'),
        'unnamed_product' => __('products.unnamed_product'),
        'no_rejection_reason' => __('products.no_rejection_reason'),
        'no_discount' => __('products.discount_value_empty'),
        'not_available' => __('common.not_available'),
        'not_specified' => __('common.not_specified'),
        'active' => __('common.active'),
        'inactive' => __('common.inactive'),
        'approved' => __('common.approved'),
        'pending' => __('common.pending'),
        'rejected' => __('common.rejected'),
        'yes' => __('common.yes'),
        'no' => __('common.no'),
        'photo' => __('products.photo'),
        'photo_single' => __('products.photo_single'),
        'photos' => __('products.photos'),
        'order' => __('products.order'),
        'sort' => __('products.sort'),
        'units' => __('products.units'),
        'badge_category' => __('products.badge_category'),
        'badge_subcategory' => __('products.badge_subcategory'),
        'badge_type' => __('products.badge_type'),
    ];

    $sectionLabels = __('products.detail_labels');
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.products.index') }}" class="transition-colors hover:text-brand-600">{{ __('admin.products') }}</a>
        <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="font-medium text-gray-900">{{ __('products.details_breadcrumb') }}</span>
    </nav>

    <x-alert type="error" id="edit-alert" />
    <x-alert type="success" id="edit-success" />

    <div id="show-loading" class="border border-slate-200 bg-white px-6 py-20 text-center dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-brand-500"></div>
        <p class="mt-4 text-sm font-medium text-slate-500">{{ __('products.loading_details') }}</p>
    </div>

    <div id="show-error" class="hidden border border-rose-200 bg-rose-50 px-6 py-14 text-center">
        <svg class="mx-auto h-12 w-12 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008zm9-3.758a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="mt-4 text-base font-bold text-rose-700">{{ __('products.load_failed') }}</p>
        <p class="mt-1 text-sm text-rose-600">{{ __('products.refresh_try_again') }}</p>
    </div>

    <div id="show-content" class="hidden space-y-6">
        <section class="card overflow-hidden">
            <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">{{ __('products.overview_badge') }}</p>
                            <h1 id="product-name" class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">—</h1>
                            <p id="product-commercial-name" class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">—</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a id="reviews-link" href="#" class="btn-secondary btn-sm">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ __('products.reviews') }}
                            </a>
                            <a id="edit-link" href="#" class="btn-primary btn-sm">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                {{ __('products.edit_product') }}
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span id="product-category-badge" class="badge">{{ __('products.badge_category') }} —</span>
                        <span id="product-subcategory-badge" class="badge">{{ __('products.badge_subcategory') }} —</span>
                        <span id="product-type-badge" class="badge badge-brand">{{ __('products.badge_type') }} —</span>
                        <span id="product-approval-status" class="badge badge-warning">{{ __('common.pending') }}</span>
                        <span id="product-active-status" class="badge badge-success">{{ __('common.active') }}</span>
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
                            <span class="metrics-row-label">{{ __('products.fields.commission') }}</span>
                            <span id="product-commission" class="metrics-row-value">—</span>
                        </div>
                        <div class="metrics-row-item">
                            <span class="metrics-row-label">{{ __('products.fields.created') }}</span>
                            <span id="product-created" class="metrics-row-value text-sm">—</span>
                        </div>
                    </div>
                </div>

                <div class="border-gray-100 dark:border-gray-800 lg:border-s lg:ps-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-gray-400 dark:text-gray-500">{{ __('products.admin_controls_badge') }}</p>
                            <h2 class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ __('products.admin_controls_title') }}</h2>
                        </div>
                        <span id="product-discount-status" class="badge">—</span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.approval_status') }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <select id="product-status-select" class="form-select hidden" style="min-width: 9rem;">
                                    <option value="pending">{{ __('common.pending') }}</option>
                                    <option value="approved">{{ __('common.approved') }}</option>
                                    <option value="rejected">{{ __('common.rejected') }}</option>
                                </select>
                                <button type="button" id="edit-status-btn" class="btn-secondary btn-xs">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                    {{ __('products.change_status') }}
                                </button>
                                <button type="button" id="save-status-btn" class="hidden btn-primary btn-xs">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    {{ __('products.save_status') }}
                                </button>
                                <button type="button" id="cancel-status-btn" class="hidden btn-ghost btn-xs">
                                    {{ __('common.cancel') }}
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-2.5 sm:grid-cols-2">
                            <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.discount_value') }}</p>
                                <p id="product-discount-value" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">—</p>
                            </div>
                            <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.vendor') }}</p>
                                <p id="product-vendor" class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">—</p>
                            </div>
                            <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.discount_starts') }}</p>
                                <p id="product-discount-start" class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">—</p>
                            </div>
                            <div class="border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60" style="border-radius: var(--radius-control)">
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('products.fields.discount_ends') }}</p>
                                <p id="product-discount-end" class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">—</p>
                            </div>
                        </div>

                        <a id="view-vendor-link" href="#" class="btn-secondary btn-sm w-full">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ __('products.view_vendor_profile') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div id="product-rejection-wrap" class="alert-shell alert-error hidden">
            <p class="text-xs font-bold uppercase tracking-[0.24em]">{{ __('products.rejection_badge') }}</p>
            <p id="product-rejection-reason" class="mt-2 text-sm font-semibold leading-7">—</p>
        </div>

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
                        <h2 class="mt-2 text-xl font-bold text-slate-900">{{ __('products.description_title') }}</h2>
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
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">{{ __('products.fields.category') }}</p>
                            <p id="product-category" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">{{ __('products.fields.subcategory') }}</p>
                            <p id="product-subcategory" class="mt-2 text-sm font-semibold text-slate-900">—</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">{{ __('products.fields.product_type') }}</p>
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
            button.innerHTML = `<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> ${escapeHtml(ui.saving_status)}`;

            try {
                const updateResponse = await window.axios.patch(`/api/admin/products/${productId}/status`, { status: nextStatus });
                currentStatus = nextStatus;
                updateStatusBadge(nextStatus);
                syncRejectionReason(nextStatus, updateResponse.data.data?.rejection_reason || product.rejection_reason || '');
                element('cancel-status-btn').click();
                showAlert('edit-success', updateResponse.data.message || ui.status_updated);
            } catch (error) {
                showAlert('edit-alert', error.response?.data?.message || ui.status_update_failed);
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
        const commercialName = product.shared_detail?.commercial_name || ui.no_commercial_name;
        const categoryName = product.category?.name || ui.not_available;
        const subcategoryName = product.subcategory?.name_ar || product.subcategory?.name_en || ui.no_subcategory;
        const productType = humanize(product.product_type) || ui.not_specified;
        const vendorName = product.vendor?.store_name || ui.no_vendor;
        const ownerName = product.vendor?.user?.name ? ` / ${product.vendor.user.name}` : '';

        element('product-name').textContent = product.name || ui.unnamed_product;
        element('product-name-secondary').textContent = product.name || ui.unnamed_product;
        element('product-commercial-name').textContent = commercialName;
        element('product-category').textContent = categoryName;
        element('product-subcategory').textContent = subcategoryName;
        element('product-type').textContent = productType;
        element('product-description').textContent = product.description || ui.no_description;
        element('product-price').textContent = formatCurrency(product.price);
        element('product-quantity').textContent = `${Number(product.quantity || 0).toLocaleString()} ${ui.units}`;
        element('product-created').textContent = formatDateOnly(product.created_at);
        element('product-commission').textContent = product.category?.commission ? `${Number(product.category.commission).toFixed(2)}%` : '—';
        element('product-vendor').textContent = `${vendorName}${ownerName}`;
        element('product-discount-value').textContent = product.discount_percentage ? `${Number(product.discount_percentage).toFixed(2)}%` : ui.no_discount;
        element('product-discount-start').textContent = formatDateOnly(product.discount_starts_at);
        element('product-discount-end').textContent = formatDateOnly(product.discount_ends_at);

        element('product-category-badge').textContent = `${ui.badge_category}: ${categoryName}`;
        element('product-subcategory-badge').textContent = `${ui.badge_subcategory}: ${subcategoryName}`;
        element('product-type-badge').textContent = `${ui.badge_type}: ${productType}`;

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
                    onclick="window.showProductPhoto(${JSON.stringify(photo.url)}, ${JSON.stringify(label)}, ${photo.sort_order || 1})"
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
            <img src="${escapeHtml(url)}" alt="${escapeHtml(label)}" class="h-full w-full object-contain p-5 cursor-zoom-in" onclick="window.openProductPhoto(${JSON.stringify(url)})">
            <div class="badge" style="position: absolute; top: 1rem; inset-inline-start: 1rem;">${escapeHtml(label)} ${escapeHtml(ui.photo)}</div>
            <div class="badge" style="position: absolute; top: 1rem; inset-inline-end: 1rem;">${escapeHtml(ui.sort)} ${order}</div>
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

    function updateActiveBadge(isActive) {
        element('product-active-status').className = `badge ${isActive ? 'badge-success' : 'badge-danger'}`;
        element('product-active-status').textContent = isActive ? statusLabels.active : statusLabels.inactive;
    }

    function updateStatusBadge(status) {
        const classes = {
            approved: 'badge-success',
            rejected: 'badge-danger',
            pending: 'badge-warning',
        };

        element('product-approval-status').className = `badge ${classes[status] || classes.pending}`;
        element('product-approval-status').textContent = statusLabels[status] || statusLabels.pending;
    }

    function updateDiscountStatusBadge(status) {
        const classMap = {
            active: 'badge-success',
            expired: 'badge-danger',
            pending: 'badge-warning',
        };

        element('product-discount-status').className = `badge ${classMap[status] || ''}`;
        element('product-discount-status').textContent = statusLabels[status] || ui.not_available;
    }

    function syncRejectionReason(status, reason) {
        if (status === 'rejected') {
            element('product-rejection-reason').textContent = reason || ui.no_rejection_reason;
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
        return `${Number(value || 0).toLocaleString()} SYP`;
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
            return ui.photo;
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
                <img src="${escapeHtml(url)}" alt="${escapeHtml(ui.photo)}" class="max-h-[92vh] max-w-[92vw] bg-white object-contain" style="border-radius: var(--radius-feature); box-shadow: var(--shadow-3);">
                <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-900 transition-colors hover:bg-gray-100" style="position: absolute; top: 0.75rem; inset-inline-end: 0.75rem;" aria-label="${escapeHtml(@json(__('common.close')))}">
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
