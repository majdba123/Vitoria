@php
    $isRtl = app()->getLocale() === 'ar';
    $vendorMainPaddingClass = $isRtl ? 'lg:pr-72' : 'lg:pl-72';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('vendor.workspace') . ' - Vetora')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
@php
    $sessionAuthUser = auth()->check()
        ? (new \App\Http\Resources\Auth\UserResource(auth()->user()->loadMissing('syndicate')))->resolve(request())
        : null;
    $vendorStrings = [
        'loading' => __('vendor.loading'),
        'mark_all_read' => __('vendor.mark_all_read'),
        'view_all' => __('vendor.view_all'),
        'no_notifications' => __('vendor.no_notifications'),
        'mark_one_read' => __('vendor.mark_one_read'),
        'failed' => __('vendor.failed_notifications'),
        'assigned_categories_empty' => __('vendor.assigned_categories_empty'),
        'failed_categories' => __('vendor.failed_categories'),
        'group_agriculture' => __('vendor.group_agriculture'),
        'group_veterinary' => __('vendor.group_veterinary'),
        'page' => __('nav.page'),
        'of' => __('nav.of'),
    ];
@endphp
<body
    data-session-auth="{{ auth()->check() ? '1' : '0' }}"
    class="dashboard-body min-h-screen font-sans text-gray-900 antialiased transition-colors duration-300 dark:text-gray-100"
>
    <a href="#main-content" class="skip-link">{{ __('common.skip_to_content') }}</a>

    <div id="vendor-app" class="hidden">
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-gray-950/60 transition-opacity dark:bg-black/70 lg:hidden" onclick="closeSidebar()"></div>

        <x-vendor.sidebar />

        <div class="{{ $vendorMainPaddingClass }}">
            <x-workspace.topbar
                context="vendor"
                :badge-label="__('vendor.workspace')"
                :open-sidebar-label="__('vendor.open_sidebar')"
                :notifications="true"
                notifications-route="vendor.notifications.index"
                :notifications-label="__('vendor.notifications')"
                :notif-strings="$vendorStrings"
                :avatar="true"
                avatar-fallback="V"
                :avatar-role-label="__('vendor.seller')"
                :theme-toggle-label="__('vendor.toggle_theme')"
                :sign-out-label="__('vendor.sign_out')"
                :login-url="route('login')"
            />

            <main id="main-content" class="workspace-shell py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="vendor-loading" class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-brand-600 dark:border-gray-700 dark:border-t-brand-300"></div>
            <p class="mt-4 text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('vendor.loading_store') }}</p>
        </div>
    </div>

    <script>
        window.__sessionAuthUser = @json($sessionAuthUser);
        window.__vendorStrings = @json($vendorStrings);

        function hydrateVendorDashboard(user) {
            if (!user) {
                return;
            }

            window.Auth?.setUser?.(user);
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
            VetoraWorkspace.initNotifications('vendor', @json($vendorStrings));
        }

        document.addEventListener('DOMContentLoaded', async function () {
            VetoraWorkspace.initSidebarToggle({ sidebarId: 'vendor-sidebar', hiddenClass: @json($isRtl ? 'translate-x-full' : '-translate-x-full') });

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('logout') === '1') {
                return;
            }

            if (window.__sessionAuthUser && window.Auth?.setUser) {
                window.Auth.setUser(window.__sessionAuthUser);
            }

            if (document.body?.dataset?.sessionAuth === '1' && window.__sessionAuthUser?.type === 2) {
                window.Auth?.clearTokenOnly?.();
                hydrateVendorDashboard(window.__sessionAuthUser);
                return;
            }

            try {
                if (window.Auth?.applyToken) {
                    window.Auth.applyToken();
                }
                const response = await window.axios.get('/api/user');
                const user = response.data.data || response.data;

                if (user.type !== 2) {
                    window.Auth.removeToken();
                    window.location.href = '{{ route("login") }}';
                    return;
                }

                hydrateVendorDashboard(user);
            } catch (error) {
                const sessionUser = window.__sessionAuthUser;
                if (document.body?.dataset?.sessionAuth === '1' && sessionUser?.type === 2) {
                    window.Auth?.clearTokenOnly?.();
                    hydrateVendorDashboard(sessionUser);
                    return;
                }

                if (window.Auth?.clearAll) {
                    window.Auth.clearAll();
                }

                window.location.href = '{{ route("login") }}';
            }
        });

        async function loadSidebarCategories() {
            const container = document.getElementById('sidebar-categories');
            if (!container) return;
            try {
                const response = await window.axios.get('/api/vendor/allowed-categories');
                const categories = response.data.data || [];
                if (categories.length === 0) {
                    container.innerHTML = '<p class="border border-white/8 bg-white/5 px-3 py-3 text-xs text-white/50 italic" style="border-radius: var(--radius-control)">' + (window.__vendorStrings?.assigned_categories_empty || '') + '</p>';
                    return;
                }
                container.innerHTML = categories.map((category) => {
                    const catId = `sidebar-cat-${category.id}`;
                    const subs = Array.isArray(category.subcategories) ? category.subcategories : [];
                    const groupLabel = category.type === 'veterinary'
                        ? (window.__vendorStrings?.group_veterinary || '')
                        : (window.__vendorStrings?.group_agriculture || '');
                    return `<div class="sidebar-cat-group">
                        <button type="button" onclick="toggleSidebarCat('${catId}')" class="mb-1 flex w-full items-center gap-3 px-3 py-2.5 text-left text-sm font-semibold text-white/75 transition-colors hover:bg-white/6 hover:text-white" style="border-radius: var(--radius-control)">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center bg-white/8 text-brand-200" style="border-radius: var(--radius-control)"><i class="fa-solid fa-layer-group text-sm"></i></span>
                            <span class="flex-1 truncate">${esc(category.name)}</span>
                            <span class="rounded-full bg-brand-500/15 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.18em] text-brand-300">${parseFloat(category.commission || 0).toFixed(0)}%</span>
                            <svg class="sidebar-cat-chevron h-4 w-4 shrink-0 transition-transform duration-200" id="${catId}-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <p class="mb-2 px-3 text-[11px] font-semibold text-white/40">${esc(groupLabel)}</p>
                        ${subs.length > 0 ? `<div id="${catId}" class="hidden ms-4 space-y-1 border-s border-white/10 ps-3 pb-2">
                            ${subs.map((sub) => `
                                <div class="flex items-center gap-2 px-2 py-2 text-xs text-white/55 transition-colors hover:bg-white/5 hover:text-white/80" style="border-radius: var(--radius-control)">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-300/70"></span>
                                    <span class="truncate">${esc(sub.name)}</span>
                                </div>
                            `).join('')}
                        </div>` : ''}
                    </div>`;
                }).join('');
            } catch (error) {
                container.innerHTML = '<p class="border border-danger-500/20 bg-danger-500/10 px-3 py-3 text-xs text-danger-300" style="border-radius: var(--radius-control)">' + (window.__vendorStrings?.failed_categories || '') + '</p>';
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

        function esc(value) { if (!value) return ''; const div = document.createElement('div'); div.textContent = value; return div.innerHTML; }
    </script>

    @stack('scripts')
</body>
</html>
