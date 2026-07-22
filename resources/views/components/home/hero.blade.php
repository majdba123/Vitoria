<section class="page-shell pb-4 pt-2 sm:pb-5 sm:pt-3">
    <div class="workspace-hero overflow-hidden">
        <div class="relative z-10 grid gap-8 lg:grid-cols-[minmax(0,1.25fr)_minmax(18rem,0.75fr)] lg:items-center">
            <div class="min-w-0">
                <span class="eyebrow">{{ __('home.hero_badge') }}</span>
                <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('home.hero_title_line_one') }}
                    <span class="block text-brand-200">{{ __('home.hero_title_highlight') }}</span>
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-white/78 sm:text-base">
                    {{ __('home.hero_subtitle') }}
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="#products" class="btn-primary">
                        {{ __('home.hero_primary_cta') }}
                        <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#categories" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white dark:border-white/15 dark:bg-white/10 dark:text-white">
                        {{ __('home.hero_secondary_cta') }}
                    </a>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                <div class="rounded-[24px] border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/55">{{ __('home.hero_stats_products') }}</p>
                    <p class="mt-2 text-2xl font-black text-white">500+</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/55">{{ __('home.hero_stats_dispatch') }}</p>
                    <p class="mt-2 text-2xl font-black text-white">24h</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/55">{{ __('home.hero_stats_support') }}</p>
                    <p class="mt-2 text-2xl font-black text-white">24/7</p>
                </div>
            </div>
        </div>
    </div>
</section>
