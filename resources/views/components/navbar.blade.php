{{-- ═══ Navbar ═══ --}}
<header class="sticky top-0 z-50 px-3 pt-3 sm:px-5 sm:pt-4">
    <nav class="glass-panel mx-auto max-w-screen-2xl rounded-[30px] border border-white/50 px-3 shadow-[0_24px_55px_-34px_rgba(5,150,105,0.35)] dark:border-white/10">
        <div class="flex h-[74px] items-center gap-3 px-2 sm:px-3 lg:px-4">
            {{-- Logo --}}
            <a href="{{ url('/') }}" class="flex shrink-0 items-center">
                <div class="rounded-[22px] bg-white px-3 py-2 shadow-sm ring-1 ring-gray-200 dark:ring-white/10">
                    <img
                        src="{{ asset('images/vetora-logo.jpg') }}"
                        alt="Vetora"
                        class="h-10 w-auto object-contain sm:h-12"
                    >
                </div>
            </a>

            {{-- Desktop Category Button --}}
            <div class="relative hidden lg:block" id="mega-wrap">
                <button id="mega-btn" class="flex items-center gap-2 rounded-2xl border border-white/40 bg-white/70 px-4 py-2.5 text-sm font-extrabold text-gray-700 shadow-sm hover:-translate-y-0.5 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-brand-500 dark:hover:bg-brand-500/10 dark:hover:text-brand-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                    {{ __('nav.categories') }}
                    <svg class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200" id="mega-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div id="mega-panel" class="dropdown-panel absolute left-0 top-full z-50 mt-3 hidden w-[780px]" style="animation:fadeIn .15s ease-out;">
                    <div class="flex" style="min-height:340px;">
                        <div id="mega-cats" class="w-64 shrink-0 overflow-y-auto border-r border-gray-100/80 bg-gray-50/70 py-2 dark:border-gray-800 dark:bg-gray-900/40">
                            <div class="px-5 py-8 text-center text-xs text-gray-400">{{ __('nav.loading_categories') }}</div>
                        </div>
                        <div id="mega-subs" class="flex-1 p-5 overflow-y-auto">
                            <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('nav.hover_category') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Desktop Links --}}
            <div class="hidden items-center gap-1 md:flex">
                <a href="{{ route('products.index') }}" class="rounded-2xl px-4 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-white/70 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white">{{ __('nav.products') }}</a>
                <a href="{{ route('categories.index') }}" class="rounded-2xl px-4 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-white/70 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white">{{ __('nav.categories') }}</a>
            </div>

            <div class="flex-1"></div>

            {{-- Right Actions --}}
            <div class="flex items-center gap-2">
                {{-- Language Switcher --}}
                <x-language-switcher />
                {{-- Dark Mode Toggle --}}
                <button id="theme-toggle" class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 transition-colors hover:border-white/40 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-gray-200" title="{{ __('nav.toggle_theme_aria') }}">
                    <svg id="icon-sun" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                    <svg id="icon-moon" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                </button>

                {{-- Cart --}}
                <button id="nav-cart" class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 transition-colors hover:border-white/40 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-gray-200" onclick="window.showCart && window.showCart()" title="{{ __('nav.cart') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    <span id="cart-badge" class="absolute -right-0.5 -top-0.5 flex h-4.5 min-w-[18px] items-center justify-center rounded-full bg-brand-500 px-1 text-[10px] font-bold leading-none text-white shadow hidden"></span>
                </button>

                {{-- Notifications (authenticated only). data-context: customer | vendor | admin for notification links. --}}
                <div id="nav-notifications-wrap" class="relative hidden" data-context="customer">
                    <button id="nav-notifications-btn" type="button" class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 transition-colors hover:border-white/40 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-gray-200" title="{{ __('nav.notifications_aria') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                        <span id="notification-badge" class="absolute -right-0.5 -top-0.5 flex h-4.5 min-w-[18px] items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold leading-none text-white shadow hidden">0</span>
                    </button>
                    <div id="notification-dropdown" class="dropdown-panel absolute right-0 top-full z-50 mt-2 hidden flex max-h-[min(32rem,75vh)] w-[min(420px,95vw)] flex-col" style="animation:fadeIn .15s ease-out;">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
                            <span class="text-[13px] font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('nav.notifications') }}</span>
                            <button type="button" id="notification-mark-all-read" class="text-[11px] font-medium uppercase tracking-wider text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ __('nav.mark_all_read') }}</button>
                        </div>
                        <div id="notification-list" class="overflow-y-auto flex-1 min-h-0 max-h-[min(24rem,55vh)]">
                            <p class="px-4 py-10 text-center text-[13px] text-gray-400 dark:text-gray-500">{{ __('common.loading') }}</p>
                        </div>
                        <div id="notification-empty" class="hidden px-4 py-12 text-center text-[13px] text-gray-400 dark:text-gray-500 shrink-0">{{ __('nav.no_notifications') }}</div>
                        <div id="notification-pagination" class="hidden border-t border-gray-100 dark:border-gray-800 px-3 py-2 flex items-center justify-between gap-2 shrink-0 bg-gray-50/50 dark:bg-gray-800/30">
                            <button type="button" id="notification-prev" class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none">{{ __('nav.prev') }}</button>
                            <span id="notification-page-info" class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('nav.page') }} 1 {{ __('nav.of') }} 1</span>
                            <button type="button" id="notification-next" class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none">{{ __('nav.next') }}</button>
                        </div>
                    </div>
                </div>

                {{-- Guest Buttons (hidden when authenticated) --}}
                <div id="nav-guest" class="hidden items-center gap-2 sm:flex">
                    <a href="{{ route('login') }}" class="rounded-2xl px-4 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-white/70 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white">{{ __('nav.sign_in') }}</a>
                    <a href="{{ route('register') }}" class="btn-primary btn-sm">{{ __('nav.register') }}</a>
                </div>

                {{-- Profile Dropdown (shown when authenticated) --}}
                <div id="profile-wrap" class="relative hidden">
                    <button id="profile-btn" class="flex items-center gap-2.5 rounded-2xl border border-transparent px-2.5 py-2 transition-all hover:border-white/40 hover:bg-white/70 dark:hover:border-white/10 dark:hover:bg-white/5">
                        <div id="profile-avatar" class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-400 to-brand-600 ring-2 ring-white dark:ring-gray-800">
                            <span id="profile-initial" class="text-sm font-bold text-white">?</span>
                        </div>
                        <div class="hidden sm:block text-left">
                            <p id="profile-name" class="text-sm font-bold leading-tight text-gray-900 dark:text-white"></p>
                            <p id="profile-role" class="text-[10px] font-medium text-gray-400 dark:text-gray-500">{{ __('nav.customer') }}</p>
                        </div>
                        <svg class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200" id="profile-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>

                    {{-- Dropdown --}}
                    <div id="profile-dropdown" class="dropdown-panel absolute right-0 top-full z-50 mt-2 hidden w-72" style="animation:fadeIn .12s ease-out;">
                        {{-- User Info Header --}}
                        <div class="border-b border-gray-100 bg-gray-50/80 px-5 py-4 dark:border-gray-800 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div id="dd-avatar" class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-400 to-brand-600 ring-2 ring-white dark:ring-gray-700">
                                    <span id="dd-initial" class="text-base font-bold text-white">?</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p id="dd-name" class="truncate text-sm font-bold text-gray-900 dark:text-white"></p>
                                    <p id="dd-email" class="truncate text-xs text-gray-500 dark:text-gray-400"></p>
                                </div>
                            </div>
                        </div>
                        {{-- Menu Links --}}
                        <div class="py-2">
                            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                {{ __('nav.my_profile') }}
                            </a>
                            <a id="dd-dashboard-link" href="#" class="hidden items-center gap-3 px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z"/></svg>
                                {{ __('nav.dashboard') }}
                            </a>
                        </div>
                        <div class="border-t border-gray-100 py-2 dark:border-gray-800">
                            <button onclick="handleLogout()" class="flex w-full items-center gap-3 px-5 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                {{ __('nav.sign_out') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Mobile Hamburger --}}
                <button id="mobile-btn" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 hover:border-white/40 hover:bg-white/70 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>
            </div>
        </div>
    </nav>

    {{-- Mobile Drawer --}}
    <div id="mobile-drawer" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeMobileMenu()"></div>
        <div class="absolute right-0 top-0 flex h-full w-80 max-w-[85vw] flex-col border-l border-white/40 bg-white/92 shadow-2xl backdrop-blur-xl dark:border-white/10 dark:bg-gray-900/94 rtl:right-auto rtl:left-0 rtl:border-l-0 rtl:border-r" style="animation:slideInRight .25s cubic-bezier(.22,1,.36,1);">
            <div class="flex items-center justify-between border-b border-gray-200/70 px-5 py-4 dark:border-gray-800">
                <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ __('nav.menu') }}</span>
                <button onclick="closeMobileMenu()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            {{-- Mobile Profile Section --}}
            <div id="mobile-profile" class="hidden border-b border-gray-200/70 px-5 py-4 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div id="mob-avatar" class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-400 to-brand-600">
                        <span id="mob-initial" class="text-sm font-bold text-white">?</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p id="mob-name" class="truncate text-sm font-bold text-gray-900 dark:text-white"></p>
                        <p id="mob-email" class="truncate text-xs text-gray-400 dark:text-gray-500"></p>
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('profile') }}" onclick="closeMobileMenu()" class="flex-1 rounded-2xl border border-gray-200 py-2 text-center text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('common.profile') }}</a>
                    <a id="mob-dashboard-link" href="#" onclick="closeMobileMenu()" class="hidden flex-1 rounded-2xl border border-gray-200 py-2 text-center text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('nav.dashboard') }}</a>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4">
                <div class="space-y-1">
                    <a href="{{ route('products.index') }}" onclick="closeMobileMenu()" class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        {{ __('nav.products') }}
                    </a>
                    <a href="{{ route('categories.index') }}" onclick="closeMobileMenu()" class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z"/></svg>
                        {{ __('nav.categories') }}
                    </a>
                </div>
                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-widest text-gray-400">{{ __('nav.categories') }}</p>
                    <div id="mobile-cats" class="space-y-0.5"></div>
                </div>
            </div>

            {{-- Mobile Footer --}}
            <div id="mobile-guest-footer" class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                <a href="{{ route('login') }}" class="mb-2 block w-full rounded-xl border border-gray-200 py-2.5 text-center text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('nav.sign_in') }}</a>
                <a href="{{ route('register') }}" class="block w-full rounded-xl bg-brand-500 py-2.5 text-center text-sm font-bold text-white transition-colors hover:bg-brand-600">{{ __('nav.register') }}</a>
            </div>
            <div id="mobile-auth-footer" class="hidden border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                <button onclick="handleLogout();closeMobileMenu();" class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 py-2.5 text-sm font-bold text-red-600 transition-colors hover:bg-red-50 dark:border-red-500/20 dark:text-red-400 dark:hover:bg-red-500/10">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    {{ __('nav.sign_out') }}
                </button>
            </div>
        </div>
    </div>
</header>

<script>
function _esc(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
function _categoryImageUrl(category) {
    if (category.image_url) {
        return category.image_url;
    }
    if (category.logo) {
        return '/storage/' + category.logo;
    }
    if (category.icon) {
        return '/storage/' + category.icon;
    }

    return '';
}
function categoryThumbHtml(c, mobile) {
    const imageUrl = _categoryImageUrl(c);
    if (imageUrl) {
        const imgCls = mobile ? 'h-full w-full rounded-lg object-cover' : 'h-full w-full object-cover';
        return '<img src="' + _esc(imageUrl) + '" class="' + imgCls + '" alt="">';
    }
    if (mobile) {
        return '<svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581"/></svg>';
    }
    return '<svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/></svg>';
}
window.szCategoryThumbHtml = categoryThumbHtml;
@php
    $navStrings = [
        'loading' => __('common.loading'),
        'no_categories' => __('common.no_categories'),
        'view_all' => __('common.view_all'),
        'failed_notifications' => __('common.failed_notifications'),
        'page' => __('nav.page'),
        'of' => __('nav.of'),
        'mark_notification_read' => __('nav.mark_notification_read'),
        'sign_in_again' => __('common.please_sign_in_again'),
        'failed_show_notifications' => __('nav.failed_show_notifications'),
        'user_fallback' => __('common.user_fallback'),
    ];
    $navRoleLabels = [
        0 => __('nav.customer'),
        1 => __('nav.admin'),
        2 => __('nav.business_account'),
    ];
    $navDefaultRole = __('nav.customer');
@endphp
window.__navStrings = @json($navStrings);
const NAV_ROLE_MAP = @json($navRoleLabels);
const NAV_ROLE_DEFAULT = @json($navDefaultRole);
document.addEventListener('DOMContentLoaded', function () {
    updateNavbar();
    refreshSessionUserFromApi();
    loadNavCategories();
    initMegaMenu();
    initThemeToggle();
    initProfileDropdown();
    initNotificationDropdown();
    window.addEventListener('focus', function () {
        if (window.Auth && window.Auth.isAuthenticated()) updateNotificationBadge();
    });

    document.getElementById('mobile-btn')?.addEventListener('click', () => {
        document.getElementById('mobile-drawer').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });
});

function closeMobileMenu() {
    document.getElementById('mobile-drawer').classList.add('hidden');
    document.body.style.overflow = '';
}

function initMegaMenu() {
    const btn = document.getElementById('mega-btn'), panel = document.getElementById('mega-panel'), chevron = document.getElementById('mega-chevron');
    if (!btn) return;
    let open = false;
    btn.addEventListener('click', () => { open = !open; panel.classList.toggle('hidden', !open); chevron.style.transform = open ? 'rotate(180deg)' : ''; });
    document.addEventListener('click', (e) => { if (open && !document.getElementById('mega-wrap').contains(e.target)) { open = false; panel.classList.add('hidden'); chevron.style.transform = ''; } });
}

function initThemeToggle() {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;
    btn.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('sz_theme', isDark ? 'dark' : 'light');
    });
}

function initProfileDropdown() {
    const btn = document.getElementById('profile-btn'), dd = document.getElementById('profile-dropdown'), chev = document.getElementById('profile-chevron');
    if (!btn || !dd) return;
    let open = false;
    btn.addEventListener('click', () => { open = !open; dd.classList.toggle('hidden', !open); chev.style.transform = open ? 'rotate(180deg)' : ''; });
    document.addEventListener('click', (e) => { if (open && !document.getElementById('profile-wrap').contains(e.target)) { open = false; dd.classList.add('hidden'); chev.style.transform = ''; } });
}

function updateNavbar() {
    const isAuth = window.Auth && window.Auth.isAuthenticated();
    const el = id => document.getElementById(id);
    const user = isAuth ? window.Auth.getUser() : null;

    if (isAuth && user) {
        el('nav-guest')?.classList.add('hidden');
        el('nav-guest')?.classList.remove('sm:flex');
        el('profile-wrap')?.classList.remove('hidden');
        el('mobile-guest-footer')?.classList.add('hidden');
        el('mobile-auth-footer')?.classList.remove('hidden');
        el('mobile-profile')?.classList.remove('hidden');

        const name = user.name || (window.__navStrings && window.__navStrings.user_fallback ? window.__navStrings.user_fallback : '');
        const email = user.email || '';
        const initial = name.charAt(0).toUpperCase();
        const avatarUrl = user.avatar_url || null;
        const roleMap = NAV_ROLE_MAP;
        const role = roleMap[user.type] ?? NAV_ROLE_DEFAULT;

        const setAvatar = (containerId, initialId) => {
            const c = el(containerId), i = el(initialId);
            if (!c) return;
            if (avatarUrl) {
                c.innerHTML = `<img src="${_esc(avatarUrl)}" class="h-full w-full object-cover" alt="">`;
            } else if (i) {
                i.textContent = initial;
            }
        };

        setAvatar('profile-avatar', 'profile-initial');
        setAvatar('dd-avatar', 'dd-initial');
        setAvatar('mob-avatar', 'mob-initial');

        if (el('profile-name')) el('profile-name').textContent = name;
        if (el('profile-role')) el('profile-role').textContent = role;
        if (el('dd-name')) el('dd-name').textContent = name;
        if (el('dd-email')) el('dd-email').textContent = email;
        if (el('mob-name')) el('mob-name').textContent = name;
        if (el('mob-email')) el('mob-email').textContent = email;

        const dashLink = el('dd-dashboard-link'), mobDash = el('mob-dashboard-link');
        if (user.type === 1) {
            if (dashLink) { dashLink.href = '{{ url("/admin/dashboard") }}'; dashLink.classList.remove('hidden'); dashLink.classList.add('flex'); }
            if (mobDash) { mobDash.href = '{{ url("/admin/dashboard") }}'; mobDash.classList.remove('hidden'); }
        } else if (user.type === 2) {
            if (dashLink) { dashLink.href = '{{ url("/vendor/dashboard") }}'; dashLink.classList.remove('hidden'); dashLink.classList.add('flex'); }
            if (mobDash) { mobDash.href = '{{ url("/vendor/dashboard") }}'; mobDash.classList.remove('hidden'); }
        }
        el('nav-notifications-wrap')?.classList.remove('hidden');
        updateNotificationBadge();
        if (typeof loadNotificationDropdown === 'function') loadNotificationDropdown(1);
        if (typeof window.setupNotificationEcho === 'function') {
            window.setupNotificationEcho(user.id);
            setTimeout(function () { if (typeof window.setupNotificationEcho === 'function') window.setupNotificationEcho(user.id); }, 2000);
        }
    } else if (isAuth) {
        el('nav-guest')?.classList.add('hidden');
        el('nav-guest')?.classList.remove('sm:flex');
        el('profile-wrap')?.classList.remove('hidden');
        el('mobile-guest-footer')?.classList.add('hidden');
        el('mobile-auth-footer')?.classList.remove('hidden');
        el('nav-notifications-wrap')?.classList.remove('hidden');
        updateNotificationBadge();
        if (typeof loadNotificationDropdown === 'function') loadNotificationDropdown(1);
        fetchAndSetUser();
    } else {
        el('nav-guest')?.classList.remove('hidden');
        el('nav-guest')?.classList.add('sm:flex');
        el('profile-wrap')?.classList.add('hidden');
        el('mobile-guest-footer')?.classList.remove('hidden');
        el('mobile-auth-footer')?.classList.add('hidden');
        el('mobile-profile')?.classList.add('hidden');
        el('nav-notifications-wrap')?.classList.add('hidden');
    }
    updateCartBadge();
}

window.updateNavbar = updateNavbar;

function updateNotificationBadge() {
    if (!window.Auth?.isAuthenticated()) return;
    if (window.Auth.applyToken) window.Auth.applyToken();
    window.axios.get('/api/notifications', { params: { per_page: 1 } }).then(function (res) {
        const count = (res.data && res.data.unread_count) ?? 0;
        const badge = document.getElementById('notification-badge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }).catch(function () {});
}

function loadNotificationDropdown(page) {
    page = typeof page === 'number' && page >= 1 ? page : 1;
    const listEl = document.getElementById('notification-list');
    const emptyEl = document.getElementById('notification-empty');
    const paginationEl = document.getElementById('notification-pagination');
    const pageInfoEl = document.getElementById('notification-page-info');
    const prevBtn = document.getElementById('notification-prev');
    const nextBtn = document.getElementById('notification-next');
    if (!listEl) return;
    listEl.innerHTML = '<p class="px-4 py-10 text-center text-[13px] text-gray-400 dark:text-gray-500">' + (window.__navStrings && window.__navStrings.loading ? window.__navStrings.loading : 'Loading...') + '</p>';
    emptyEl?.classList.add('hidden');
    paginationEl?.classList.add('hidden');
    if (window.Auth?.applyToken) window.Auth.applyToken();
    window.axios.get('/api/notifications', { params: { page: page } }).then(function (res) {
        try {
            const raw = res && res.data;
            const data = raw && typeof raw === 'object' ? raw : {};
            const items = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
            const meta = data.meta && typeof data.meta === 'object' ? data.meta : {};
            const currentPage = typeof meta.current_page === 'number' ? meta.current_page : 1;
            const lastPage = typeof meta.last_page === 'number' ? meta.last_page : 1;
            const total = typeof meta.total === 'number' ? meta.total : 0;
            const unreadCount = typeof data.unread_count === 'number' ? data.unread_count : 0;
            const badge = document.getElementById('notification-badge');
            if (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
            if (items.length === 0 && page === 1) {
                listEl.innerHTML = '';
                emptyEl?.classList.remove('hidden');
                return;
            }
            emptyEl?.classList.add('hidden');
            const esc = typeof _esc !== 'undefined' ? _esc : function (s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
            const wrapEl = document.getElementById('nav-notifications-wrap');
            const context = (wrapEl && wrapEl.getAttribute('data-context')) || 'customer';
            function notificationLink(actionType, actionId) {
                if (!actionType || actionId == null) return '';
                var id = String(actionId);
                if (context === 'vendor') {
                    if (actionType === 'product') return '/products/' + id;
                    if (actionType === 'order') return '/vendor/orders/' + id;
                }
                if (context === 'admin') {
                    if (actionType === 'product') return '/admin/products/' + id;
                    if (actionType === 'order') return '/admin/orders/' + id;
                }
                if (actionType === 'product') return '/products/' + id;
                if (actionType === 'order') return '/orders/' + id;
                return '';
            }
            listEl.innerHTML = items.map(function (n) {
                const isUnread = !n.read_at;
                let time = '';
                try {
                    if (n.sent_at) time = new Date(n.sent_at).toLocaleDateString(undefined, { dateStyle: 'short', timeStyle: 'short' });
                } catch (e) {}
                const body = n && (n.body != null) ? esc(String(n.body)) : '';
                const sender = n && n.sender_name != null ? esc(String(n.sender_name)) : '';
                const id = n && n.id != null ? String(n.id) : '';
                const href = notificationLink(n.action_type, n.action_id != null ? n.action_id : null);
                const clickable = href ? ' cursor-pointer' : '';
                const dataHref = href ? ' data-href="' + esc(href) + '"' : '';
                return '<div class="flex border-b border-gray-100 last:border-0 dark:border-gray-800 ' + (isUnread ? 'bg-gray-50/60 dark:bg-gray-800/40' : 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30') + clickable + '" data-notification-id="' + id + '"' + dataHref + ' role="' + (href ? 'button' : 'presentation') + '">' +
                    (isUnread ? '<div class="w-0.5 shrink-0 self-stretch bg-brand-500 dark:bg-brand-400" aria-hidden="true"></div>' : '') +
                    '<div class="min-w-0 flex-1 py-3.5 px-4 ' + (isUnread ? 'pl-3.5' : 'pl-4') + '">' +
                    '<p class="text-[14px] leading-relaxed text-gray-800 dark:text-gray-100">' + body + '</p>' +
                    '<p class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">' + time + (sender ? ' · ' + sender : '') + '</p>' +
                    (isUnread ? '<button type="button" class="notification-mark-one mt-2 text-[11px] font-medium text-brand-600 hover:underline dark:text-brand-400" data-id="' + id + '">' + esc((window.__navStrings && window.__navStrings.mark_notification_read) ? window.__navStrings.mark_notification_read : '') + '</button>' : '') +
                    '</div></div>';
            }).join('');
            listEl.querySelectorAll('[data-notification-id][data-href]').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.notification-mark-one')) return;
                    var h = row.getAttribute('data-href');
                    if (h) {
                        document.getElementById('notification-dropdown')?.classList.add('hidden');
                        window.location.href = h;
                    }
                });
            });
            if (lastPage > 1 && paginationEl && pageInfoEl && prevBtn && nextBtn) {
                const P = (window.__navStrings && window.__navStrings.page) ? window.__navStrings.page : '';
                const O = (window.__navStrings && window.__navStrings.of) ? window.__navStrings.of : '';
                pageInfoEl.textContent = P + ' ' + currentPage + ' ' + O + ' ' + lastPage + (total ? ' (' + total + ')' : '');
                prevBtn.disabled = currentPage <= 1;
                nextBtn.disabled = currentPage >= lastPage;
                prevBtn.onclick = function () { if (currentPage > 1) loadNotificationDropdown(currentPage - 1); };
                nextBtn.onclick = function () { if (currentPage < lastPage) loadNotificationDropdown(currentPage + 1); };
                paginationEl.classList.remove('hidden');
            }
            listEl.querySelectorAll('.notification-mark-one').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = btn.getAttribute('data-id');
                    if (!id) return;
                    var row = btn.closest('[data-notification-id]');
                    btn.disabled = true;
                    window.axios.patch('/api/notifications/' + id + '/read').then(function () {
                        if (row) {
                            var accent = row.querySelector('[aria-hidden="true"]');
                            if (accent) accent.remove();
                            row.classList.remove('bg-gray-50/60', 'dark:bg-gray-800/40');
                            row.classList.add('hover:bg-gray-50/50', 'dark:hover:bg-gray-800/30');
                            var inner = row.querySelector('div[class*="min-w-0"][class*="pl-3"]');
                            if (inner) inner.classList.replace('pl-3.5', 'pl-4');
                            btn.remove();
                        }
                        var badge = document.getElementById('notification-badge');
                        if (badge && !badge.classList.contains('hidden')) {
                            var n = parseInt(badge.textContent, 10) || 0;
                            if (n > 1) { badge.textContent = n - 1; } else { badge.classList.add('hidden'); }
                        }
                    }).finally(function () { btn.disabled = false; });
                });
            });
        } catch (e) {
            const fsn = (window.__navStrings && window.__navStrings.failed_show_notifications) ? window.__navStrings.failed_show_notifications : '';
            listEl.innerHTML = '<p class="px-4 py-6 text-center text-sm text-red-500">' + (typeof _esc !== 'undefined' ? _esc(fsn) : fsn) + '</p>';
        }
    }).catch(function (err) {
        const msg = err.response?.status === 401            ? ((window.__navStrings && window.__navStrings.sign_in_again) ? window.__navStrings.sign_in_again : '')
            : (err.response?.data?.message || (window.__navStrings && window.__navStrings.failed_notifications ? window.__navStrings.failed_notifications : ''));
        listEl.innerHTML = '<p class="px-4 py-6 text-center text-sm text-red-500">' + (typeof _esc !== 'undefined' ? _esc(msg) : msg) + '</p>';
    });
}

function initNotificationDropdown() {
    const wrap = document.getElementById('nav-notifications-wrap');
    const btn = document.getElementById('nav-notifications-btn');
    const dd = document.getElementById('notification-dropdown');
    const markAll = document.getElementById('notification-mark-all-read');
    if (!btn || !dd) return;
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const open = dd.classList.toggle('hidden');
        if (open) loadNotificationDropdown();
    });
    document.addEventListener('click', function (e) {
        if (wrap && !wrap.contains(e.target)) dd.classList.add('hidden');
    });
    markAll?.addEventListener('click', function () {
        markAll.disabled = true;
        window.axios.post('/api/notifications/mark-all-read').then(function () {
            var list = document.getElementById('notification-list');
            if (list) {
                list.querySelectorAll('.notification-mark-one').forEach(function (b) {
                    var row = b.closest('[data-notification-id]');
                    if (row) {
                        var accent = row.querySelector('[aria-hidden="true"]');
                        if (accent) accent.remove();
                        row.classList.remove('bg-gray-50/60', 'dark:bg-gray-800/40');
                        row.classList.add('hover:bg-gray-50/50', 'dark:hover:bg-gray-800/30');
                        var inner = row.querySelector('div[class*="min-w-0"][class*="pl-3"]');
                        if (inner) inner.classList.replace('pl-3.5', 'pl-4');
                    }
                    b.remove();
                });
            }
            var badge = document.getElementById('notification-badge');
            if (badge) badge.classList.add('hidden');
        }).finally(function () { markAll.disabled = false; });
    });
}

async function refreshSessionUserFromApi() {
    if (!window.Auth || !window.Auth.getToken()) {
        return;
    }
    try {
        const res = await window.axios.get('/api/user');
        const user = res.data.data || res.data;
        if (user && typeof user === 'object') {
            window.Auth.setUser(user);
            updateNavbar();
        }
    } catch (e) {
        // 401/403: axios interceptor clears session and may redirect
    }
}

async function fetchAndSetUser() {
    try {
        const res = await window.axios.get('/api/user');
        const user = res.data.data || res.data;
        window.Auth.setUser(user);
        updateNavbar();
    } catch (e) {
        // 401: axios interceptor clears session and redirects when a token was sent
        updateNavbar();
    }
}

async function loadNavCategories() {
    try {
        const res = await window.axios.get('/api/categories?per_page=100');
        const cats = res.data.data || [];
        window._navCats = cats;

        const list = document.getElementById('mega-cats');
        const mobileCats = document.getElementById('mobile-cats');

        if (cats.length === 0) {
            const nc = (window.__navStrings && window.__navStrings.no_categories) ? window.__navStrings.no_categories : '';
            list.innerHTML = '<p class="px-5 py-8 text-center text-xs text-gray-400">' + _esc(nc) + '</p>';
            return;
        }

        list.innerHTML = cats.map(c => `
            <a href="/categories/${c.id}" data-cat-id="${c.id}" class="mega-cat-btn group flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition-all hover:bg-white dark:hover:bg-gray-800"
                    onmouseenter="showNavSubs(${c.id}, this)">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-200/50 dark:bg-gray-800 dark:ring-gray-700">
                    ${categoryThumbHtml(c, false)}
                </div>
                <span class="flex-1 truncate font-medium text-gray-700 group-hover:text-brand-600 dark:text-gray-300 dark:group-hover:text-brand-400">${_esc(c.name)}</span>
                <svg class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        `).join('');

        mobileCats.innerHTML = cats.map(c => {
            return `
            <div>
                <a href="/categories/${c.id}" onclick="closeMobileMenu()" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                        ${categoryThumbHtml(c, true)}
                    </div>
                    <span class="flex-1">${_esc(c.name)}</span>
                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>`;
        }).join('');
    } catch(e) {}
}

window.showNavSubs = function(catId, btn) {
    document.querySelectorAll('.mega-cat-btn').forEach(el => { el.classList.remove('bg-white', 'dark:bg-gray-800'); el.style.boxShadow = ''; });
    btn.classList.add('bg-white', 'dark:bg-gray-800');
    btn.style.boxShadow = 'inset 3px 0 0 #f97316';
    const cat = (window._navCats || []).find(c => c.id === catId);
    const panel = document.getElementById('mega-subs');
    if (!cat) {
        panel.innerHTML = '<div class="flex h-full items-center justify-center"><p class="text-sm text-gray-400">' + _esc(window.__navStrings.view_all || 'View All') + '</p></div>';
        return;
    }
    panel.innerHTML = `
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">${_esc(cat.name)}</h3>
            <a href="/categories/${cat.id}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">${window.__navStrings && window.__navStrings.view_all ? window.__navStrings.view_all : 'View All'} &rarr;</a>
        </div>
        <div class="rounded-2xl border border-dashed border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            ${_esc(cat.type || '')}
        </div>`;
};

function updateCartBadge(animate) {
    try {
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        const badge = document.getElementById('cart-badge');
        if (!badge) return;
        const total = cart.reduce((s, i) => s + (i.quantity || 1), 0);
        if (total > 0) { badge.textContent = total > 99 ? '99+' : total; badge.classList.remove('hidden'); if (animate) { badge.classList.add('animate-bounce'); setTimeout(() => badge.classList.remove('animate-bounce'), 600); } }
        else { badge.classList.add('hidden'); }
    } catch (e) {}
}

window.addEventListener('cartUpdated', () => updateCartBadge(true));
window.addEventListener('storage', (e) => { if (e.key === 'cart') updateCartBadge(); });
updateCartBadge();
window.updateCartBadge = updateCartBadge;

async function handleLogout() {
    try { await window.axios.post('/api/auth/logout'); } catch (e) {}
    window.Auth.clearAll();
    window.location.href = '{{ route("login") }}';
}
</script>
