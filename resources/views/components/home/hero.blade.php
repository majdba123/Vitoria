<section class="page-shell pb-5 pt-2 sm:pb-6 sm:pt-3">
    <div class="storefront-hero">
        <div class="storefront-hero-copy">
            <span class="eyebrow">{{ __('home.hero_badge') }}</span>
            <h1 class="mt-5 max-w-xl text-3xl font-bold leading-[1.08] tracking-[-0.035em] sm:text-4xl lg:text-[3.4rem]" style="color: var(--color-text); font-family: var(--font-display);">
                {{ __('home.hero_title_line_one') }}
                <span class="block" style="color: var(--color-brand);">{{ __('home.hero_title_highlight') }}</span>
            </h1>
            <p class="mt-4 max-w-lg text-sm leading-7 sm:text-base" style="color: var(--color-text-secondary);">
                {{ __('home.hero_subtitle') }}
            </p>
            <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
                <a href="#products" class="btn-primary">
                    {{ __('home.hero_primary_cta') }}
                    <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
                <a href="#categories" class="inline-flex items-center gap-1.5 text-sm font-semibold" style="color: var(--color-text);">
                    {{ __('home.hero_secondary_cta') }}
                    <svg class="h-3.5 w-3.5 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
            <p class="hero-context-note">{{ __('home.hero_context_note') }}</p>
        </div>
        <div class="storefront-hero-media">
            <img src="{{ asset('images/hero-agriculture.webp') }}" alt="{{ __('home.hero_image_alt') }}" width="1200" height="900" fetchpriority="high">
        </div>
    </div>
</section>
