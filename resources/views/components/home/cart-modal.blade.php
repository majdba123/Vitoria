{{-- ═══ Cart Slide-over ═══ --}}
<div id="cart-modal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="window.closeCartModal && window.closeCartModal()"></div>
    <div class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl dark:bg-gray-900" style="animation:slideInRight .3s cubic-bezier(.22,1,.36,1);">
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-500/10"><svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg></div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('cart.shopping_cart') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400" id="cart-item-count" data-item="{{ __('common.item') }}" data-items="{{ __('common.items') }}">0 {{ __('common.items') }}</p>
                </div>
            </div>
            <button onclick="window.closeCartModal && window.closeCartModal()" class="rounded-xl p-2.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        {{-- Items --}}
        <div class="flex-1 overflow-y-auto px-6 py-4 hide-scrollbar">
            <div id="cart-items" class="space-y-3"></div>
            <div id="cart-empty" class="hidden py-20 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                <p class="mt-4 text-base font-bold text-gray-600 dark:text-gray-300">{{ __('common.your_cart_empty') }}</p>
                <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">{{ __('cart.empty_hint') }}</p>
            </div>
            <div id="cart-order-success" class="hidden py-12">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-black text-emerald-800 dark:text-emerald-300">Order Placed Successfully</h4>
                            <p id="cart-order-success-message" class="mt-1 text-xs font-medium leading-relaxed text-emerald-700 dark:text-emerald-300">Your order has been created.</p>
                            <p id="cart-order-success-note" class="mt-1 text-[11px] text-emerald-700/80 dark:text-emerald-300/80">You can review full details from your profile order history.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button type="button" id="cart-order-success-close" class="rounded-xl border border-emerald-300 bg-white px-3 py-2 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-50 dark:border-emerald-500/30 dark:bg-gray-900 dark:text-emerald-300 dark:hover:bg-emerald-500/10">
                            Continue Shopping
                        </button>
                        <a href="/profile" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700">
                            View Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
        {{-- Footer --}}
        <div class="border-t border-gray-200 bg-gray-50 px-6 py-5 dark:border-gray-800 dark:bg-gray-800/50">
            <div id="cart-backend-message" class="mb-3 hidden rounded-xl border px-3 py-2 text-xs font-semibold"></div>
            <div class="mb-3">
                <label for="cart-coupon-code" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('cart.coupon_label') }}</label>
                <input id="cart-coupon-code" type="text" placeholder="{{ __('cart.coupon_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none transition-colors focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>
            <p class="mb-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                Payment way: <span class="font-bold text-gray-900 dark:text-white">Cash only</span>
            </p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Subtotal</span>
                <span id="cart-total" class="text-xl font-black text-gray-900 dark:text-white">0.00 <span class="text-sm font-normal text-gray-400">SYP</span></span>
            </div>
            <button id="checkout-btn" class="mt-4 hidden w-full rounded-2xl bg-brand-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-500/20 transition-all hover:bg-brand-600 hover:shadow-xl active:scale-[.98]">{{ __('cart.proceed_checkout') }}</button>
        </div>
    </div>
</div>
