{{-- Best-selling products --}}
<section id="best-selling" class="storefront-section scroll-mt-24 bg-white dark:bg-gray-950/70">
    <div class="page-shell py-0">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="eyebrow bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ __('home.badge_bestsellers') }}</span>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('home.bestselling_title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-gray-500 dark:text-gray-400">{{ __('home.bestselling_subtitle') }}</p>
            </div>
            <a id="home-best-view-all" href="/products?sort=best_selling" class="hidden items-center gap-1.5 rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-bold text-white transition-all hover:bg-brand-600 active:scale-[.97] sm:inline-flex dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">{{ __('common.view_all') }} <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg></a>
        </div>
        <div id="best-selling-loading" class="responsive-shop-grid"><div class="skeleton h-[25rem] rounded-[28px]"></div><div class="skeleton h-[25rem] rounded-[28px]"></div><div class="skeleton h-[25rem] rounded-[28px]"></div><div class="skeleton h-[25rem] rounded-[28px]"></div><div class="skeleton hidden h-[25rem] rounded-[28px] xl:block"></div></div>
        <div id="best-selling-grid" class="responsive-shop-grid"></div>
        <div id="best-selling-empty" class="hidden py-12 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('home.no_products_yet') }}</div>
    </div>
</section>
