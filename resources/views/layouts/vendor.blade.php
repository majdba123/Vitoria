<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Vendor — Vetora')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <script>
        (function(){
            const t = localStorage.getItem('sz_theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased transition-colors duration-300 dark:bg-gray-950 dark:text-gray-100">

    {{-- Authenticated wrapper (hidden until verified) --}}
    <div id="vendor-app" class="hidden">
        {{-- Mobile sidebar backdrop --}}
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-gray-900/60 backdrop-blur-sm transition-opacity dark:bg-black/60 lg:hidden" onclick="closeSidebar()"></div>

        {{-- Sidebar --}}
        <x-vendor.sidebar />

        {{-- Main Column --}}
        <div class="lg:pl-72">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 dark:border-gray-800 dark:bg-gray-900/95 dark:supports-[backdrop-filter]:bg-gray-900/80">
                <div class="flex h-14 items-center gap-x-4 px-4 sm:px-6 lg:px-8">
                    {{-- Mobile menu button --}}
                    <button type="button" id="sidebar-toggle" class="-m-2.5 p-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 lg:hidden" aria-label="Open sidebar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>

                    {{-- Separator --}}
                    <div class="h-5 w-px bg-gray-200 dark:bg-gray-700 lg:hidden" aria-hidden="true"></div>

                    {{-- Page title --}}
                    <h1 class="flex-1 text-base font-semibold text-gray-900 dark:text-white sm:text-lg">@yield('page-title', 'Dashboard')</h1>

                    {{-- Right side --}}
                    <div class="flex items-center gap-x-3">
                        {{-- Home (go to website) --}}
                        <a href="{{ route('home') }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200" title="Go to website">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                        </a>
                        {{-- Notifications (data-context for notification links: customer | vendor | admin) --}}
                        <div id="vendor-notif-wrap" class="relative" data-context="vendor">
                            <button type="button" id="vendor-notif-btn" class="relative flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200" title="Notifications">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                <span id="vendor-notif-badge" class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold leading-none text-white hidden">0</span>
                            </button>
                            <div id="vendor-notif-dropdown" class="absolute right-0 top-full z-50 mt-2 hidden w-[min(420px,95vw)] max-h-[min(32rem,75vh)] overflow-hidden rounded-2xl bg-white/95 shadow-xl ring-1 ring-black/5 backdrop-blur-md dark:bg-gray-900/95 dark:ring-white/10 flex flex-col">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
                                    <a href="{{ route('vendor.notifications.index') }}" class="text-[13px] font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">الإشعارات</a>
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="vendor-notif-mark-all" class="text-[11px] font-medium uppercase tracking-wider text-brand-600 hover:text-brand-700 dark:text-brand-400">تحديد الكل كمقروء</button>
                                        <a href="{{ route('vendor.notifications.index') }}" class="text-[11px] font-medium uppercase tracking-wider text-brand-600 hover:text-brand-700 dark:text-brand-400">عرض الكل</a>
                                    </div>
                                </div>
                                <div id="vendor-notif-list" class="flex-1 min-h-0 max-h-[min(24rem,55vh)] overflow-y-auto">
                                    <p class="px-4 py-10 text-center text-[13px] text-gray-400 dark:text-gray-500">Loading...</p>
                                </div>
                                <div id="vendor-notif-empty" class="hidden px-4 py-12 text-center text-[13px] text-gray-400 dark:text-gray-500 shrink-0">No notifications.</div>
                                <div id="vendor-notif-pagination" class="hidden border-t border-gray-100 dark:border-gray-800 px-3 py-2 flex items-center justify-between gap-2 shrink-0 bg-gray-50/50 dark:bg-gray-800/30">
                                    <button type="button" id="vendor-notif-prev" class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none">Prev</button>
                                    <span id="vendor-notif-page-info" class="text-[11px] text-gray-500 dark:text-gray-400">Page 1 of 1</span>
                                    <button type="button" id="vendor-notif-next" class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none">Next</button>
                                </div>
                            </div>
                        </div>
                        {{-- Dark Mode Toggle --}}
                        <button onclick="toggleVendorTheme()" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200" title="Toggle theme">
                            <svg class="hidden h-4 w-4 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                            <svg class="block h-4 w-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                        </button>
                        <div class="hidden items-center gap-2 sm:flex">
                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400" id="vendor-avatar">V</div>
                            <span id="vendor-name" class="text-sm font-medium text-gray-700 dark:text-gray-300"></span>
                        </div>
                        <button onclick="vendorLogout()" class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Sign Out">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                            <span class="hidden sm:inline">Sign Out</span>
                        </button>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Loading Screen --}}
    <div id="vendor-loading" class="flex min-h-screen items-center justify-center bg-gray-50 dark:bg-gray-950">
        <div class="text-center">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-emerald-500 dark:border-gray-700"></div>
            <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">Loading your store...</p>
        </div>
    </div>

    <script>
        function toggleVendorTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('sz_theme', isDark ? 'dark' : 'light');
        }
    </script>
    <script>
        // Helper function to delete a cookie
        function deleteCookie(name, path = '/', domain = '') {
            let cookieString = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=' + path;
            if (domain) {
                cookieString += '; domain=' + domain;
            }
            document.cookie = cookieString;
            // Also try without domain
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=' + path;
        }

        // Define logout function globally before DOM loads
        window.vendorLogout = async function() {
            try {
                // Get token before clearing (for API call)
                const token = window.Auth?.getToken() || localStorage.getItem('auth_token');

                // Call logout API FIRST to invalidate server-side session
                if (token && window.axios) {
                    try {
                        await window.axios.post('/api/auth/logout', {}, {
                            headers: {
                                'Authorization': 'Bearer ' + token
                            }
                        });
                    } catch (e) {
                        // Continue even if API call fails
                        console.log('Logout API call failed (continuing):', e);
                    }
                }

                // Clear all auth data from client
                if (window.Auth && window.Auth.clearAll) {
                    window.Auth.clearAll();
                } else {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('auth_user');
                    sessionStorage.clear();
                    delete window.axios.defaults.headers.common['Authorization'];
                }

                // Delete cookies
                deleteCookie('XSRF-TOKEN');
                deleteCookie('laravel_session');

                // Force immediate redirect with logout parameter to prevent redirect loop
                window.location.replace('{{ route("login") }}?logout=1');
            } catch (e) {
                console.error('Error during logout:', e);
                // Even on error, redirect to login
                window.location.replace('{{ route("login") }}?logout=1');
            }
        };

        // Vendor Auth Guard
        document.addEventListener('DOMContentLoaded', async function () {
            // Check if this is a logout redirect
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('logout') === '1') {
                // Don't check auth, just show login page
                return;
            }

            if (!window.Auth || !window.Auth.isAuthenticated()) {
                window.location.href = '{{ route("login") }}';
                return;
            }

            try {
                const response = await window.axios.get('/api/user');
                const user = response.data.data || response.data;

                if (user.type !== 2) {
                    window.Auth.removeToken();
                    window.location.href = '{{ route("login") }}';
                    return;
                }

                window.Auth.setUser(user);
                document.getElementById('vendor-name').textContent = user.name;
                const avatarEl = document.getElementById('vendor-avatar');
                if (user.avatar_url) {
                    avatarEl.innerHTML = `<img src="${user.avatar_url}" alt="" class="h-full w-full rounded-full object-cover">`;
                } else if (user.avatar) {
                    avatarEl.innerHTML = `<img src="/storage/${user.avatar}" alt="" class="h-full w-full rounded-full object-cover">`;
                } else {
                    avatarEl.textContent = (user.name || 'V').charAt(0).toUpperCase();
                }
                document.getElementById('vendor-loading').classList.add('hidden');
                document.getElementById('vendor-app').classList.remove('hidden');

                loadSidebarCategories();
                vendorNotificationBadge();
                initVendorNotificationDropdown();
                if (typeof loadVendorNotificationDropdown === 'function') loadVendorNotificationDropdown(1);
            } catch (e) {
                window.Auth.removeToken();
                window.location.href = '{{ route("login") }}';
            }
        });

        function _vendorEsc(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
        function vendorNotificationBadge() {
            if (window.Auth && window.Auth.applyToken) window.Auth.applyToken();
            window.axios.get('/api/notifications', { params: { per_page: 1 } }).then(function (res) {
                const count = (res.data && res.data.unread_count) ?? 0;
                const badge = document.getElementById('vendor-notif-badge');
                if (!badge) return;
                if (count > 0) { badge.textContent = count > 99 ? '99+' : count; badge.classList.remove('hidden'); }
                else { badge.classList.add('hidden'); }
            }).catch(function () {});
        }
        function loadVendorNotificationDropdown(page) {
            page = typeof page === 'number' && page >= 1 ? page : 1;
            const listEl = document.getElementById('vendor-notif-list');
            const emptyEl = document.getElementById('vendor-notif-empty');
            const paginationEl = document.getElementById('vendor-notif-pagination');
            const pageInfoEl = document.getElementById('vendor-notif-page-info');
            const prevBtn = document.getElementById('vendor-notif-prev');
            const nextBtn = document.getElementById('vendor-notif-next');
            if (!listEl) return;
            listEl.innerHTML = '<p class="px-4 py-10 text-center text-[13px] text-gray-400 dark:text-gray-500">Loading...</p>';
            emptyEl && emptyEl.classList.add('hidden');
            paginationEl && paginationEl.classList.add('hidden');
            if (window.Auth && window.Auth.applyToken) window.Auth.applyToken();
            window.axios.get('/api/notifications', { params: { page: page } }).then(function (res) {
                try {
                    const data = res.data || {};
                    const items = Array.isArray(data.data) ? data.data : [];
                    const meta = data.meta || {};
                    const currentPage = typeof meta.current_page === 'number' ? meta.current_page : 1;
                    const lastPage = typeof meta.last_page === 'number' ? meta.last_page : 1;
                    const total = typeof meta.total === 'number' ? meta.total : 0;
                    const unread = data.unread_count ?? 0;
                    const badge = document.getElementById('vendor-notif-badge');
                    if (badge) { if (unread > 0) { badge.textContent = unread > 99 ? '99+' : unread; badge.classList.remove('hidden'); } else { badge.classList.add('hidden'); } }
                    if (items.length === 0 && page === 1) { listEl.innerHTML = ''; if (emptyEl) emptyEl.classList.remove('hidden'); return; }
                    if (emptyEl) emptyEl.classList.add('hidden');
                    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; }
                    var wrapEl = document.getElementById('vendor-notif-wrap');
                    var context = (wrapEl && wrapEl.getAttribute('data-context')) || 'vendor';
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
                        var isUnread = !n.read_at;
                        var time = '';
                        try { if (n.sent_at) time = new Date(n.sent_at).toLocaleDateString(undefined, { dateStyle: 'short', timeStyle: 'short' }); } catch (e) {}
                        var body = n.body != null ? esc(String(n.body)) : '';
                        var sender = n.sender_name != null ? esc(String(n.sender_name)) : '';
                        var href = notificationLink(n.action_type, n.action_id != null ? n.action_id : null);
                        var clickable = href ? ' cursor-pointer' : '';
                        var dataHref = href ? ' data-href="' + esc(href) + '"' : '';
                        var nid = n.id != null ? String(n.id) : '';
                        return '<div class="flex border-b border-gray-100 last:border-0 dark:border-gray-800 ' + (isUnread ? 'bg-gray-50/60 dark:bg-gray-800/40' : 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30') + clickable + '" data-nid="' + nid + '"' + dataHref + ' role="' + (href ? 'button' : 'presentation') + '">' +
                            (isUnread ? '<div class="w-0.5 shrink-0 self-stretch bg-brand-500 dark:bg-brand-400" aria-hidden="true"></div>' : '') +
                            '<div class="min-w-0 flex-1 py-3.5 px-4 ' + (isUnread ? 'pl-3.5' : 'pl-4') + '">' +
                            '<p class="text-[14px] leading-relaxed text-gray-800 dark:text-gray-100">' + body + '</p>' +
                            '<p class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">' + time + (sender ? ' · ' + sender : '') + '</p>' +
                            (isUnread ? '<button type="button" class="vendor-mark-one mt-2 text-[11px] font-medium text-brand-600 hover:underline dark:text-brand-400" data-id="' + nid + '">تحديد كمقروء</button>' : '') +
                            '</div></div>';
                    }).join('');
                    document.querySelectorAll('#vendor-notif-list [data-nid][data-href]').forEach(function (row) {
                        row.addEventListener('click', function (e) {
                            if (e.target.closest('.vendor-mark-one')) return;
                            var h = row.getAttribute('data-href');
                            if (h) { var dd = document.getElementById('vendor-notif-dropdown'); if (dd) dd.classList.add('hidden'); window.location.href = h; }
                        });
                    });
                    if (lastPage > 1 && paginationEl && pageInfoEl && prevBtn && nextBtn) {
                        pageInfoEl.textContent = 'Page ' + currentPage + ' of ' + lastPage + (total ? ' (' + total + ')' : '');
                        prevBtn.disabled = currentPage <= 1;
                        nextBtn.disabled = currentPage >= lastPage;
                        prevBtn.onclick = function () { if (currentPage > 1) loadVendorNotificationDropdown(currentPage - 1); };
                        nextBtn.onclick = function () { if (currentPage < lastPage) loadVendorNotificationDropdown(currentPage + 1); };
                        paginationEl.classList.remove('hidden');
                    }
                    listEl.querySelectorAll('.vendor-mark-one').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var id = btn.getAttribute('data-id');
                            if (id) window.axios.patch('/api/notifications/' + id + '/read').then(function () { loadVendorNotificationDropdown(currentPage); });
                        });
                    });
                } catch (e) {
                    console.error('Vendor notifications render error:', e);
                    listEl.innerHTML = '<p class="px-4 py-6 text-center text-sm text-red-500">Failed to load notifications.</p>';
                }
            }).catch(function (err) {
                var msg = (err.response && err.response.status === 401) ? 'Please sign in again.' : (err.response && err.response.data && err.response.data.message) ? err.response.data.message : 'Failed to load notifications.';
                listEl.innerHTML = '<p class="px-4 py-6 text-center text-sm text-red-500">' + msg + '</p>';
            });
        }
        function initVendorNotificationDropdown() {
            const btn = document.getElementById('vendor-notif-btn');
            const dd = document.getElementById('vendor-notif-dropdown');
            const wrap = document.getElementById('vendor-notif-wrap');
            const markAll = document.getElementById('vendor-notif-mark-all');
            if (!btn || !dd) return;
            btn.addEventListener('click', function (e) { e.stopPropagation(); dd.classList.toggle('hidden'); if (!dd.classList.contains('hidden')) loadVendorNotificationDropdown(); });
            document.addEventListener('click', function (e) { if (wrap && !wrap.contains(e.target)) dd.classList.add('hidden'); });
            markAll && markAll.addEventListener('click', function () { window.axios.post('/api/notifications/mark-all-read').then(function () { loadVendorNotificationDropdown(); vendorNotificationBadge(); }); });
        }

        // Sidebar toggle (mobile)
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('sidebar-toggle');
            if (toggle) {
                toggle.addEventListener('click', function () {
                    document.getElementById('vendor-sidebar').classList.remove('-translate-x-full');
                    document.getElementById('sidebar-backdrop').classList.remove('hidden');
                    document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
                });
            }
        });

        function closeSidebar() {
            document.getElementById('vendor-sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebar-backdrop').classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
        }

        async function loadSidebarCategories() {
            const container = document.getElementById('sidebar-categories');
            if (!container) return;
            try {
                const res = await window.axios.get('/api/vendor/allowed-categories');
                const categories = res.data.data || [];
                if (categories.length === 0) {
                    container.innerHTML = '<p class="px-3 py-2 text-xs text-gray-500 italic">No categories assigned yet.</p>';
                    return;
                }
                container.innerHTML = categories.map((cat, idx) => {
                    const subs = cat.subcategories || [];
                    const catId = `sidebar-cat-${cat.id}`;
                    return `<div class="sidebar-cat-group">
                        <button type="button" onclick="toggleSidebarCat('${catId}')"
                                class="mb-0.5 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 transition-all duration-150 hover:bg-white/5 hover:text-gray-200">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                            </svg>
                            <span class="flex-1 truncate text-left">${esc(cat.name)}</span>
                            <span class="rounded-full bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-bold text-emerald-400">${parseFloat(cat.commission || 0).toFixed(0)}%</span>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200 sidebar-cat-chevron" id="${catId}-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        ${subs.length > 0 ? `<div id="${catId}" class="hidden ml-4 space-y-0.5 border-l border-white/10 pl-3 pb-2">
                            ${subs.map(s => `
                                <div class="flex items-center gap-2 rounded-md px-2 py-1.5 text-xs text-gray-500 transition-colors hover:bg-white/5 hover:text-gray-300">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                    <span class="truncate">${esc(s.name)}</span>
                                </div>
                            `).join('')}
                        </div>` : ''}
                    </div>`;
                }).join('');
            } catch (e) {
                container.innerHTML = '<p class="px-3 py-2 text-xs text-red-400">Failed to load categories.</p>';
            }
        }

        function toggleSidebarCat(id) {
            const el = document.getElementById(id);
            const chevron = document.getElementById(id + '-chevron');
            if (!el) return;
            const isHidden = el.classList.contains('hidden');
            el.classList.toggle('hidden', !isHidden);
            if (chevron) {
                chevron.style.transform = isHidden ? 'rotate(180deg)' : '';
            }
        }

        function esc(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    </script>

    @stack('scripts')
</body>
</html>

