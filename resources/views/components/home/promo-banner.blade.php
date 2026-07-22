<section class="page-shell py-4 sm:py-5">
    <div class="surface-card-muted overflow-hidden px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-brand-600 dark:text-brand-300">{{ __('home.promo_badge') }}</p>
                    <h3 class="mt-2 text-xl font-black text-gray-900 dark:text-white sm:text-2xl">{{ __('home.promo_title') }}</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-gray-500 dark:text-gray-400">{{ __('home.promo_text') }}</p>
                </div>
            </div>
            <a href="#products" class="btn-primary shrink-0">
                {{ __('home.promo_action') }}
                <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</section>
