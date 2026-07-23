<section class="page-shell pb-5 pt-2 sm:pb-6 sm:pt-3">
    <div class="storefront-hero">
        <div class="relative z-10 grid gap-8 xl:grid-cols-[minmax(0,1.2fr)_minmax(22rem,0.8fr)] xl:items-center">
            <div class="min-w-0">
                <span class="eyebrow border-white/10 bg-white/10 text-white">{{ __('home.hero_badge') }}</span>
                <h1 class="mt-5 max-w-4xl text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('home.hero_title_line_one') }}
                    <span class="block text-brand-200">{{ __('home.hero_title_highlight') }}</span>
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-white/78 sm:text-base">
                    {{ __('home.hero_subtitle') }}
                </p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="#products" class="btn-primary">
                        {{ __('home.hero_primary_cta') }}
                        <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#categories" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white dark:border-white/15 dark:bg-white/10 dark:text-white">
                        {{ __('home.hero_secondary_cta') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-3">
                <div class="storefront-info-strip">
                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-white/55">{{ __('home.hero_flow_badge') }}</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                        <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-4">
                            <p class="text-xs font-black text-white">{{ __('home.hero_flow_category') }}</p>
                            <p class="mt-1 text-xs leading-6 text-white/65">{{ __('home.hero_flow_category_text') }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-4">
                            <p class="text-xs font-black text-white">{{ __('home.hero_flow_subcategory') }}</p>
                            <p class="mt-1 text-xs leading-6 text-white/65">{{ __('home.hero_flow_subcategory_text') }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-4">
                            <p class="text-xs font-black text-white">{{ __('home.hero_flow_product') }}</p>
                            <p class="mt-1 text-xs leading-6 text-white/65">{{ __('home.hero_flow_product_text') }}</p>
                        </div>
                    </div>
                </div>

                <div class="storefront-info-strip">
                    <p class="text-sm font-black text-white">{{ __('home.hero_support_title') }}</p>
                    <p class="mt-2 text-sm leading-7 text-white/72">{{ __('home.hero_support_text') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
