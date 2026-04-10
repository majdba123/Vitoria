{{-- Highest-rated products --}}
<section id="top-rated" class="scroll-mt-20 bg-gray-50 py-14 dark:bg-gray-900/50 sm:py-20">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <span class="inline-block rounded-full bg-amber-50 px-4 py-1.5 text-[11px] font-bold uppercase tracking-widest text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">Top Rated</span>
                <h2 class="mt-3 text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">Highest Rated</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Products loved by our customers</p>
            </div>
            <a href="/products?sort=top_rated" class="hidden items-center gap-1.5 rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-bold text-white transition-all hover:bg-brand-600 active:scale-[.97] sm:inline-flex dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">View All <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg></a>
        </div>
        <div id="top-rated-loading" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton hidden h-80 rounded-2xl xl:block"></div></div>
        <div id="top-rated-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"></div>
        <div id="top-rated-empty" class="hidden py-12 text-center text-sm text-gray-400 dark:text-gray-500">No products yet.</div>
    </div>
</section>
