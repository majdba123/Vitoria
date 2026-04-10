@php
    $currentRoute = request()->route()?->getName() ?? '';
@endphp

<aside id="vendor-sidebar"
       class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-gray-900 transition-transform duration-300 ease-in-out lg:translate-x-0">

    {{-- Logo --}}
    <div class="flex h-14 shrink-0 items-center gap-3 border-b border-white/10 px-6">
        <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-2">
            <span class="text-xl font-bold tracking-tight text-white">Syria<span class="text-emerald-400">Zone</span></span>
        </a>
        <span class="rounded-md bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400">Vendor</span>

        {{-- Close button (mobile) --}}
        <button onclick="closeSidebar()" class="ml-auto rounded-md p-1 text-gray-400 hover:text-white lg:hidden">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-4 py-5">
        <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Overview</p>

        {{-- Dashboard --}}
        <a href="{{ route('vendor.dashboard') }}"
           class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150
                  {{ str_starts_with($currentRoute, 'vendor.dashboard') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            Dashboard
        </a>

        <p class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">My Store</p>

        {{-- Products --}}
        <a href="{{ route('vendor.products.index') }}"
           class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150
                  {{ str_starts_with($currentRoute, 'vendor.products') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            Products
            @if(str_starts_with($currentRoute, 'vendor.products'))
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            @endif
        </a>

        {{-- Discounts --}}
        <a href="{{ route('vendor.discounts.index') }}"
           class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150
                  {{ str_starts_with($currentRoute, 'vendor.discounts') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m-5.25 0h.008v.008H9.75V8.25zm4.5 7.5h.008v.008h-.008v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Discounts
            @if(str_starts_with($currentRoute, 'vendor.discounts'))
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            @endif
        </a>

        {{-- Orders --}}
        <a href="{{ route('vendor.orders.index') }}"
           class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150
                  {{ str_starts_with($currentRoute, 'vendor.orders') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
            Orders
            @if(str_starts_with($currentRoute, 'vendor.orders'))
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            @endif
        </a>

        {{-- Commission --}}
        <a href="{{ route('vendor.commission') }}"
           class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150
                  {{ str_starts_with($currentRoute, 'vendor.commission') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.5 4.5L21.75 7.5M15.75 7.5h6v6"/>
            </svg>
            Commission
            @if(str_starts_with($currentRoute, 'vendor.commission'))
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            @endif
        </a>

        {{-- Notifications (bill / list) --}}
        <a href="{{ route('vendor.notifications.index') }}"
           class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150
                  {{ str_starts_with($currentRoute, 'vendor.notifications') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
            سجل الإشعارات
            @if(str_starts_with($currentRoute, 'vendor.notifications'))
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            @endif
        </a>

        {{-- My Categories --}}
        <p class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">My Categories</p>
        <div id="sidebar-categories" class="space-y-0.5">
            <div class="px-3 py-2">
                <div class="h-4 w-24 animate-pulse rounded bg-white/5"></div>
            </div>
        </div>

        <p class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Account</p>

        {{-- Profile --}}
        <a href="{{ route('vendor.profile') }}"
           class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150
                  {{ str_starts_with($currentRoute, 'vendor.profile') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            My Profile
            @if(str_starts_with($currentRoute, 'vendor.profile'))
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            @endif
        </a>
    </nav>

    {{-- Footer --}}
    <div class="border-t border-white/10 px-6 py-3">
        <p class="text-[11px] text-gray-500">&copy; {{ date('Y') }} SyriaZone</p>
    </div>
</aside>

