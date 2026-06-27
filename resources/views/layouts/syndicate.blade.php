@php
    $isRtl = app()->getLocale() === 'ar';
    $sidebarEdgeClass = $isRtl ? 'right-0' : 'left-0';
    $sidebarHiddenClass = $isRtl ? 'translate-x-full' : '-translate-x-full';
    $mainPaddingClass = $isRtl ? 'lg:pr-72' : 'lg:pl-72';
    $closeMarginClass = $isRtl ? 'mr-auto' : 'ml-auto';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة النقابة - Vetora')</title>
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
        ? (new \App\Http\Resources\Auth\UserResource(auth()->user()->loadMissing('syndicate')))->resolve(request())
        : null;
@endphp
<body
    data-session-auth="{{ auth()->check() ? '1' : '0' }}"
    class="dashboard-body min-h-screen font-sans text-gray-900 antialiased dark:text-gray-100"
>
    <div id="syndicate-app" class="hidden">
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-gray-950/55 backdrop-blur-sm lg:hidden" onclick="closeSidebar()"></div>
        <aside id="syndicate-sidebar" class="dashboard-sidebar fixed inset-y-0 {{ $sidebarEdgeClass }} z-50 flex w-72 {{ $sidebarHiddenClass }} flex-col lg:translate-x-0">
            <div class="flex h-[88px] items-center gap-3 border-b border-white/8 px-6">
                <a href="{{ route('syndicate.dashboard') }}" class="flex items-center gap-3 text-white">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-teal-700 shadow-lg shadow-cyan-500/20">
                        <i class="fa-solid fa-users-rays text-sm"></i>
                    </span>
                    <span>
                        <span class="block font-display text-xl font-extrabold">Vetora</span>
                        <span class="mt-1 block text-[11px] font-extrabold uppercase tracking-[0.28em] text-cyan-200">Syndicate</span>
                    </span>
                </a>
                <button onclick="closeSidebar()" class="{{ $closeMarginClass }} rounded-2xl p-2 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @php
                $currentRoute = request()->route()?->getName() ?? '';
                $links = [
                    ['route' => 'syndicate.dashboard', 'label' => 'نظرة عامة', 'icon' => 'fa-solid fa-grid-2'],
                    ['route' => 'syndicate.categories', 'label' => 'التصنيفات', 'icon' => 'fa-solid fa-layer-group'],
                    ['route' => 'syndicate.vendors', 'label' => 'التجار', 'icon' => 'fa-solid fa-store'],
                    ['route' => 'syndicate.products', 'label' => 'المنتجات', 'icon' => 'fa-solid fa-box-open'],
                    ['route' => 'syndicate.podcasts', 'label' => 'البودكاست', 'icon' => 'fa-solid fa-microphone-lines'],
                    ['route' => 'syndicate.orders', 'label' => 'الطلبات', 'icon' => 'fa-solid fa-bag-shopping'],
                    ['route' => 'syndicate.sales', 'label' => 'المبيعات', 'icon' => 'fa-solid fa-chart-line'],
                    ['route' => 'syndicate.reports', 'label' => 'التقارير', 'icon' => 'fa-regular fa-chart-bar'],
                ];
            @endphp
            <div class="px-6 pt-5">
                <div class="rounded-[24px] border border-white/8 bg-white/5 p-4 text-white/80">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-white/45">Association layer</p>
                    <p class="mt-2 text-sm leading-6 text-white/75">Track type-specific network performance, category coverage, and sales intelligence from one secure view.</p>
                </div>
            </div>
            <nav class="min-h-0 flex-1 overflow-y-auto px-4 py-5">
                <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] text-white/35">Workspace</p>
                @foreach ($links as $link)
                    @php
                        $isActive = $currentRoute === $link['route'];
                    @endphp
                    <a href="{{ route($link['route']) }}" class="dashboard-sidebar-link {{ $isActive ? 'is-active' : '' }}">
                        <span class="dashboard-sidebar-bullet h-2.5 w-2.5 rounded-full bg-white/20"></span>
                        <i class="{{ $link['icon'] }} w-4 text-center text-[13px]"></i>
                        <span class="flex-1">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-white/8 px-6 py-4 text-[11px] text-white/40">
                <p>&copy; {{ date('Y') }} Vetora</p>
            </div>
        </aside>
        <div class="{{ $mainPaddingClass }}">
            <header class="dashboard-topbar sticky top-0 z-30">
                <div class="workspace-shell flex h-[78px] items-center gap-4">
                    <button type="button" id="sidebar-toggle" class="-m-2.5 flex h-10 w-10 items-center justify-center rounded-2xl text-gray-500 hover:bg-white/70 dark:hover:bg-white/5 lg:hidden">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-cyan-600 dark:text-cyan-300">Syndicate hub</p>
                        <h1 class="mt-1 truncate text-lg font-black text-gray-900 dark:text-white">@yield('page-title', 'النقابة')</h1>
                    </div>
                    <span id="syndicate-type-badge" class="badge badge-brand"></span>
                    <button onclick="syndicateLogout()" class="btn-secondary btn-sm">تسجيل الخروج</button>
                </div>
            </header>
            <main class="workspace-shell py-8">@yield('content')</main>
        </div>
    </div>
    <div id="syndicate-loading" class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white/70 shadow-lg shadow-gray-900/5 backdrop-blur-md dark:bg-white/5 dark:shadow-black/20">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-cyan-500"></div>
            </div>
            <p class="mt-4 text-sm font-semibold text-gray-500">جارٍ التحقق من صلاحية النقابة...</p>
        </div>
    </div>
    <script>
        window.__sessionAuthUser = @json($sessionAuthUser);
        const syndicateHiddenClass = @json($sidebarHiddenClass);
        function deleteCookie(name) { document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/'; }
        function syndicateTypeLabel(type) {
            if (type === 'agriculture') return 'زراعي';
            if (type === 'veterinary') return 'بيطري';
            return 'نقابة';
        }
        window.syndicateLogout = async function() {
            try { await window.axios.post('/api/auth/logout'); } catch (error) {}
            if (window.Auth?.clearAll) window.Auth.clearAll(); else localStorage.clear();
            deleteCookie('XSRF-TOKEN'); deleteCookie('laravel_session');
            window.location.replace('{{ route("login") }}?logout=1');
        };
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
                if (user.type !== 3) { window.Auth.removeToken(); window.location.href = '{{ route("login") }}'; return; }
                document.getElementById('syndicate-type-badge').textContent = syndicateTypeLabel(user.syndicate?.type);
                document.getElementById('syndicate-loading').classList.add('hidden');
                document.getElementById('syndicate-app').classList.remove('hidden');
                document.dispatchEvent(new CustomEvent('syndicate-ready'));
            } catch (error) {
                if (window.Auth?.clearAll) {
                    window.Auth.clearAll();
                }
                window.location.href = '{{ route("login") }}';
            }
        });
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('sidebar-toggle')?.addEventListener('click', function () {
                document.getElementById('syndicate-sidebar').classList.remove(syndicateHiddenClass);
                document.getElementById('sidebar-backdrop').classList.remove('hidden');
            });
        });
        function closeSidebar() {
            document.getElementById('syndicate-sidebar').classList.add(syndicateHiddenClass);
            document.getElementById('sidebar-backdrop').classList.add('hidden');
        }
    </script>
    @stack('scripts')
</body>
</html>
