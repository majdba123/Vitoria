@extends('layouts.app')

@section('title', __('checkout.title') . ' — Vetora')

@section('content')
{{--
    Single-page checkout (spec §7).

    A five-step wizard was rejected: the whole flow is address + review +
    payment, which fits on one screen and reads better on mobile than a
    stepper that hides the total behind a "next" button.

    Every figure rendered here comes from GET /api/checkout/summary. Nothing on
    this page computes a price.
--}}
<div class="min-h-screen bg-transparent">
    <div class="catalog-page-band">
        <div class="page-shell py-3">
            <nav class="page-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
                <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">{{ __('checkout.title') }}</span>
            </nav>
        </div>
    </div>

    <div class="page-shell py-6">
        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">{{ __('checkout.title') }}</h1>

        <div id="checkout-loading" class="py-20 text-center text-sm text-gray-500 dark:text-gray-400">
            <span class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-brand-500" role="status" aria-label="{{ __('common.loading') }}"></span>
        </div>

        <div id="checkout-empty" class="hidden rounded-xl border border-gray-200 bg-white p-10 text-center dark:border-gray-800 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-700 dark:text-gray-200">{{ __('checkout.empty_cart') }}</p>
            <a href="{{ route('products.index') }}" class="mt-4 inline-flex rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600">
                {{ __('checkout.back_to_shopping') }}
            </a>
        </div>

        <div id="checkout-body" class="hidden grid gap-6 lg:grid-cols-[1fr_360px] lg:items-start">
            {{-- ── Left column ── --}}
            <div class="space-y-6">
                {{-- Delivery address --}}
                <section class="surface-card p-5" aria-labelledby="checkout-address-heading">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 id="checkout-address-heading" class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('checkout.delivery_address') }}</h2>
                        <button type="button" id="checkout-new-address-btn" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            {{ __('checkout.new_address') }}
                        </button>
                    </div>

                    <div id="checkout-addresses" class="space-y-2"></div>

                    <p id="checkout-no-addresses" class="hidden text-sm text-gray-500 dark:text-gray-400">{{ __('checkout.no_addresses') }}</p>

                    {{-- Inline new-address form: keeps the shopper on the page --}}
                    <form id="checkout-address-form" class="mt-4 hidden space-y-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="addr-label" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.field.label') }}</label>
                                <select id="addr-label" name="label" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    @foreach ($addressLabels as $label)
                                        <option value="{{ $label }}">{{ __("addresses.label.{$label}") }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="addr-recipient_name" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.field.recipient_name') }}</label>
                                <input id="addr-recipient_name" name="recipient_name" type="text" required class="form-input">
                            </div>
                            <div>
                                <label for="addr-phone" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.field.phone') }}</label>
                                <input id="addr-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required dir="ltr" class="form-input">
                            </div>
                            <div>
                                <label for="addr-governorate" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.field.governorate') }}</label>
                                <input id="addr-governorate" name="governorate" type="text" required class="form-input">
                            </div>
                            <div>
                                <label for="addr-city" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.field.city') }}</label>
                                <input id="addr-city" name="city" type="text" required class="form-input">
                            </div>
                            <div>
                                <label for="addr-district" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.field.district') }}</label>
                                <input id="addr-district" name="district" type="text" class="form-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="addr-street" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.field.street') }}</label>
                                <input id="addr-street" name="street" type="text" class="form-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="addr-notes" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.field.notes') }}</label>
                                <textarea id="addr-notes" name="notes" rows="2" class="form-textarea"></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary">{{ __('checkout.save_address') }}</button>
                            <button type="button" id="checkout-address-cancel" class="btn-secondary">{{ __('checkout.cancel') }}</button>
                        </div>
                    </form>
                </section>

                {{-- Order review --}}
                <section class="surface-card p-5" aria-labelledby="checkout-items-heading">
                    <h2 id="checkout-items-heading" class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('checkout.order_review') }}</h2>
                    <p id="checkout-multi-vendor" class="mb-4 hidden rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
                        {{ __('checkout.multi_vendor_notice') }}
                    </p>
                    <div id="checkout-items" class="divide-y divide-gray-100 dark:divide-gray-800"></div>
                </section>

                {{-- Payment --}}
                <section class="surface-card p-5" aria-labelledby="checkout-payment-heading">
                    <h2 id="checkout-payment-heading" class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('checkout.payment_method') }}</h2>
                    <div id="checkout-payment-methods" class="space-y-2"></div>
                </section>
            </div>

            {{-- ── Summary rail ── --}}
            <aside class="surface-card p-5 lg:sticky lg:top-24" aria-labelledby="checkout-summary-heading">
                <h2 id="checkout-summary-heading" class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('checkout.total') }}</h2>

                <div class="mb-4">
                    <label for="checkout-coupon" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('checkout.discount_code') }}</label>
                    <div class="flex gap-2">
                        <input id="checkout-coupon" type="text" placeholder="{{ __('checkout.coupon_placeholder') }}"
                            class="form-input min-w-0 flex-1">
                        <button type="button" id="checkout-coupon-apply" class="btn-secondary shrink-0">
                            {{ __('checkout.apply') }}
                        </button>
                    </div>
                </div>

                <dl class="space-y-2 border-t border-gray-100 pt-4 text-sm dark:border-gray-800">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('checkout.subtotal') }}</dt>
                        <dd id="sum-subtotal" class="font-medium tabular-nums text-gray-900 dark:text-white">—</dd>
                    </div>
                    <div id="sum-discount-row" class="flex hidden justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('checkout.discount') }}</dt>
                        <dd id="sum-discount" class="font-medium tabular-nums text-emerald-600 dark:text-emerald-400">—</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('checkout.shipping') }}</dt>
                        <dd id="sum-shipping" class="font-medium tabular-nums text-gray-900 dark:text-white">—</dd>
                    </div>
                    <div id="sum-tax-row" class="flex hidden justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('checkout.tax') }}</dt>
                        <dd id="sum-tax" class="font-medium tabular-nums text-gray-900 dark:text-white">—</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                        <dt class="font-semibold text-gray-900 dark:text-white">{{ __('checkout.total') }}</dt>
                        <dd id="sum-total" class="text-lg font-bold tabular-nums text-gray-900 dark:text-white">—</dd>
                    </div>
                </dl>

                <div id="checkout-error" class="mt-4 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300" role="alert"></div>

                <button type="button" id="checkout-place-order"
                    class="btn-primary mt-5 w-full">
                    {{ __('checkout.place_order') }}
                </button>
            </aside>
        </div>

        {{-- Success state --}}
        <div id="checkout-success" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-8 text-center dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <h2 class="text-lg font-bold text-emerald-800 dark:text-emerald-300">{{ __('checkout.order_placed') }}</h2>
            <p id="checkout-success-message" class="mt-2 text-sm text-emerald-700 dark:text-emerald-300"></p>
            <div id="checkout-success-orders" class="mx-auto mt-4 max-w-sm space-y-1 text-sm text-emerald-800 dark:text-emerald-200"></div>
            <a href="{{ route('profile') }}" class="btn-primary mt-6">
                {{ __('checkout.view_orders') }}
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @php
        $checkoutStrings = [
            'cash' => __('checkout.cash_on_delivery'),
            'cash_hint' => __('checkout.cash_on_delivery_hint'),
            'only_method' => __('checkout.only_method_available'),
            'address_required' => __('checkout.address_required'),
            'placing' => __('checkout.placing_order'),
            'place_order' => __('checkout.place_order'),
            'sold_by' => __('checkout.sold_by'),
            'qty' => __('checkout.quantity_short'),
            'shipping_free' => __('checkout.shipping_free'),
            'change' => __('checkout.change_address'),
            'default' => __('addresses.default'),
        ];
    @endphp
    <script>window.__checkoutStrings = @json($checkoutStrings);</script>
    @vite('resources/js/entries/checkout.js')
@endpush
