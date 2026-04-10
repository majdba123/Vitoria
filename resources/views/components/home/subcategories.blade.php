{{-- ═══ Subcategories Carousel ═══ --}}
<section id="subcategories" class="scroll-mt-20 bg-gray-50 py-14 dark:bg-gray-900 sm:py-20">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <span class="inline-block rounded-full bg-blue-50 px-4 py-1.5 text-[11px] font-bold uppercase tracking-widest text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">Browse</span>
                <h2 class="mt-3 text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">Popular Subcategories</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Explore specific product types</p>
            </div>
            <div class="hidden gap-2 sm:flex">
                <button onclick="document.getElementById('subs-track').scrollBy({left:-300,behavior:'smooth'})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm hover:border-brand-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg></button>
                <button onclick="document.getElementById('subs-track').scrollBy({left:300,behavior:'smooth'})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm hover:border-brand-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg></button>
            </div>
        </div>
        <div id="subs-loading" class="flex gap-4 overflow-hidden"><div class="skeleton h-28 w-44 shrink-0 rounded-2xl"></div><div class="skeleton h-28 w-44 shrink-0 rounded-2xl"></div><div class="skeleton h-28 w-44 shrink-0 rounded-2xl"></div><div class="skeleton h-28 w-44 shrink-0 rounded-2xl"></div><div class="skeleton h-28 w-44 shrink-0 rounded-2xl"></div><div class="skeleton h-28 w-44 shrink-0 rounded-2xl"></div></div>
        <div id="subs-track" class="flex gap-4 overflow-x-auto scroll-smooth pb-4 hide-scrollbar"></div>
    </div>
</section>
