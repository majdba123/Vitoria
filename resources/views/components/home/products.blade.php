{{-- ═══ Latest Products — Shows last 5, links to full page ═══ --}}
<section id="products" class="storefront-section scroll-mt-24 bg-white dark:bg-gray-950/70">
    <div class="page-shell py-0">
        <div class="commerce-section-header">
            <div>
                <p class="commerce-kicker">{{ __('home.badge_new_arrivals') }}</p>
                <h2 class="commerce-title">{{ __('home.latest_products_title') }}</h2>
                <p class="commerce-copy">{{ __('home.latest_products_subtitle') }}</p>
            </div>
            <a id="home-products-view-all" href="/products" class="btn-secondary btn-sm hidden sm:inline-flex">
                {{ __('common.view_all') }}
                <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>

        <div id="products-loading" class="responsive-shop-grid">
            <div class="skeleton h-[25rem]"></div><div class="skeleton h-[25rem]"></div><div class="skeleton h-[25rem]"></div><div class="skeleton h-[25rem]"></div><div class="skeleton hidden h-[25rem] xl:block"></div>
        </div>
        <div id="products-grid" class="responsive-shop-grid"></div>
        <div id="products-empty" class="hidden py-12 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('home.no_products_yet') }}</div>

        <div class="mt-8 text-center sm:hidden">
            <a id="home-products-view-all-mobile" href="/products" class="btn-secondary btn-sm">{{ __('home.view_all_products_arrow') }} <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg></a>
        </div>
    </div>
</section>
