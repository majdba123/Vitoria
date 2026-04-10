{{-- ═══ Featured Stores — Horizontal carousel ═══ --}}
<section id="vendors" class="scroll-mt-20 bg-gray-50 py-14 dark:bg-gray-900 sm:py-20">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <span class="inline-block rounded-full bg-purple-50 px-4 py-1.5 text-[11px] font-bold uppercase tracking-widest text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">Stores</span>
                <h2 class="mt-3 text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">Featured Stores</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Trusted vendors with quality products</p>
            </div>
            <div class="hidden gap-2 sm:flex">
                <button onclick="document.getElementById('vendors-track').scrollBy({left:-320,behavior:'smooth'})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition-all hover:border-brand-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-brand-500"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg></button>
                <button onclick="document.getElementById('vendors-track').scrollBy({left:320,behavior:'smooth'})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition-all hover:border-brand-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-brand-500"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg></button>
            </div>
        </div>

        {{-- Skeleton --}}
        <div id="vendors-loading" class="flex gap-4 overflow-hidden"><div class="skeleton h-64 w-72 shrink-0 rounded-2xl"></div><div class="skeleton h-64 w-72 shrink-0 rounded-2xl"></div><div class="skeleton h-64 w-72 shrink-0 rounded-2xl"></div><div class="skeleton h-64 w-72 shrink-0 rounded-2xl"></div></div>

        {{-- Track --}}
        <div id="vendors-track" class="flex gap-4 overflow-x-auto scroll-smooth pb-4 hide-scrollbar"></div>

        {{-- Empty --}}
        <div id="vendors-empty" class="hidden py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349"/></svg>
            <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">No stores available yet</p>
        </div>
    </div>
</section>
