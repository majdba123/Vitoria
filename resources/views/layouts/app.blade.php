<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Vetora'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preload" href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet"></noscript>
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#030712" media="(prefers-color-scheme: dark)">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
<body class="min-h-screen bg-white text-gray-900 antialiased transition-colors duration-300 dark:bg-gray-950 dark:text-gray-100">
    @php
        $appStrings = [
            'fav_added' => __('common.added_to_favourites'),
            'fav_removed' => __('common.removed_from_favourites'),
            'cart_added' => __('cart.added_to_cart_toast'),
            'cart_empty_msg' => __('common.your_cart_empty'),
            'order_created' => __('cart.order_created_fallback'),
            'checkout_failed' => __('cart.checkout_failed'),
            'checkout_ok' => __('cart.checkout_success_fallback'),
            'processing' => __('cart.processing_checkout'),
            'cart_remove' => __('cart.remove_line'),
        ];
    @endphp
    <script>window.__appStrings = @json($appStrings);</script>

    <x-navbar />

    <main>@yield('content')</main>

    {{-- ═══ Global Cart Modal ═══ --}}
    <x-home.cart-modal />

    <div id="startup-modal" class="fixed inset-0 z-[100] hidden bg-gray-950/70 p-4 backdrop-blur-sm">
        <div class="mx-auto flex min-h-full max-w-xl items-center justify-center">
            <div class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-100 px-6 py-5 dark:border-gray-800">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">Vetora</p>
                    <h2 id="startup-title" class="mt-1 text-xl font-black text-gray-900 dark:text-white">اضبط تفضيلاتك</h2>
                </div>
                <div class="px-6 py-5">
                    <div class="mb-5 grid grid-cols-2 gap-2">
                        <div id="startup-step-location-indicator" class="h-1.5 rounded-full bg-brand-500"></div>
                        <div id="startup-step-timezone-indicator" class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                    <div id="startup-location-step" class="space-y-4">
                        <div>
                            <label for="startup-location" class="mb-1.5 block text-sm font-bold text-gray-800 dark:text-gray-200">العنوان أو تفضيل الموقع</label>
                            <input id="startup-location" type="text" class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="المدينة، المنطقة، أو تفضيل التوصيل">
                        </div>
                        <button type="button" id="startup-use-browser-location" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-brand-500/10">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            استخدام موقع المتصفح
                        </button>
                        <input id="startup-latitude" type="hidden">
                        <input id="startup-longitude" type="hidden">
                    </div>
                    <div id="startup-timezone-step" class="hidden space-y-4">
                        <div>
                            <label for="startup-timezone" class="mb-1.5 block text-sm font-bold text-gray-800 dark:text-gray-200">المنطقة الزمنية</label>
                            <select id="startup-timezone" class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></select>
                        </div>
                    </div>
                    <p id="startup-error" class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"></p>
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                    <button type="button" id="startup-back" class="hidden rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">رجوع</button>
                    <button type="button" id="startup-next" class="ms-auto rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-black text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">متابعة</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Footer ═══ --}}
    <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-2xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 sm:grid-cols-2 lg:grid-cols-5">
                {{-- Brand --}}
                <div class="col-span-2 sm:col-span-2 lg:col-span-2">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72"/></svg>
                        </div>
                        Vetora
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        {{ $footerSettings->about_description ?? __('home.tagline') }}
                    </p>
                    <div class="mt-5 flex gap-3">
                        @if(!empty($footerSettings->facebook_url))
                            <a href="{{ $footerSettings->facebook_url }}" target="_blank" rel="noopener noreferrer" class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-200 text-gray-600 transition-all hover:bg-brand-500 hover:text-white dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-brand-500 dark:hover:text-white" aria-label="Facebook">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                        @endif
                        @if(!empty($footerSettings->instagram_url))
                            <a href="{{ $footerSettings->instagram_url }}" target="_blank" rel="noopener noreferrer" class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-200 text-gray-600 transition-all hover:bg-brand-500 hover:text-white dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-brand-500 dark:hover:text-white" aria-label="Instagram">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            </a>
                        @endif
                        @if(!empty($footerSettings->twitter_url))
                            <a href="{{ $footerSettings->twitter_url }}" target="_blank" rel="noopener noreferrer" class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-200 text-gray-600 transition-all hover:bg-brand-500 hover:text-white dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-brand-500 dark:hover:text-white" aria-label="Twitter">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                        @endif
                    </div>
                </div>
                {{-- Quick Links --}}
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-widest text-gray-900 dark:text-gray-200">{{ __('footer.shop') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('categories.index') }}" class="text-gray-500 transition-colors hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">{{ __('nav.categories') }}</a></li>
                        <li><a href="{{ route('products.index') }}" class="text-gray-500 transition-colors hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">{{ __('footer.all_products') }}</a></li>
                    </ul>
                </div>
                {{-- Account --}}
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-widest text-gray-900 dark:text-gray-200">{{ __('footer.account') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('login') }}" class="text-gray-500 transition-colors hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">{{ __('nav.sign_in') }}</a></li>
                        <li><a href="{{ route('register') }}" class="text-gray-500 transition-colors hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">{{ __('footer.create_account') }}</a></li>
                    </ul>
                </div>
                {{-- Contact --}}
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-widest text-gray-900 dark:text-gray-200">{{ __('footer.contact') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('home') }}#contact" class="text-gray-500 transition-colors hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">{{ __('footer.contact_us') }}</a></li>
                        @if(!empty($footerSettings->contact_email))
                            <li class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                <a href="mailto:{{ e($footerSettings->contact_email) }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ e($footerSettings->contact_email) }}</a>
                            </li>
                        @endif
                        @if(!empty($footerSettings->contact_address))
                            <li class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                {{ e($footerSettings->contact_address) }}
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-gray-200 pt-8 sm:flex-row dark:border-gray-800">
                <p class="text-xs text-gray-400 dark:text-gray-500">&copy; {{ date('Y') }} Vetora. {{ __('footer.rights') }}</p>
                <div class="flex gap-6 text-xs text-gray-400 dark:text-gray-500">
                    <a href="#" class="hover:text-gray-600 dark:hover:text-gray-300">{{ __('footer.privacy') }}</a>
                    <a href="#" class="hover:text-gray-600 dark:hover:text-gray-300">{{ __('footer.terms') }}</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- ═══ Global Favourites Logic ═══ --}}
    <script>
    (function(){
        window._favIds = new Set();
        window._favLoaded = false;

        window.loadFavIds = async function() {
            if (!window.Auth || !window.Auth.isAuthenticated()) return;
            try {
                const res = await window.axios.get('/api/favourites/ids');
                window._favIds = new Set(res.data.data || []);
                window._favLoaded = true;
                document.querySelectorAll('[data-fav-btn]').forEach(btn => {
                    const id = parseInt(btn.dataset.favBtn);
                    updateFavBtn(btn, window._favIds.has(id));
                });
            } catch(e) {}
        };

        window.toggleFav = async function(productId, btn) {
            if (!window.Auth || !window.Auth.isAuthenticated()) {
                window.location.href = '/login';
                return;
            }
            try {
                const res = await window.axios.post('/api/favourites/' + productId);
                const isFav = res.data.favourited;
                if (isFav) window._favIds.add(productId); else window._favIds.delete(productId);
                document.querySelectorAll('[data-fav-btn="' + productId + '"]').forEach(b => updateFavBtn(b, isFav));
                const S = window.__appStrings || {};
                favToast(isFav ? (S.fav_added || '') : (S.fav_removed || ''));
            } catch(e) {}
        };

        function updateFavBtn(btn, isFav) {
            const svg = btn.querySelector('svg');
            if (!svg) return;
            if (isFav) {
                svg.setAttribute('fill', 'currentColor');
                btn.classList.add('text-red-500');
                btn.classList.remove('text-gray-400', 'dark:text-gray-500');
            } else {
                svg.setAttribute('fill', 'none');
                btn.classList.remove('text-red-500');
                btn.classList.add('text-gray-400', 'dark:text-gray-500');
            }
        }

        function favToast(msg) {
            const t = document.createElement('div');
            t.className = 'fixed bottom-6 left-6 z-[80] flex items-center gap-3 rounded-2xl bg-gray-900 px-6 py-4 text-sm font-medium text-white shadow-2xl dark:bg-white dark:text-gray-900';
            t.style.animation = 'fadeInUp .3s cubic-bezier(.22,1,.36,1)';
            t.innerHTML = '<svg class="h-5 w-5 text-red-400 dark:text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>' + msg;
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 2000);
        }

        document.addEventListener('DOMContentLoaded', () => window.loadFavIds());
    })();
    </script>

    <script>
    (function(){
        const modal = document.getElementById('startup-modal');
        const locationStep = document.getElementById('startup-location-step');
        const timezoneStep = document.getElementById('startup-timezone-step');
        const timezoneSelect = document.getElementById('startup-timezone');
        const nextBtn = document.getElementById('startup-next');
        const backBtn = document.getElementById('startup-back');
        const errorEl = document.getElementById('startup-error');
        const titleEl = document.getElementById('startup-title');
        const locIndicator = document.getElementById('startup-step-location-indicator');
        const tzIndicator = document.getElementById('startup-step-timezone-indicator');
        let step = 'location';

        function showError(message) {
            if (!message) {
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
                return;
            }

            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }

        function showStep(nextStep) {
            step = nextStep;
            showError('');
            const isTimezone = step === 'timezone';
            locationStep.classList.toggle('hidden', isTimezone);
            timezoneStep.classList.toggle('hidden', !isTimezone);
            backBtn.classList.toggle('hidden', !isTimezone);
            nextBtn.textContent = isTimezone ? 'إنهاء الإعداد' : 'متابعة';
            titleEl.textContent = isTimezone ? 'اختر المنطقة الزمنية' : 'اضبط تفضيلاتك';
            locIndicator.className = 'h-1.5 rounded-full ' + (isTimezone ? 'bg-brand-500/50' : 'bg-brand-500');
            tzIndicator.className = 'h-1.5 rounded-full ' + (isTimezone ? 'bg-brand-500' : 'bg-gray-200 dark:bg-gray-700');
        }

        async function initStartupFlow() {
            if (localStorage.getItem('sz_startup_completed') === '1') {
                return;
            }

            try {
                const response = await window.axios.get('/api/startup/preferences');
                const data = response.data.data || {};

                if (data.completed) {
                    localStorage.setItem('sz_startup_completed', '1');
                    return;
                }

                const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || data.default_timezone || 'Asia/Damascus';
                timezoneSelect.innerHTML = (data.timezones || []).map(tz => `<option value="${tz.value}">${tz.label}</option>`).join('');
                timezoneSelect.value = data.timezone || browserTimezone;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            } catch (error) {}
        }

        document.getElementById('startup-use-browser-location')?.addEventListener('click', function () {
            if (!navigator.geolocation) {
                showError('موقع المتصفح غير متاح.');
                return;
            }

            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('startup-latitude').value = position.coords.latitude;
                document.getElementById('startup-longitude').value = position.coords.longitude;
                document.getElementById('startup-location').value = 'تم اختيار موقع المتصفح';
            }, function() {
                showError('تعذر قراءة موقع المتصفح. يمكنك كتابة الموقع يدوياً.');
            }, { enableHighAccuracy: false, timeout: 6000 });
        });

        backBtn?.addEventListener('click', () => showStep('location'));
        nextBtn?.addEventListener('click', async function () {
            if (step === 'location') {
                showStep('timezone');
                return;
            }

            nextBtn.disabled = true;
            try {
                await window.axios.post('/api/startup/preferences', {
                    timezone: timezoneSelect.value,
                    location_preference: document.getElementById('startup-location').value || null,
                    latitude: document.getElementById('startup-latitude').value || null,
                    longitude: document.getElementById('startup-longitude').value || null,
                });
                localStorage.setItem('sz_startup_completed', '1');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            } catch (error) {
                showError(error.response?.data?.message || 'تعذر حفظ التفضيلات.');
            } finally {
                nextBtn.disabled = false;
            }
        });

        document.addEventListener('DOMContentLoaded', initStartupFlow);
    })();
    </script>

    {{-- ═══ Global Cart Logic ═══ --}}
    <script>
    (function(){
        function _esc(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
        function toast(msg){const t=document.createElement('div');t.className='fixed bottom-6 right-6 z-[80] flex items-center gap-3 rounded-2xl bg-gray-900 px-6 py-4 text-sm font-medium text-white shadow-2xl dark:bg-white dark:text-gray-900';t.style.animation='fadeInUp .3s cubic-bezier(.22,1,.36,1)';t.innerHTML=`<svg class="h-5 w-5 text-emerald-400 dark:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>${_esc(msg)}`;document.body.appendChild(t);setTimeout(()=>{t.style.opacity='0';t.style.transition='opacity .3s';setTimeout(()=>t.remove(),300);},2500);}
        function showCartBackendMessage(msg, type = 'info') {
            const el = document.getElementById('cart-backend-message');
            if (!el) return;
            if (!msg) {
                el.textContent = '';
                el.className = 'mb-3 hidden rounded-xl border px-3 py-2 text-xs font-semibold';
                return;
            }
            const classes = {
                success: 'mb-3 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-2 text-xs font-semibold dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
                error: 'mb-3 rounded-xl border border-red-200 bg-red-50 text-red-700 px-3 py-2 text-xs font-semibold dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
                info: 'mb-3 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 px-3 py-2 text-xs font-semibold dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
            };
            el.className = classes[type] || classes.info;
            el.textContent = msg;
        }

        function showOrderSuccessState(message) {
            const successWrap = document.getElementById('cart-order-success');
            const successMessage = document.getElementById('cart-order-success-message');
            const cartItems = document.getElementById('cart-items');
            const cartEmpty = document.getElementById('cart-empty');
            const checkoutBtn = document.getElementById('checkout-btn');

            if (successMessage) {
                const _os = window.__appStrings || {};
                successMessage.textContent = message || _os.order_created || '';
            }
            if (successWrap) {
                successWrap.classList.remove('hidden');
            }
            if (cartItems) {
                cartItems.innerHTML = '';
            }
            if (cartEmpty) {
                cartEmpty.classList.add('hidden');
            }
            if (checkoutBtn) {
                checkoutBtn.classList.add('hidden');
            }
        }

        function hideOrderSuccessState() {
            const successWrap = document.getElementById('cart-order-success');
            if (successWrap) {
                successWrap.classList.add('hidden');
            }
        }

        window.addToCart = function(id,name,price,photo){
            let cart=JSON.parse(localStorage.getItem('cart')||'[]');
            const ex=cart.find(i=>i.id===id);
            if(ex)ex.quantity+=1;else cart.push({id,name,price:parseFloat(price),photo:photo||'',quantity:1});
            localStorage.setItem('cart',JSON.stringify(cart));
            window.dispatchEvent(new CustomEvent('cartUpdated'));
            window._refreshCartDisplay&&window._refreshCartDisplay();
            if(typeof window.updateCartBadge==='function')window.updateCartBadge(true);
            const _ac = window.__appStrings || {};
            toast(_ac.cart_added || '');
        };
        window.removeFromCart = function(id){
            let cart=JSON.parse(localStorage.getItem('cart')||'[]').filter(i=>i.id!==id);
            localStorage.setItem('cart',JSON.stringify(cart));
            window.dispatchEvent(new CustomEvent('cartUpdated'));
            window._refreshCartDisplay&&window._refreshCartDisplay();
            if(typeof window.updateCartBadge==='function')window.updateCartBadge();
        };
        window.updateQty = function(id,qty){
            if(qty<=0){window.removeFromCart(id);return;}
            let cart=JSON.parse(localStorage.getItem('cart')||'[]');
            const item=cart.find(i=>i.id===id);
            if(item){item.quantity=qty;localStorage.setItem('cart',JSON.stringify(cart));window.dispatchEvent(new CustomEvent('cartUpdated'));window._refreshCartDisplay&&window._refreshCartDisplay();if(typeof window.updateCartBadge==='function')window.updateCartBadge();}
        };

        window._refreshCartDisplay = function(){
            const cart=JSON.parse(localStorage.getItem('cart')||'[]');
            const items=document.getElementById('cart-items'),empty=document.getElementById('cart-empty'),total=document.getElementById('cart-total'),checkout=document.getElementById('checkout-btn'),count=document.getElementById('cart-item-count');
            if(!items)return;
            const _rm=(window.__appStrings&&window.__appStrings.cart_remove)?window.__appStrings.cart_remove:'Remove';
            if(!cart.length){items.innerHTML='';empty&&empty.classList.remove('hidden');if(total)total.innerHTML='0.00 <span class="text-sm font-normal text-gray-400">SYP</span>';checkout&&checkout.classList.add('hidden');if(count)count.textContent='0 '+(count.getAttribute('data-items')||'items');return;}
            empty&&empty.classList.add('hidden');checkout&&checkout.classList.remove('hidden');
            const n=cart.reduce((s,i)=>s+i.quantity,0);if(count)count.textContent=n+' '+(n===1?(count.getAttribute('data-item')||'item'):(count.getAttribute('data-items')||'items'));
            let t=0;
            items.innerHTML=cart.map(item=>{const sub=item.price*item.quantity;t+=sub;return `<div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 dark:border-gray-800 dark:bg-gray-800/50"><div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-gray-50 dark:bg-gray-800">${item.photo?`<img src="${_esc(item.photo)}" class="h-full w-full object-contain p-1" alt="">`:`<div class="flex h-full items-center justify-center"><svg class="h-5 w-5 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}</div><div class="min-w-0 flex-1"><h4 class="truncate text-sm font-bold text-gray-900 dark:text-white">${_esc(item.name)}</h4><p class="text-xs text-gray-500">${item.price.toLocaleString()} SYP</p><p class="text-xs font-bold text-brand-600 dark:text-brand-400">${sub.toLocaleString()} SYP</p></div><div class="flex flex-col items-end gap-2"><div class="flex items-center rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"><button onclick="updateQty(${item.id},${item.quantity-1})" class="flex h-7 w-7 items-center justify-center text-gray-500 hover:text-brand-600"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M19.5 12h-15"/></svg></button><span class="w-6 text-center text-xs font-bold dark:text-white">${item.quantity}</span><button onclick="updateQty(${item.id},${item.quantity+1})" class="flex h-7 w-7 items-center justify-center text-gray-500 hover:text-brand-600"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg></button></div><button onclick="removeFromCart(${item.id})" class="text-[10px] font-semibold text-red-500 hover:text-red-700">${_esc(_rm)}</button></div></div>`;}).join('');
            if(total)total.innerHTML=t.toLocaleString()+' <span class="text-sm font-normal text-gray-400">SYP</span>';
        };

        window.checkoutCart = async function(){
            const _S = window.__appStrings || {};
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            if (!cart.length) {
                const emptyMsg = _S.cart_empty_msg || '';
                showCartBackendMessage(emptyMsg, 'error');
                toast(emptyMsg);
                return;
            }

            if (!window.Auth || !window.Auth.isAuthenticated()) {
                window.location.href = '/login';
                return;
            }

            const checkoutBtn = document.getElementById('checkout-btn');
            const originalLabel = checkoutBtn ? checkoutBtn.innerHTML : '';

            if (checkoutBtn) {
                checkoutBtn.disabled = true;
                checkoutBtn.innerHTML = _S.processing || '';
            }

            try {
                const couponInput = document.getElementById('cart-coupon-code');
                const couponCode = couponInput ? String(couponInput.value || '').trim().toUpperCase() : '';
                const payload = {
                    items: cart.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity || 1,
                    })),
                    coupon_code: couponCode || null,
                    payment_way: 'cash',
                };

                const response = await window.axios.post('/api/orders/checkout', payload);
                const successMessage = response.data?.message || _S.checkout_ok || '';
                showCartBackendMessage(successMessage, 'success');
                localStorage.removeItem('cart');
                if (couponInput) couponInput.value = '';
                window.dispatchEvent(new CustomEvent('cartUpdated'));
                window._refreshCartDisplay && window._refreshCartDisplay();
                showOrderSuccessState(successMessage);
                toast(successMessage);
            } catch (error) {
                const backendMessage = error.response?.data?.message;
                const backendErrors = error.response?.data?.errors;
                const firstValidation = backendErrors
                    ? Object.values(backendErrors).flat().find(Boolean)
                    : null;
                const message = firstValidation || backendMessage || _S.checkout_failed || '';
                showCartBackendMessage(message, 'error');
                toast(message);
            } finally {
                if (checkoutBtn) {
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerHTML = originalLabel;
                }
            }
        };

        window.showCart = function(){window._refreshCartDisplay();hideOrderSuccessState();showCartBackendMessage('');const m=document.getElementById('cart-modal');if(m){m.classList.remove('hidden');document.body.style.overflow='hidden';}};
        window.closeCartModal = function(){
            hideOrderSuccessState();
            showCartBackendMessage('');
            const m=document.getElementById('cart-modal');
            if(m){m.classList.add('hidden');}
            document.body.style.overflow='';
        };
        document.addEventListener('DOMContentLoaded', function () {
            const checkoutBtn = document.getElementById('checkout-btn');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function () {
                    window.checkoutCart && window.checkoutCart();
                });
            }
            const closeSuccessBtn = document.getElementById('cart-order-success-close');
            if (closeSuccessBtn) {
                closeSuccessBtn.addEventListener('click', function () {
                    window.closeCartModal && window.closeCartModal();
                });
            }
        });
    })();
    </script>

    @stack('scripts')
</body>
</html>
