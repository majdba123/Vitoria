{{-- ═══ Hero Section ═══ --}}
<section class="relative overflow-hidden bg-gradient-to-br from-gray-900 via-gray-900 to-brand-900 dark:from-black dark:via-gray-950 dark:to-brand-950">
    {{-- Animated Background --}}
    <div class="absolute inset-0">
        <div class="absolute -top-1/2 left-1/4 h-[600px] w-[600px] rounded-full bg-brand-500/10 blur-[120px]" style="animation:float 8s ease-in-out infinite;"></div>
        <div class="absolute -bottom-1/2 right-1/4 h-[500px] w-[500px] rounded-full bg-brand-600/8 blur-[100px]" style="animation:float 10s ease-in-out infinite 2s;"></div>
        <div class="absolute inset-0" style="background-image:radial-gradient(rgba(255,255,255,.03) 1px, transparent 1px); background-size:32px 32px;"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8 lg:py-36">
        <div class="mx-auto max-w-3xl text-center">
            {{-- Badge --}}
            <div class="anim-down mb-6 inline-flex items-center gap-2.5 rounded-full border border-brand-500/20 bg-brand-500/10 px-5 py-2 backdrop-blur-sm">
                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-brand-500"></span></span>
                <span class="text-xs font-semibold tracking-wide text-brand-300">Free shipping on orders over 50,000 SYP</span>
            </div>

            {{-- Headline --}}
            <h1 class="anim-up text-4xl font-black leading-[1.1] tracking-tight text-white sm:text-6xl lg:text-7xl" style="animation-delay:.15s;">
                Discover.<br>
                <span class="bg-gradient-to-r from-brand-400 via-brand-500 to-yellow-400 bg-clip-text text-transparent gradient-animate">Shop. Enjoy.</span>
            </h1>

            {{-- Subtitle --}}
            <p class="anim-up mx-auto mt-6 max-w-xl text-base leading-relaxed text-gray-400 sm:text-lg" style="animation-delay:.25s;">
                Thousands of products in one trusted marketplace. Premium quality, competitive prices, and fast delivery — all in one place.
            </p>

            {{-- CTAs --}}
            <div class="anim-up mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row" style="animation-delay:.35s;">
                <a href="#products" class="group inline-flex items-center gap-2.5 rounded-2xl bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-4 text-sm font-bold text-white shadow-xl shadow-brand-500/25 transition-all duration-300 hover:from-brand-600 hover:to-brand-700 hover:shadow-2xl hover:shadow-brand-500/30 active:scale-[.97]">
                    Start Shopping
                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
                <a href="#categories" class="inline-flex items-center gap-2 rounded-2xl border border-gray-700 bg-white/5 px-8 py-4 text-sm font-bold text-gray-300 backdrop-blur-sm transition-all duration-300 hover:border-gray-500 hover:bg-white/10 hover:text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z"/></svg>
                    Browse Categories
                </a>
            </div>

            {{-- Stats --}}
            <div class="anim-up mx-auto mt-16 grid max-w-lg grid-cols-3 gap-8 sm:max-w-xl" style="animation-delay:.45s;">
                <div class="text-center">
                    <div class="text-3xl font-black text-white sm:text-4xl" id="stat-products">500+</div>
                    <div class="mt-1 text-xs font-medium tracking-wide text-gray-500">Products</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-black text-white sm:text-4xl">24h</div>
                    <div class="mt-1 text-xs font-medium tracking-wide text-gray-500">Quick dispatch</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-black text-white sm:text-4xl">24/7</div>
                    <div class="mt-1 text-xs font-medium tracking-wide text-gray-500">Support</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gradient separator --}}
    <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-white dark:from-gray-950"></div>
</section>
