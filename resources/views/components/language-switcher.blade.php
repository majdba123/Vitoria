@props([
    'variant' => 'default', // 'default' (navbar) | 'compact' (admin: icon only)
])
@php
    $current = app()->getLocale();
    $isAr = $current === 'ar';
    $id = 'lang-switcher-' . bin2hex(random_bytes(4));
@endphp
<div class="relative" id="{{ $id }}">
    <button
        type="button"
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="{{ __('lang.choose_language') }}"
        data-lang-toggle
        class="{{ $variant === 'compact' ? 'h-8 w-8' : 'h-9 min-w-[2.25rem] px-2' }} flex items-center justify-center gap-1.5 rounded-xl text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
    >
        <svg class="{{ $variant === 'compact' ? 'h-4 w-4' : 'h-5 w-5' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
        </svg>
        @if($variant === 'default')
            <span class="hidden text-sm font-medium sm:inline">{{ $isAr ? __('lang.arabic') : __('lang.english') }}</span>
            <svg class="lang-chevron h-3.5 w-3.5 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
        @endif
    </button>
    <div
        data-lang-dropdown
        class="absolute top-full z-50 mt-2 hidden min-w-[10rem] overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900 dark:ring-white/10 rtl:right-0 rtl:left-auto"
    >
        <a href="{{ route('locale.switch', ['locale' => 'ar']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/80 {{ $isAr ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 dark:text-gray-300' }}">
            @if($isAr)
                <svg class="h-4 w-4 shrink-0 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            @endif
            <span class="{{ $isAr ? '' : 'rtl:pl-8' }}">{{ __('lang.arabic') }}</span>
        </a>
        <a href="{{ route('locale.switch', ['locale' => 'en']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/80 {{ !$isAr ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 dark:text-gray-300' }}">
            @if(!$isAr)
                <svg class="h-4 w-4 shrink-0 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            @endif
            <span class="{{ !$isAr ? '' : 'rtl:pr-8' }}">{{ __('lang.english') }}</span>
        </a>
    </div>
</div>
<script>
(function() {
    var wrap = document.getElementById('{{ $id }}');
    if (!wrap) return;
    var btn = wrap.querySelector('[data-lang-toggle]');
    var dropdown = wrap.querySelector('[data-lang-dropdown]');
    var chevron = wrap.querySelector('.lang-chevron');
    function open() {
        dropdown.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
    }
    function close() {
        dropdown.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
        if (chevron) chevron.style.transform = '';
    }
    function toggle() {
        if (dropdown.classList.contains('hidden')) open(); else close();
    }
    btn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggle(); });
    document.addEventListener('click', function() { close(); });
    wrap.addEventListener('click', function(e) { e.stopPropagation(); });
})();
</script>
