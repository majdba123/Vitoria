<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            color: #1f2937;
            background: #fff;
            margin: 0;
            padding: 2rem;
            max-width: 780px;
            margin-inline: auto;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #297497;
            padding-bottom: 1rem;
            margin-bottom: 1.1rem;
        }
        .invoice-header h1 { font-size: 1.3rem; margin: 0 0 0.2rem; color: #297497; }
        .invoice-meta { text-align: end; font-size: 0.8rem; color: #4b5563; }
        .invoice-meta strong { color: #1f2937; }
        .parties { display: flex; justify-content: space-between; gap: 2rem; margin-bottom: 1.1rem; }
        .parties h2 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin: 0 0 0.35rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.1rem; font-size: 0.85rem; table-layout: fixed; }
        th, td { padding: 0.45rem 0.7rem; text-align: start; border-bottom: 1px solid #e5e7eb; overflow-wrap: break-word; }
        th:first-child, td:first-child { width: 42%; }
        th { background: #f9fafb; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
        .num { text-align: end; font-variant-numeric: tabular-nums; }
        .qty-badge {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 1.9rem; padding: 0.15rem 0.5rem; border-radius: 999px;
            background: #eaf3f7; color: #1c5a75; font-weight: 700; font-variant-numeric: tabular-nums;
        }
        .section-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin: 0 0 0.35rem; }
        .payment { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; margin-bottom: 1.1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .payment-value { font-weight: 600; }
        .totals { margin-inline-start: auto; width: 280px; font-size: 0.85rem; }
        .totals div { display: flex; justify-content: space-between; padding: 0.3rem 0; }
        .totals .discount .num { color: #b45309; }
        .totals .grand { border-top: 2px solid #297497; margin-top: 0.35rem; padding-top: 0.55rem; font-weight: 700; font-size: 1rem; }
        .print-bar { text-align: center; margin-top: 1.5rem; }
        .print-bar button {
            background: #297497; color: #fff; border: none; border-radius: 8px;
            padding: 0.6rem 1.5rem; font-size: 0.9rem; cursor: pointer;
        }
        @page {
            size: A4;
            margin: 10mm;
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
                font-size: 0.8rem;
            }
            .invoice-header {
                padding-bottom: 0.7rem;
                margin-bottom: 0.8rem;
                break-inside: avoid;
            }
            .parties {
                margin-bottom: 0.8rem;
                break-inside: avoid;
            }
            table {
                margin-bottom: 0.8rem;
            }
            th, td {
                padding: 0.35rem 0.5rem;
            }
            thead {
                display: table-header-group;
            }
            tr, .totals, .payment {
                break-inside: avoid;
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
        <div>
            <h1>{{ config('app.name', 'Vetora') }}</h1>
            <div>{{ __('invoices.title') }} — {{ $invoice->order?->order_number }}</div>
        </div>
        <div class="invoice-meta">
            <div><strong>{{ $invoice->invoice_number }}</strong></div>
            <div>{{ __('invoices.issued_at') }}: {{ $invoice->issued_at->format('Y-m-d H:i') }}</div>
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
                <th class="num">{{ __('invoices.quantity') }}</th>
                <th class="num">{{ __('invoices.unit_price') }}</th>
                <th class="num">{{ __('invoices.line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->order?->items ?? [] as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="num"><span class="qty-badge">{{ $item->quantity }}</span></td>
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
