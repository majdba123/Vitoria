@extends('layouts.admin')

@section('title', __('admin.dashboard') . ' - Vetora')
@section('page-title', __('admin.dashboard'))

@section('content')
    <div class="space-y-6">
        <section class="dashboard-page-header">
            <div class="min-w-0">
                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">{{ __('admin.badge') }}</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-900 dark:text-white sm:text-3xl">{{ __('admin.dashboard_title') }}</h2>
                <p class="dashboard-section-copy max-w-3xl">{{ __('admin.dashboard_copy') }}</p>
            </div>

            <div class="dashboard-page-header-actions">
                <a href="{{ route('admin.vendors.create') }}" class="btn-primary btn-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    {{ __('admin.add_vendor') }}
                </a>
                <a href="{{ route('admin.users.create') }}" class="btn-secondary btn-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    {{ __('admin.add_user') }}
                </a>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h3 class="dashboard-section-title">{{ __('admin.stats_section_title') }}</h3>
                <p class="dashboard-section-copy">{{ __('admin.stats_section_copy') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-7">
                <div class="stat-tile">
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('admin.total_users') }}</p>
                            <p id="stat-users" class="mt-3 text-2xl font-black text-gray-900 dark:text-white">—</p>
                        </div>
                        <span class="icon-chip bg-blue-500/10 text-blue-700 dark:text-blue-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        </span>
                    </div>
                </div>

                <div class="stat-tile">
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('admin.total_vendors') }}</p>
                            <p id="stat-vendors" class="mt-3 text-2xl font-black text-gray-900 dark:text-white">—</p>
                        </div>
                        <span class="icon-chip">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72" /></svg>
                        </span>
                    </div>
                </div>

                <div class="stat-tile">
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('admin.total_syndicates') }}</p>
                            <p id="stat-syndicates" class="mt-3 text-2xl font-black text-gray-900 dark:text-white">—</p>
                        </div>
                        <span class="icon-chip bg-cyan-500/10 text-cyan-700 dark:text-cyan-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.941 3.479a8.985 8.985 0 01-4.686 0m4.686 0V19.5m-4.686-.5a9.094 9.094 0 01-3.741-.479 3 3 0 014.682-2.72m-.941 3.479V19.5m0 0a3 3 0 11-6 0m6 0a3 3 0 00-6 0m6 0h.008v.008H12v-.008zM12 8.25a3 3 0 100-6 3 3 0 000 6z" /></svg>
                        </span>
                    </div>
                </div>

                <div class="stat-tile">
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('admin.active_vendors') }}</p>
                            <p id="stat-active-vendors" class="mt-3 text-2xl font-black text-emerald-600 dark:text-emerald-400">—</p>
                        </div>
                        <span class="icon-chip bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                    </div>
                </div>

                <div class="stat-tile">
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('admin.inactive_vendors') }}</p>
                            <p id="stat-inactive-vendors" class="mt-3 text-2xl font-black text-rose-600 dark:text-rose-400">—</p>
                        </div>
                        <span class="icon-chip bg-rose-500/10 text-rose-700 dark:text-rose-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        </span>
                    </div>
                </div>

                <div class="stat-tile">
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('admin.total_products') }}</p>
                            <p id="stat-products" class="mt-3 text-2xl font-black text-gray-900 dark:text-white">—</p>
                        </div>
                        <span class="icon-chip bg-violet-500/10 text-violet-700 dark:text-violet-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                        </span>
                    </div>
                </div>

                <div class="stat-tile">
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ __('admin.active_products') }}</p>
                            <p id="stat-active-products" class="mt-3 text-2xl font-black text-emerald-600 dark:text-emerald-400">—</p>
                        </div>
                        <span class="icon-chip bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h3 class="dashboard-section-title">{{ __('admin.insights_section_title') }}</h3>
                <p class="dashboard-section-copy">{{ __('admin.insights_section_copy') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.vendors_by_type_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.vendors_by_type_copy') }}</p>
                        </div>
                    </div>
                    <div id="vendors-by-type" class="card-body grid gap-3 sm:grid-cols-3"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.categories_by_type_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.categories_by_type_copy') }}</p>
                        </div>
                    </div>
                    <div id="categories-by-type" class="card-body grid gap-3 sm:grid-cols-2"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.syndicates_by_type_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.syndicates_by_type_copy') }}</p>
                        </div>
                    </div>
                    <div id="syndicates-by-type" class="card-body grid gap-3 sm:grid-cols-2"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.recent_syndicate_agents_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.recent_syndicate_agents_copy') }}</p>
                        </div>
                    </div>
                    <div id="recent-syndicate-agents" class="card-body space-y-3"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.products_by_type_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.products_by_type_copy') }}</p>
                        </div>
                    </div>
                    <div id="products-by-category-type" class="card-body grid gap-3 sm:grid-cols-2"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.monthly_product_growth_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.monthly_product_growth_copy') }}</p>
                        </div>
                    </div>
                    <div id="monthly-product-growth" class="card-body space-y-3"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.top_vendors_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.top_vendors_copy') }}</p>
                        </div>
                    </div>
                    <div id="top-vendors-by-product-count" class="card-body space-y-3"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.most_selected_categories_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.most_selected_categories_copy') }}</p>
                        </div>
                    </div>
                    <div id="most-selected-categories" class="card-body space-y-3"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.categories_without_products_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.categories_without_products_copy') }}</p>
                        </div>
                    </div>
                    <div id="categories-with-no-products" class="card-body space-y-3"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.categories_without_vendors_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.categories_without_vendors_copy') }}</p>
                        </div>
                    </div>
                    <div id="categories-with-no-vendors" class="card-body space-y-3"></div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-[1.3fr_1fr]">
            <div class="card">
                <div class="panel-heading">
                    <div>
                        <h3 class="dashboard-section-title">{{ __('admin.vendors_by_category_title') }}</h3>
                        <p class="dashboard-section-copy">{{ __('admin.vendors_by_category_copy') }}</p>
                    </div>
                    <a href="{{ route('admin.vendors.index') }}" class="btn-secondary btn-xs">{{ __('admin.view_vendors') }}</a>
                </div>
                <div class="card-body">
                    <div id="vendor-category-stats" class="space-y-3">
                        <div class="status-note text-center">{{ __('admin.loading_category_stats') }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="panel-heading">
                    <div>
                        <h3 class="dashboard-section-title">{{ __('admin.recent_products_title') }}</h3>
                        <p class="dashboard-section-copy">{{ __('admin.recent_products_copy') }}</p>
                    </div>
                    <a href="{{ route('admin.products.index') }}" class="btn-secondary btn-xs">{{ __('admin.show_all') }}</a>
                </div>
                <div class="card-body">
                    <div id="recent-products" class="space-y-3">
                        <div class="status-note text-center">{{ __('admin.loading_products') }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h3 class="dashboard-section-title">{{ __('admin.recent_activity_title') }}</h3>
                <p class="dashboard-section-copy">{{ __('admin.recent_activity_copy') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.recent_vendors_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.recent_vendors_copy') }}</p>
                        </div>
                    </div>
                    <div id="recent-vendors" class="card-body space-y-3"></div>
                </div>

                <div class="card">
                    <div class="panel-heading">
                        <div>
                            <h4 class="dashboard-section-title">{{ __('admin.recent_syndicate_agents_title') }}</h4>
                            <p class="dashboard-section-copy">{{ __('admin.recent_syndicate_agents_copy') }}</p>
                        </div>
                    </div>
                    <div id="recent-syndicate-agents-summary" class="card-body space-y-3"></div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h3 class="dashboard-section-title">{{ __('admin.actions_section_title') }}</h3>
                <p class="dashboard-section-copy">{{ __('admin.actions_section_copy') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <a href="{{ route('admin.vendors.index') }}" class="list-panel group">
                    <div class="flex items-center gap-4">
                        <span class="icon-chip">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ __('admin.manage_vendors_title') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.manage_vendors_copy') }}</p>
                        </div>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>

                <a href="{{ route('admin.users.index') }}" class="list-panel group">
                    <div class="flex items-center gap-4">
                        <span class="icon-chip bg-blue-500/10 text-blue-700 dark:text-blue-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ __('admin.manage_users_title') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.manage_users_copy') }}</p>
                        </div>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>

                <a href="{{ route('admin.products.index') }}" class="list-panel group">
                    <div class="flex items-center gap-4">
                        <span class="icon-chip bg-violet-500/10 text-violet-700 dark:text-violet-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ __('admin.manage_products_title') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.manage_products_copy') }}</p>
                        </div>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @php
        $adminDashboardStrings = [
            'noProductsYet' => __('admin.no_products_yet'),
            'noVendorCategoriesYet' => __('admin.no_vendor_categories_yet'),
            'noMetricData' => __('admin.no_metric_data'),
            'noCategoryPreferencesYet' => __('admin.no_category_preferences_yet'),
            'noVendorsYet' => __('admin.no_vendors_yet'),
            'noSyndicateAgentsYet' => __('admin.no_syndicate_agents_yet'),
            'noTopVendorsYet' => __('admin.no_top_vendors_yet'),
            'noGapData' => __('admin.no_gap_data'),
            'noGrowthData' => __('admin.no_growth_data'),
            'dashboardLoadFailed' => __('admin.dashboard_load_failed'),
            'retryLabel' => __('common.refresh'),
            'typeAgriculture' => __('admin.type_agriculture'),
            'typeVeterinary' => __('admin.type_veterinary'),
            'typeBoth' => __('admin.type_both'),
            'statusActive' => __('admin.status_active'),
            'statusInactive' => __('admin.status_inactive'),
            'statusPending' => __('admin.status_pending'),
            'statusApproved' => __('admin.status_approved'),
            'vendorsTotalLabel' => __('admin.vendors_total_label'),
            'vendorsCountLabel' => __('admin.vendors_count_label'),
            'productsCountLabel' => __('admin.products_count_label'),
        ];
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const adminDashboardI18n = @json($adminDashboardStrings);

            // Per-endpoint state so a single failed request doesn't blank out data
            // that other, successful requests already provided (e.g. the overview
            // endpoint can supply vendor/product totals even if those endpoints fail).
            let usersData = null;
            let vendorsData = null;
            let productsData = null;
            let overviewData = null;

            async function fetchUsers() {
                try {
                    const res = await window.axios.get('/api/admin/users?page=1');
                    usersData = res.data;
                } catch (error) {
                    usersData = null;
                }
                renderUsersStat();
            }

            async function fetchVendors() {
                try {
                    const res = await window.axios.get('/api/admin/vendors?page=1');
                    vendorsData = res.data;
                } catch (error) {
                    vendorsData = null;
                }
                renderVendorStats();
            }

            async function fetchProducts() {
                try {
                    const res = await window.axios.get('/api/admin/products?page=1&per_page=5');
                    productsData = res.data;
                } catch (error) {
                    productsData = null;
                }
                renderProductStats();
                renderRecentProductsSection();
            }

            async function fetchCategoryStats() {
                try {
                    const res = await window.axios.get('/api/admin/dashboard/vendor-category-stats');
                    renderVendorCategoryStats(res.data.data || []);
                } catch (error) {
                    renderSectionFailure('vendor-category-stats', adminDashboardI18n.dashboardLoadFailed, fetchCategoryStats);
                }
            }

            async function fetchOverview() {
                try {
                    const res = await window.axios.get('/api/admin/dashboard/overview');
                    overviewData = res.data.data || {};
                    renderOverview(overviewData);
                } catch (error) {
                    overviewData = null;
                }
                renderVendorStats();
                renderProductStats();
                renderRecentProductsSection();
            }

            function getRecentProducts() {
                if (overviewData?.recent_products) {
                    return overviewData.recent_products;
                }
                if (productsData?.data) {
                    return productsData.data;
                }

                return null;
            }

            function renderUsersStat() {
                if (!usersData) {
                    renderStatFailure('stat-users', fetchUsers);
                    return;
                }

                document.getElementById('stat-users').textContent = usersData.meta?.total ?? '0';
            }

            function renderVendorStats() {
                const overviewTotal = overviewData?.total_vendors;
                const overviewActive = overviewData?.active_vendors;
                const overviewInactive = overviewData?.inactive_vendors;

                if (overviewTotal === undefined && !vendorsData) {
                    const retry = () => Promise.all([fetchVendors(), fetchOverview()]);
                    renderStatFailure('stat-vendors', retry);
                    renderStatFailure('stat-active-vendors', retry);
                    renderStatFailure('stat-inactive-vendors', retry);
                    return;
                }

                const vendors = vendorsData?.data || [];
                const pageActiveVendors = vendors.filter((vendor) => vendor.is_active).length;
                const pageInactiveVendors = vendors.length - pageActiveVendors;

                document.getElementById('stat-vendors').textContent = overviewTotal ?? vendorsData?.meta?.total ?? 0;
                document.getElementById('stat-active-vendors').textContent = overviewActive ?? pageActiveVendors;
                document.getElementById('stat-inactive-vendors').textContent = overviewInactive ?? pageInactiveVendors;

                if (overviewData) {
                    document.getElementById('stat-syndicates').textContent = overviewData.total_syndicates ?? 0;
                } else {
                    renderStatFailure('stat-syndicates', fetchOverview);
                }
            }

            function renderProductStats() {
                const overviewTotal = overviewData?.total_products;
                const overviewActive = overviewData?.active_products;

                if (overviewTotal === undefined && !productsData) {
                    const retry = () => Promise.all([fetchProducts(), fetchOverview()]);
                    renderStatFailure('stat-products', retry);
                    renderStatFailure('stat-active-products', retry);
                    return;
                }

                const products = getRecentProducts() || [];
                document.getElementById('stat-products').textContent = overviewTotal ?? productsData?.meta?.total ?? 0;
                document.getElementById('stat-active-products').textContent = overviewActive ?? products.filter((product) => product.is_active).length;
            }

            function renderRecentProductsSection() {
                const products = getRecentProducts();

                if (products === null) {
                    renderSectionFailure('recent-products', adminDashboardI18n.dashboardLoadFailed, () => Promise.all([fetchProducts(), fetchOverview()]));
                    return;
                }

                renderRecentProducts(products);
            }

            function renderStatFailure(elementId, retryFn) {
                const el = document.getElementById(elementId);
                if (!el) {
                    return;
                }

                el.innerHTML = `<span class="block text-sm font-bold text-red-500 dark:text-red-300">${esc(adminDashboardI18n.dashboardLoadFailed)}</span><button type="button" class="stat-retry-btn mt-1 text-xs font-bold text-brand-600 underline dark:text-brand-300">${esc(adminDashboardI18n.retryLabel)}</button>`;
                el.querySelector('.stat-retry-btn').addEventListener('click', () => retryFn());
            }

            function renderSectionFailure(containerId, message, retryFn) {
                const container = document.getElementById(containerId);
                if (!container) {
                    return;
                }

                container.innerHTML = `<div class="rounded-2xl border px-4 py-6 text-center text-sm font-medium text-red-500 border-red-200/70 bg-red-50/70 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300"><p>${esc(message)}</p><button type="button" class="section-retry-btn mt-2 font-bold underline">${esc(adminDashboardI18n.retryLabel)}</button></div>`;
                container.querySelector('.section-retry-btn').addEventListener('click', () => retryFn());
            }

            await Promise.allSettled([
                fetchUsers(),
                fetchVendors(),
                fetchProducts(),
                fetchCategoryStats(),
                fetchOverview(),
            ]);

            function renderRecentProducts(products) {
                const container = document.getElementById('recent-products');

                if (!products.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noProductsYet);
                    return;
                }

                container.innerHTML = products.map((product) => `
                    <a href="/admin/products/${product.id}" class="list-panel group">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="icon-chip bg-violet-500/10 text-violet-700 dark:text-violet-300">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-gray-900 dark:text-white">${esc(product.name)}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${esc(product.category?.name || product.status || '')}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="badge ${product.is_active ? 'badge-success' : 'badge-danger'}">${product.is_active ? esc(adminDashboardI18n.statusActive) : esc(adminDashboardI18n.statusInactive)}</span>
                            <svg class="h-4 w-4 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                        </div>
                    </a>
                `).join('');
            }

            function renderVendorCategoryStats(rows) {
                const container = document.getElementById('vendor-category-stats');

                if (!rows.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noVendorCategoriesYet);
                    return;
                }

                container.innerHTML = rows.map((row) => {
                    const total = Number(row.total_vendors || 0);
                    const active = Number(row.active_vendors || 0);
                    const pending = Number(row.pending_vendors || 0);
                    const inactive = Number(row.inactive_vendors || 0);
                    const activeWidth = total > 0 ? Math.round((active / total) * 100) : 0;
                    const pendingWidth = total > 0 ? Math.round((pending / total) * 100) : 0;
                    const inactiveWidth = Math.max(0, 100 - activeWidth - pendingWidth);
                    const url = row.id ? `/admin/vendors?category_id=${encodeURIComponent(row.id)}` : '/admin/vendors';

                    return `
                        <a href="${url}" class="list-panel">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-gray-900 dark:text-white">${esc(row.name)}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${esc((adminDashboardI18n.vendorsTotalLabel || '').replace(':count', String(total)))}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="badge badge-success">${active} ${esc(adminDashboardI18n.statusActive)}</span>
                                        <span class="badge badge-warning">${pending} ${esc(adminDashboardI18n.statusPending)}</span>
                                        <span class="badge badge-danger">${inactive} ${esc(adminDashboardI18n.statusInactive)}</span>
                                    </div>
                                </div>
                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="flex h-full w-full">
                                        <div class="bg-emerald-500" style="width:${activeWidth}%"></div>
                                        <div class="bg-amber-500" style="width:${pendingWidth}%"></div>
                                        <div class="bg-rose-500" style="width:${inactiveWidth}%"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    `;
                }).join('');
            }

            function renderOverview(overview) {
                renderMetricTiles('vendors-by-type', overview.vendors_by_type || [], 'vendor');
                renderMetricTiles('syndicates-by-type', overview.syndicates_by_type || [], 'syndicate');
                renderMetricTiles('categories-by-type', overview.categories_by_type || [], 'category');
                renderMetricTiles('products-by-category-type', overview.products_by_category_type || [], 'product');
                renderMostSelectedCategories(overview.most_selected_categories || []);
                renderRecentVendors(overview.recent_vendor_registrations || []);
                renderRecentSyndicates(overview.recent_syndicate_agents || []);
                renderTopVendors(overview.top_vendors_by_product_count || []);
                renderCategoryGapList('categories-with-no-products', overview.categories_with_no_products || [], 'products_count');
                renderCategoryGapList('categories-with-no-vendors', overview.categories_with_no_vendors || [], 'vendors_count');
                renderMonthlyProductGrowth(overview.monthly_product_growth || []);
            }

            function renderMetricTiles(id, rows, noun) {
                const container = document.getElementById(id);
                if (!container) {
                    return;
                }

                if (!rows.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noMetricData);
                    return;
                }

                container.innerHTML = rows.map((row) => `
                    <a href="${metricTileUrl(noun, row.type)}" class="list-panel">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">${esc(typeLabel(row.type, row.label))}</p>
                            <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">${Number(row.total || 0)}</p>
                        </div>
                    </a>
                `).join('');
            }

            function metricTileUrl(noun, type) {
                if (noun === 'vendor') {
                    return `/admin/vendors?business_type=${encodeURIComponent(type)}`;
                }
                if (noun === 'syndicate') {
                    return `/admin/syndicates?type=${encodeURIComponent(type)}`;
                }
                if (noun === 'product') {
                    return `/admin/products?category_type=${encodeURIComponent(type)}`;
                }

                return `/admin/categories?type=${encodeURIComponent(type)}`;
            }

            function renderMostSelectedCategories(rows) {
                const container = document.getElementById('most-selected-categories');

                if (!rows.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noCategoryPreferencesYet);
                    return;
                }

                container.innerHTML = rows.map((row) => `
                    <a href="/admin/vendors?category_id=${encodeURIComponent(row.id)}" class="list-panel">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-gray-900 dark:text-white">${esc(row.name)}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${esc(typeLabel(row.type, row.type_label))}</p>
                        </div>
                        <span class="badge badge-brand">${esc((adminDashboardI18n.vendorsCountLabel || '').replace(':count', String(Number(row.vendors_count || 0))))}</span>
                    </a>
                `).join('');
            }

            function renderRecentVendors(rows) {
                const container = document.getElementById('recent-vendors');
                if (!container) {
                    return;
                }

                if (!rows.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noVendorsYet);
                    return;
                }

                container.innerHTML = rows.map((vendor) => `
                    <a href="/admin/vendors/${vendor.id}" class="list-panel">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-gray-900 dark:text-white">${esc(vendor.store_name)}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${esc(vendor.user?.name || '')} · ${esc(businessTypeLabel(vendor.business_type, vendor.business_type_label))}</p>
                        </div>
                        <span class="badge ${vendor.status === 'pending' ? 'badge-warning' : (vendor.is_active ? 'badge-success' : 'badge-danger')}">${esc(statusLabel(vendor.status || (vendor.is_active ? 'active' : 'inactive')))}</span>
                    </a>
                `).join('');
            }

            function renderRecentSyndicates(rows) {
                const container = document.getElementById('recent-syndicate-agents');
                const summaryContainer = document.getElementById('recent-syndicate-agents-summary');

                if (!rows.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noSyndicateAgentsYet);
                    if (summaryContainer) {
                        summaryContainer.innerHTML = emptyState(adminDashboardI18n.noSyndicateAgentsYet);
                    }
                    return;
                }

                const markup = rows.map((syndicate) => `
                    <a href="/admin/syndicates/${syndicate.id}" class="list-panel">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-gray-900 dark:text-white">${esc(syndicate.name)}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${esc(syndicate.user?.email || '')} · ${esc(typeLabel(syndicate.type, syndicate.type_label))}</p>
                        </div>
                        <span class="badge ${syndicate.status === 'active' ? 'badge-success' : 'badge-danger'}">${esc(statusLabel(syndicate.status))}</span>
                    </a>
                `).join('');

                container.innerHTML = markup;

                if (summaryContainer) {
                    summaryContainer.innerHTML = markup;
                }
            }

            function renderTopVendors(rows) {
                const container = document.getElementById('top-vendors-by-product-count');

                if (!rows.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noTopVendorsYet);
                    return;
                }

                container.innerHTML = rows.map((vendor) => `
                    <a href="/admin/vendors/${vendor.id}" class="list-panel">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-gray-900 dark:text-white">${esc(vendor.store_name)}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${esc(businessTypeLabel(vendor.business_type, vendor.business_type_label))}</p>
                        </div>
                        <span class="badge badge-brand">${esc((adminDashboardI18n.productsCountLabel || '').replace(':count', String(Number(vendor.products_count || 0))))}</span>
                    </a>
                `).join('');
            }

            function renderCategoryGapList(id, rows, countKey) {
                const container = document.getElementById(id);

                if (!rows.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noGapData);
                    return;
                }

                container.innerHTML = rows.slice(0, 8).map((row) => `
                    <a href="/admin/categories/${row.id}" class="list-panel">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-gray-900 dark:text-white">${esc(row.name)}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${esc(typeLabel(row.type, row.type_label))}</p>
                        </div>
                        <span class="badge badge-danger">${Number(row[countKey] || 0)}</span>
                    </a>
                `).join('');
            }

            function renderMonthlyProductGrowth(rows) {
                const container = document.getElementById('monthly-product-growth');

                if (!rows.length) {
                    container.innerHTML = emptyState(adminDashboardI18n.noGrowthData);
                    return;
                }

                const max = Math.max(...rows.map((row) => Number(row.total || 0)), 1);
                container.innerHTML = rows.map((row) => {
                    const width = Math.max(4, Math.round((Number(row.total || 0) / max) * 100));

                    return `
                        <div class="rounded-2xl border border-gray-200/80 bg-white/70 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                            <div class="mb-2 flex items-center justify-between gap-3 text-xs">
                                <span class="font-bold text-gray-700 dark:text-gray-200">${esc(row.month)}</span>
                                <span class="text-gray-500 dark:text-gray-400">${Number(row.total || 0)}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-brand-500" style="width:${width}%"></div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function emptyState(message, tone = 'muted') {
                const toneClass = tone === 'danger'
                    ? 'text-red-500 border-red-200/70 bg-red-50/70 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300'
                    : 'text-gray-500 border-gray-200/70 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400';

                return `<div class="rounded-2xl border px-4 py-6 text-center text-sm font-medium ${toneClass}">${esc(message)}</div>`;
            }

            function esc(value) {
                if (!value) {
                    return '';
                }

                const element = document.createElement('div');
                element.textContent = value;

                return element.innerHTML;
            }

            function typeLabel(type, fallback = '') {
                if (type === 'agriculture') {
                    return adminDashboardI18n.typeAgriculture || fallback || type || '';
                }
                if (type === 'veterinary') {
                    return adminDashboardI18n.typeVeterinary || fallback || type || '';
                }
                if (type === 'both') {
                    return adminDashboardI18n.typeBoth || fallback || type || '';
                }

                return fallback || type || '';
            }

            function businessTypeLabel(type, fallback = '') {
                return typeLabel(type, fallback);
            }

            function statusLabel(status) {
                if (status === 'active') {
                    return adminDashboardI18n.statusActive || status;
                }
                if (status === 'inactive') {
                    return adminDashboardI18n.statusInactive || status;
                }
                if (status === 'pending') {
                    return adminDashboardI18n.statusPending || status;
                }
                if (status === 'approved') {
                    return adminDashboardI18n.statusApproved || status;
                }

                return status || '';
            }
        });
    </script>
@endpush
