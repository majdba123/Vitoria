{{-- Best-selling products --}}
<section id="best-selling" class="storefront-section scroll-mt-24 bg-white dark:bg-gray-950/70">
    <div class="page-shell py-0">
        <div class="commerce-section-header border-t-2 border-gray-950 pt-7 dark:border-white">
            <div>
                <p class="commerce-kicker">{{ __('home.badge_bestsellers') }}</p>
                <h2 class="commerce-title">{{ __('home.bestselling_title') }}</h2>
                <p class="commerce-copy">{{ __('home.bestselling_subtitle') }}</p>
            </div>
            <a id="home-best-view-all" href="/products?sort=best_selling" class="hidden items-center gap-1.5 rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-bold text-white transition-all hover:bg-brand-600 active:scale-[.97] sm:inline-flex dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">{{ __('common.view_all') }} <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg></a>
        </div>
        <div id="best-selling-loading" class="responsive-shop-grid"><div class="skeleton h-[25rem]"></div><div class="skeleton h-[25rem]"></div><div class="skeleton h-[25rem]"></div><div class="skeleton h-[25rem]"></div><div class="skeleton hidden h-[25rem] xl:block"></div></div>
        <div id="best-selling-grid" class="responsive-shop-grid"></div>
        <div id="best-selling-empty" class="hidden py-12 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('home.no_products_yet') }}</div>
    </div>
</section>
