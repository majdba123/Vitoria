@php
    $isRtl = app()->getLocale() === 'ar';
    $sidebarEdgeClass = $isRtl ? 'right-0' : 'left-0';
    $sidebarHiddenClass = $isRtl ? 'translate-x-full' : '-translate-x-full';
    $mainPaddingClass = $isRtl ? 'lg:pr-72' : 'lg:pl-72';
    $closeMarginClass = $isRtl ? 'mr-auto' : 'ml-auto';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Employee - Vetora')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|sora:600,700,800|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />
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
        ? (new \App\Http\Resources\Auth\UserResource(auth()->user()->loadMissing()))->resolve(request())
        : null;
@endphp
<body class="dashboard-body min-h-screen font-sans text-gray-900 antialiased transition-colors duration-300 dark:text-gray-100">
    <div id="employee-app" class="hidden">
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-gray-950/55 backdrop-blur-sm transition-opacity dark:bg-black/70 lg:hidden" onclick="closeSidebar()"></div>

        <x-employee.sidebar />

        <div class="{{ $mainPaddingClass }}">
            <header class="dashboard-topbar sticky top-0 z-30">
                <div class="workspace-shell flex h-[78px] items-center gap-x-4">
                    <button type="button" id="sidebar-toggle" class="-m-2.5 flex h-10 w-10 items-center justify-center rounded-2xl text-gray-500 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200 lg:hidden" aria-label="Open sidebar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>

                    <div class="h-5 w-px bg-gray-200 dark:bg-gray-700 lg:hidden" aria-hidden="true"></div>

                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-cyan-600 dark:text-cyan-300">Employee workspace</p>
                        <h1 class="mt-1 truncate text-lg font-black text-gray-900 dark:text-white sm:text-xl">@yield('page-title', 'Dashboard')</h1>
                    </div>

                    <div class="flex items-center gap-x-3">
                        <a href="{{ route('home') }}" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 transition-colors hover:border-white/50 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-gray-200" title="{{ __('nav.home') }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                        </a>

                        <x-language-switcher variant="compact" />

                        <button onclick="toggleEmployeeTheme()" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-gray-500 transition-colors hover:border-white/50 hover:bg-white/70 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-gray-200" title="{{ __('nav.toggle_theme_aria') }}">
                            <svg id="employee-sun" class="hidden h-4 w-4 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                            <svg id="employee-moon" class="block h-4 w-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                        </button>

                        <button onclick="employeeLogout()" class="flex items-center gap-1.5 rounded-2xl px-3 py-2 text-sm font-semibold text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="{{ __('nav.sign_out') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                            <span class="hidden sm:inline">{{ __('nav.sign_out') }}</span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="workspace-shell py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="employee-loading" class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white/70 shadow-lg shadow-gray-900/5 backdrop-blur-md dark:bg-white/5 dark:shadow-black/20">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-cyan-500 dark:border-gray-700"></div>
            </div>
            <p class="mt-4 text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('common.loading') }}</p>
        </div>
    </div>

    <script>
        window.__sessionAuthUser = @json($sessionAuthUser);
        const employeeSidebarHiddenClass = @json($sidebarHiddenClass);
        function toggleEmployeeTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('sz_theme', isDark ? 'dark' : 'light');
        }
        async function employeeLogout() {
            try {
                await window.axios.post('/api/auth/logout');
            } catch (error) {}
            if (window.Auth?.clearAll) {
                window.Auth.clearAll();
            } else {
                localStorage.clear();
            }
            window.location.replace('{{ route("login") }}?logout=1');
        }

        document.addEventListener('DOMContentLoaded', async function () {
            if (window.__sessionAuthUser && window.Auth?.setUser) {
                window.Auth.setUser(window.__sessionAuthUser);
            }
            try {
                if (window.Auth?.applyToken) {
                    window.Auth.applyToken();
                }
                const response = await window.axios.get('/api/user');
                const user = response.data.data || response.data;
                if (user.type !== 4) {
                    window.Auth?.clearAll?.();
                    window.location.href = '{{ route("login") }}';
                    return;
                }

                window.Auth.setUser(user);
                document.getElementById('employee-loading').classList.add('hidden');
                document.getElementById('employee-app').classList.remove('hidden');
                document.dispatchEvent(new CustomEvent('employee-ready'));
            } catch (error) {
                window.Auth?.clearAll?.();
                window.location.href = '{{ route("login") }}';
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('sidebar-toggle')?.addEventListener('click', function () {
                document.getElementById('employee-sidebar').classList.remove(employeeSidebarHiddenClass);
                document.getElementById('sidebar-backdrop').classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            });
        });

        function closeSidebar() {
            document.getElementById('employee-sidebar').classList.add(employeeSidebarHiddenClass);
            document.getElementById('sidebar-backdrop').classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
        }
    </script>

    @stack('scripts')
</body>
</html>
