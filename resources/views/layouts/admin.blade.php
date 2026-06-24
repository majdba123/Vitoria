<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
@php
    $isRtl = app()->getLocale() === 'ar';
    $adminMainPaddingClass = $isRtl ? 'lg:pr-72' : 'lg:pl-72';
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('admin.dashboard') . ' - ' . __('Vetora'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|sora:600,700,800|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @stack('styles')
    <script>
        (function () {
            const theme = localStorage.getItem('sz_theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="dashboard-body min-h-screen font-sans text-gray-900 antialiased transition-colors duration-300 dark:text-gray-100">
    <div id="admin-app" class="hidden">
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-gray-950/55 backdrop-blur-sm transition-opacity dark:bg-black/70 lg:hidden" onclick="closeSidebar()"></div>

        <x-admin.sidebar />

        <div class="{{ $adminMainPaddingClass }}">
            <header class="dashboard-topbar sticky top-0 z-30">
                <div class="workspace-shell flex h-[78px] items-center gap-x-4">
                    <button type="button" id="sidebar-toggle" class="-m-2.5 flex h-10 w-10 items-center justify-center rounded-2xl text-gray-500 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200 lg:hidden" aria-label="{{ __('admin.open_sidebar') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>

                    <div class="h-5 w-px bg-gray-200 dark:bg-gray-700 lg:hidden" aria-hidden="true"></div>

                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">{{ __('admin.badge') }}</p>
                        <h1 class="mt-1 truncate text-lg font-black text-gray-900 dark:text-white sm:text-xl">@yield('page-title', 'Dashboard')</h1>
                    </div>

                    <div class="flex items-center gap-x-3">
                        <a href="{{ route('home') }}" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 transition-colors hover:border-white/50 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-gray-200" title="{{ __('nav.home') }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                        </a>

                        <x-language-switcher variant="compact" />

                        <div id="admin-notif-wrap" class="relative" data-context="admin">
                            <button type="button" id="admin-notif-btn" class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 transition-colors hover:border-white/50 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-gray-200" title="{{ __('admin.notifications') }}">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                <span id="admin-notif-badge" class="absolute -right-0.5 -top-0.5 hidden min-w-[16px] rounded-full bg-amber-500 px-1 text-[10px] font-bold leading-[16px] text-white">0</span>
                            </button>
                            <div id="admin-notif-dropdown" class="dropdown-panel absolute right-0 top-full z-50 mt-2 hidden w-[min(420px,95vw)] max-h-[min(32rem,75vh)]">
                                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                                    <a href="{{ route('admin.notifications.index') }}" class="text-[13px] font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('admin.notifications_title') }}</a>
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="admin-notif-mark-all" class="text-[11px] font-medium uppercase tracking-wider text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ __('admin.mark_all_read') }}</button>
                                        <a href="{{ route('admin.notifications.index') }}" class="text-[11px] font-medium uppercase tracking-wider text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ __('admin.view_all') }}</a>
                                    </div>
                                </div>
                                <div id="admin-notif-list" class="max-h-[min(24rem,55vh)] overflow-y-auto">
                                    <p class="px-4 py-10 text-center text-[13px] text-gray-400 dark:text-gray-500">{{ __('admin.loading') }}</p>
                                </div>
                                <div id="admin-notif-empty" class="hidden px-4 py-12 text-center text-[13px] text-gray-400 dark:text-gray-500">{{ __('admin.no_notifications') }}</div>
                                <div id="admin-notif-pagination" class="hidden items-center justify-between gap-2 border-t border-gray-100 bg-gray-50/60 px-3 py-2 dark:border-gray-800 dark:bg-gray-800/30">
                                    <button type="button" id="admin-notif-prev" class="rounded-xl px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 disabled:pointer-events-none disabled:opacity-50">{{ __('nav.prev') }}</button>
                                    <span id="admin-notif-page-info" class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('nav.page') }} 1 {{ __('nav.of') }} 1</span>
                                    <button type="button" id="admin-notif-next" class="rounded-xl px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 disabled:pointer-events-none disabled:opacity-50">{{ __('nav.next') }}</button>
                                </div>
                            </div>
                        </div>

                        <button onclick="toggleAdminTheme()" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 transition-colors hover:border-white/50 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-gray-200" title="{{ __('admin.toggle_theme') }}">
                            <svg id="admin-sun" class="hidden h-4 w-4 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                            <svg id="admin-moon" class="block h-4 w-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                        </button>

                        <div class="hidden items-center gap-3 rounded-2xl border border-white/40 bg-white/65 px-3 py-2 shadow-sm dark:border-white/10 dark:bg-white/5 sm:flex">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-400" id="admin-avatar">A</div>
                            <div>
                                <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('admin.badge') }}</p>
                                <span id="admin-name" class="text-sm font-bold text-gray-700 dark:text-gray-300"></span>
                            </div>
                        </div>

                        <button onclick="adminLogout()" class="flex items-center gap-1.5 rounded-2xl px-3 py-2 text-sm font-semibold text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="{{ __('admin.sign_out') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                            <span class="hidden sm:inline">{{ __('admin.sign_out') }}</span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="workspace-shell py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="admin-loading" class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white/70 shadow-lg shadow-gray-900/5 backdrop-blur-md dark:bg-white/5 dark:shadow-black/20">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700"></div>
            </div>
            <p class="mt-4 text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('admin.verifying_access') }}</p>
        </div>
    </div>

    @php
        $adminStrings = [
            'loading' => __('admin.loading'),
            'no_notifications' => __('admin.no_notifications'),
            'failed' => __('common.failed_notifications'),
            'page' => __('nav.page'),
            'of' => __('nav.of'),
            'mark_one_read' => __('admin.mark_one_read'),
        ];
    @endphp
    <script>
        window.__adminStrings = @json($adminStrings);
        const adminSidebarHiddenClass = @json($isRtl ? 'translate-x-full' : '-translate-x-full');
        function toggleAdminTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('sz_theme', isDark ? 'dark' : 'light');
        }
    </script>
    <script>
        function deleteCookie(name, path = '/', domain = '') {
            let cookieString = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=' + path;
            if (domain) {
                cookieString += '; domain=' + domain;
            }
            document.cookie = cookieString;
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=' + path;
        }

        window.adminLogout = async function () {
            try {
                const token = window.Auth?.getToken() || localStorage.getItem('auth_token');
                if (token && window.axios) {
                    try {
                        await window.axios.post('/api/auth/logout', {}, {
                            headers: {
                                Authorization: 'Bearer ' + token,
                            },
                        });
                    } catch (error) {
                        console.log('Logout API call failed (continuing):', error);
                    }
                }

                if (window.Auth && window.Auth.clearAll) {
                    window.Auth.clearAll();
                } else {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('auth_user');
                    sessionStorage.clear();
                    delete window.axios.defaults.headers.common.Authorization;
                }

                deleteCookie('XSRF-TOKEN');
                deleteCookie('laravel_session');
                window.location.replace('{{ route("login") }}?logout=1');
            } catch (error) {
                console.error('Error during logout:', error);
                window.location.replace('{{ route("login") }}?logout=1');
            }
        };

        document.addEventListener('DOMContentLoaded', async function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('logout') === '1') {
                return;
            }

            if (!window.Auth || !window.Auth.isAuthenticated()) {
                window.location.href = '{{ route("login") }}';
                return;
            }

            try {
                const response = await window.axios.get('/api/user');
                const user = response.data.data || response.data;

                if (user.type !== 1) {
                    window.Auth.removeToken();
                    window.location.href = '{{ route("login") }}';
                    return;
                }

                window.Auth.setUser(user);
                document.getElementById('admin-name').textContent = user.name;
                document.getElementById('admin-avatar').textContent = (user.name || 'A').charAt(0).toUpperCase();
                document.getElementById('admin-loading').classList.add('hidden');
                document.getElementById('admin-app').classList.remove('hidden');
                if (window.Auth && window.Auth.applyToken) {
                    window.Auth.applyToken();
                }
                document.dispatchEvent(new CustomEvent('admin-ready'));
                if (typeof adminNotificationBadge === 'function') {
                    adminNotificationBadge();
                }
                if (typeof initAdminNotificationDropdown === 'function') {
                    initAdminNotificationDropdown();
                }
                if (typeof loadAdminNotificationDropdown === 'function') {
                    loadAdminNotificationDropdown(1);
                }
            } catch (error) {
                window.Auth.removeToken();
                window.location.href = '{{ route("login") }}';
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('sidebar-toggle');
            if (toggle) {
                toggle.addEventListener('click', function () {
                    document.getElementById('admin-sidebar').classList.remove(adminSidebarHiddenClass);
                    document.getElementById('sidebar-backdrop').classList.remove('hidden');
                    document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
                });
            }
        });

        function closeSidebar() {
            document.getElementById('admin-sidebar').classList.add(adminSidebarHiddenClass);
            document.getElementById('sidebar-backdrop').classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
        }

        function adminNotificationBadge() {
            if (window.Auth && window.Auth.applyToken) {
                window.Auth.applyToken();
            }
            window.axios.get('/api/notifications', { params: { per_page: 1 } }).then(function (response) {
                const count = (response.data && response.data.unread_count) || 0;
                const badge = document.getElementById('admin-notif-badge');
                if (!badge) {
                    return;
                }
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }).catch(function () {});
        }

        function loadAdminNotificationDropdown(page) {
            page = typeof page === 'number' && page >= 1 ? page : 1;
            const listEl = document.getElementById('admin-notif-list');
            const emptyEl = document.getElementById('admin-notif-empty');
            const paginationEl = document.getElementById('admin-notif-pagination');
            const pageInfoEl = document.getElementById('admin-notif-page-info');
            const prevBtn = document.getElementById('admin-notif-prev');
            const nextBtn = document.getElementById('admin-notif-next');
            if (!listEl) {
                return;
            }
            listEl.innerHTML = '<p class="px-4 py-10 text-center text-[13px] text-gray-400 dark:text-gray-500">' + (window.__adminStrings && window.__adminStrings.loading ? window.__adminStrings.loading : 'Loading...') + '</p>';
            emptyEl && emptyEl.classList.add('hidden');
            paginationEl && paginationEl.classList.add('hidden');
            if (window.Auth && window.Auth.applyToken) {
                window.Auth.applyToken();
            }
            window.axios.get('/api/notifications', { params: { page: page } }).then(function (response) {
                try {
                    const data = response.data || {};
                    const items = Array.isArray(data.data) ? data.data : [];
                    const meta = data.meta || {};
                    const currentPage = meta.current_page || 1;
                    const lastPage = meta.last_page || 1;
                    const total = meta.total || 0;
                    const unread = data.unread_count ?? 0;
                    const badge = document.getElementById('admin-notif-badge');
                    if (badge) {
                        if (unread > 0) {
                            badge.textContent = unread > 99 ? '99+' : unread;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                    if (items.length === 0 && page === 1) {
                        listEl.innerHTML = '';
                        if (emptyEl) {
                            emptyEl.classList.remove('hidden');
                        }
                        return;
                    }
                    if (emptyEl) {
                        emptyEl.classList.add('hidden');
                    }

                    function esc(value) {
                        if (value == null) {
                            return '';
                        }
                        const div = document.createElement('div');
                        div.textContent = String(value);
                        return div.innerHTML;
                    }

                    function linkUrl(actionType, actionId) {
                        if (!actionType || actionId == null) {
                            return '';
                        }
                        const id = String(actionId);
                        if (actionType === 'product') {
                            return '{{ url("/admin/products") }}/' + id;
                        }
                        if (actionType === 'order') {
                            return '{{ url("/admin/orders") }}/' + id;
                        }
                        return '';
                    }

                    listEl.innerHTML = items.map(function (notification) {
                        const isUnread = !notification.read_at;
                        let time = '';
                        try {
                            if (notification.sent_at) {
                                time = new Date(notification.sent_at).toLocaleDateString(undefined, { dateStyle: 'short', timeStyle: 'short' });
                            }
                        } catch (error) {}
                        const body = notification.body != null ? esc(String(notification.body)) : '';
                        const sender = notification.sender_name != null ? esc(String(notification.sender_name)) : '';
                        const href = linkUrl(notification.action_type, notification.action_id != null ? notification.action_id : null);
                        const clickable = href ? ' cursor-pointer' : '';
                        const dataHref = href ? ' data-href="' + esc(href) + '"' : '';
                        const id = notification.id != null ? String(notification.id) : '';
                        return '<div class="flex border-b border-gray-100 last:border-0 dark:border-gray-800 ' + (isUnread ? 'bg-gray-50/60 dark:bg-gray-800/40' : 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30') + clickable + '" data-nid="' + id + '"' + dataHref + ' role="' + (href ? 'button' : 'presentation') + '">' +
                            (isUnread ? '<div class="w-0.5 shrink-0 self-stretch bg-brand-500 dark:bg-brand-400" aria-hidden="true"></div>' : '') +
                            '<div class="min-w-0 flex-1 px-4 py-3.5 ' + (isUnread ? 'pl-3.5' : 'pl-4') + '">' +
                            '<p class="text-[14px] leading-relaxed text-gray-800 dark:text-gray-100">' + body + '</p>' +
                            '<p class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">' + time + (sender ? ' - ' + sender : '') + '</p>' +
                            (isUnread ? '<button type="button" class="admin-mark-one mt-2 text-[11px] font-medium text-brand-600 hover:underline dark:text-brand-400" data-id="' + id + '">' + (window.__adminStrings && window.__adminStrings.mark_one_read ? window.__adminStrings.mark_one_read : 'Mark as read') + '</button>' : '') +
                            '</div></div>';
                    }).join('');

                    if (lastPage > 1 && paginationEl && pageInfoEl && prevBtn && nextBtn) {
                        pageInfoEl.textContent = (window.__adminStrings && window.__adminStrings.page ? window.__adminStrings.page : 'Page') + ' ' + currentPage + ' ' + (window.__adminStrings && window.__adminStrings.of ? window.__adminStrings.of : 'of') + ' ' + lastPage + (total ? ' (' + total + ')' : '');
                        prevBtn.disabled = currentPage <= 1;
                        nextBtn.disabled = currentPage >= lastPage;
                        prevBtn.onclick = function () { if (currentPage > 1) loadAdminNotificationDropdown(currentPage - 1); };
                        nextBtn.onclick = function () { if (currentPage < lastPage) loadAdminNotificationDropdown(currentPage + 1); };
                        paginationEl.classList.remove('hidden');
                    }

                    listEl.querySelectorAll('.admin-mark-one').forEach(function (button) {
                        button.addEventListener('click', function (event) {
                            event.stopPropagation();
                            event.preventDefault();
                            const id = button.getAttribute('data-id');
                            if (id) {
                                window.axios.patch('/api/notifications/' + id + '/read').then(function () {
                                    loadAdminNotificationDropdown(currentPage);
                                    adminNotificationBadge();
                                });
                            }
                        });
                    });

                    document.querySelectorAll('#admin-notif-list [data-nid][data-href]').forEach(function (row) {
                        row.addEventListener('click', function (event) {
                            if (event.target.closest('.admin-mark-one')) {
                                return;
                            }
                            const href = row.getAttribute('data-href');
                            if (href) {
                                const dropdown = document.getElementById('admin-notif-dropdown');
                                if (dropdown) {
                                    dropdown.classList.add('hidden');
                                }
                                window.location.href = href;
                            }
                        });
                    });
                } catch (error) {
                    console.error('Admin notifications render error:', error);
                    listEl.innerHTML = '<p class="px-4 py-6 text-center text-sm text-red-500">' + (window.__adminStrings && window.__adminStrings.failed ? window.__adminStrings.failed : 'Failed to load.') + '</p>';
                }
            }).catch(function (error) {
                const message = (error.response && error.response.status === 401)
                    ? 'Please sign in again.'
                    : (error.response && error.response.data && error.response.data.message)
                        ? error.response.data.message
                        : (window.__adminStrings && window.__adminStrings.failed ? window.__adminStrings.failed : 'Failed to load.');
                listEl.innerHTML = '<p class="px-4 py-6 text-center text-sm text-red-500">' + message + '</p>';
            });
        }

        function initAdminNotificationDropdown() {
            const button = document.getElementById('admin-notif-btn');
            const dropdown = document.getElementById('admin-notif-dropdown');
            const wrap = document.getElementById('admin-notif-wrap');
            const markAll = document.getElementById('admin-notif-mark-all');
            if (!button || !dropdown) {
                return;
            }
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                dropdown.classList.toggle('hidden');
                if (!dropdown.classList.contains('hidden')) {
                    loadAdminNotificationDropdown(1);
                }
            });
            document.addEventListener('click', function (event) {
                if (wrap && !wrap.contains(event.target)) {
                    dropdown.classList.add('hidden');
                }
            });
            markAll && markAll.addEventListener('click', function () {
                markAll.disabled = true;
                if (window.Auth && window.Auth.applyToken) {
                    window.Auth.applyToken();
                }
                window.axios.post('/api/notifications/mark-all-read').then(function () {
                    loadAdminNotificationDropdown(1);
                    adminNotificationBadge();
                }).finally(function () {
                    markAll.disabled = false;
                });
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
