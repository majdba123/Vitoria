<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700,800|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Manrope', 'IBM Plex Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif;
            color: #1f2937;
            background: #fff;
            margin: 0;
            padding: 1.75rem;
            max-width: 780px;
            margin-inline: auto;
            font-size: 0.875rem;
            line-height: 1.35;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            border-bottom: 2.5px solid #297497;
            padding-bottom: 0.85rem;
            margin-bottom: 0.9rem;
        }
        .invoice-brand { display: flex; align-items: center; gap: 0.6rem; }
        .invoice-brand img { width: 46px; height: 46px; object-fit: contain; }
        .invoice-brand h1 { font-size: 1.25rem; margin: 0; color: #297497; line-height: 1.1; }
        .invoice-brand div { font-size: 0.8rem; color: #4b5563; }
        .invoice-meta { text-align: end; font-size: 0.8rem; color: #4b5563; }
        .invoice-meta .invoice-number { font-size: 0.95rem; font-weight: 700; color: #1f2937; }
        .parties {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 0.9rem;
            padding: 0.6rem 0.75rem;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .parties h2 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin: 0 0 0.25rem; }
        .parties div { font-size: 0.85rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 0.9rem; font-size: 0.85rem; table-layout: fixed; }
        th, td { padding: 0.45rem 0.6rem; text-align: start; border-bottom: 1px solid #e5e7eb; overflow-wrap: break-word; vertical-align: middle; }
        thead th { background: #297497; color: #fff; }
        th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; }
        th:first-child, td:first-child { width: 46%; }
        th.qty-col, td.qty-col { width: 13%; text-align: center; }
        th.num, td.num { text-align: end; font-variant-numeric: tabular-nums; }
        td.item-name { font-weight: 600; }
        .qty-badge {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 2.1rem; padding: 0.2rem 0.55rem; border-radius: 999px;
            background: #eaf3f7; border: 1px solid #bcd9e4; color: #1c5a75;
            font-weight: 800; font-variant-numeric: tabular-nums; font-size: 0.95rem;
        }
        .section-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin: 0; }
        .payment { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; margin-bottom: 0.9rem; padding-bottom: 0.6rem; border-bottom: 1px solid #e5e7eb; }
        .payment-value { font-weight: 600; }
        .totals { margin-inline-start: auto; width: 290px; font-size: 0.85rem; }
        .totals div { display: flex; justify-content: space-between; padding: 0.25rem 0; }
        .totals .num { min-width: 8.5rem; }
        .totals .discount .num { color: #b45309; }
        .totals .grand { border-top: 2px solid #297497; margin-top: 0.3rem; padding-top: 0.5rem; font-weight: 700; font-size: 1rem; }
        .totals .grand .num { color: #297497; }
        /* Identifiers, timestamps and amounts are single tokens: breaking them
           mid-string ("4,500./00", "INV-20260905-/83015") makes them unreadable. */
        .code, .num, .invoice-meta bdi { white-space: nowrap; }
        .code { direction: ltr; unicode-bidi: isolate; }
        .print-bar { text-align: center; margin-top: 1.25rem; }
        .print-bar button {
            background: #297497; color: #fff; border: none; border-radius: 8px;
            padding: 0.6rem 1.5rem; font-size: 0.9rem; cursor: pointer;
        }
        /* Arabic is cursive: letter-spacing pulls the joined strokes apart and
           uppercasing is a no-op, so both are dropped in RTL. */
        [dir="rtl"] th,
        [dir="rtl"] .parties h2,
        [dir="rtl"] .section-label {
            text-transform: none;
            letter-spacing: normal;
        }
        @media screen and (max-width: 560px) {
            body { padding: 1rem; font-size: 0.8rem; }
            .invoice-header { flex-direction: column; align-items: flex-start; }
            .invoice-meta { text-align: start; }
            .parties { flex-direction: column; gap: 0.6rem; }
            table { font-size: 0.75rem; }
            th, td { padding: 0.4rem 0.3rem; }
            th { white-space: nowrap; font-size: 0.68rem; }
            th:first-child, td:first-child { width: 34%; }
            th.qty-col, td.qty-col { width: 16%; }
            .qty-badge { min-width: 1.8rem; padding: 0.15rem 0.4rem; font-size: 0.85rem; }
            .totals { width: 100%; }
        }
        @page {
            size: A4;
            margin: 9mm;
        }
        @media print {
            html, body {
                width: auto;
                height: auto;
                min-height: 0;
            }
            body {
                max-width: none;
                margin: 0;
                padding: 0;
                overflow: visible;
                font-size: 0.78rem;
                line-height: 1.3;
            }
            .invoice-header {
                padding-bottom: 0.55rem;
                margin-bottom: 0.6rem;
                break-inside: avoid;
            }
            .parties {
                margin-bottom: 0.6rem;
                padding: 0.45rem 0.6rem;
                break-inside: avoid;
            }
            table {
                margin-bottom: 0.6rem;
            }
            th, td {
                padding: 0.28rem 0.45rem;
            }
            thead {
                display: table-header-group;
            }
            tr, .totals, .payment {
                break-inside: avoid;
            }
            .totals {
                width: 270px;
                font-size: 0.8rem;
            }
            .print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    @php
        $isArabic = app()->getLocale() === 'ar';
        $currencySymbol = trans('reports.currency');
        $money = fn ($value) => $isArabic
            ? number_format((float) $value, 2).' '.$currencySymbol
            : $currencySymbol.' '.number_format((float) $value, 2);
    @endphp
    <div class="invoice-header">
        <div class="invoice-brand">
            <img src="{{ asset('images/vetora-logo-transparent.png') }}" alt="">
            <div>
                <h1>{{ config('app.name', 'Vetora') }}</h1>
                <div>{{ __('invoices.title') }} — <bdi class="code">{{ $invoice->order?->order_number }}</bdi></div>
            </div>
        </div>
        <div class="invoice-meta">
            <div class="invoice-number"><bdi class="code">{{ $invoice->invoice_number }}</bdi></div>
            <div>{{ __('invoices.issued_at') }}: <bdi dir="ltr">{{ $invoice->issued_at->format('Y-m-d H:i') }}</bdi></div>
        </div>
    </div>

    <div class="parties">
        <div>
            <h2>{{ __('invoices.from') }}</h2>
            <div>{{ $invoice->vendor?->store_name }}</div>
        </div>
        <div>
            <h2>{{ __('invoices.bill_to') }}</h2>
            <div>{{ $invoice->user?->name }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('invoices.item') }}</th>
                <th class="qty-col">{{ __('invoices.quantity') }}</th>
                <th class="num">{{ __('invoices.unit_price') }}</th>
                <th class="num">{{ __('invoices.line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->order?->items ?? [] as $item)
                <tr>
                    <td class="item-name">{{ $item->product_name }}</td>
                    <td class="qty-col"><span class="qty-badge">{{ $item->quantity }}</span></td>
                    <td class="num">{{ $money($item->unit_price) }}</td>
                    <td class="num">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php $paymentMethodLabels = ['cash' => __('checkout.cash_on_delivery')]; @endphp
    <div class="payment">
        <span class="section-label">{{ __('invoices.payment_method') }}</span>
        <span class="payment-value">{{ $paymentMethodLabels[$invoice->payment_method] ?? $invoice->payment_method }}</span>
    </div>

    <div class="totals">
        <div><span>{{ __('invoices.subtotal') }}</span><span class="num">{{ $money($invoice->subtotal_amount) }}</span></div>
        @if ((float) $invoice->discount_total > 0)
            <div class="discount"><span>{{ __('invoices.discount') }}</span><span class="num">−{{ $money($invoice->discount_total) }}</span></div>
        @endif
        <div><span>{{ __('invoices.shipping') }}</span><span class="num">{{ $money($invoice->shipping_total) }}</span></div>
        @if ((float) $invoice->tax_total > 0)
            <div><span>{{ __('invoices.tax') }}</span><span class="num">{{ $money($invoice->tax_total) }}</span></div>
        @endif
        <div class="grand"><span>{{ __('invoices.grand_total') }}</span><span class="num">{{ $money($invoice->grand_total) }}</span></div>
    </div>

    <div class="print-bar">
        <button type="button" onclick="window.print()">{{ __('invoices.print_button') }}</button>
    </div>
</body>
</html>
