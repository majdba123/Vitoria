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
            margin: 0;
            padding: 2.5rem;
            max-width: 800px;
            margin-inline: auto;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #297497;
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .invoice-header h1 { font-size: 1.5rem; margin: 0 0 0.25rem; color: #297497; }
        .invoice-meta { text-align: end; font-size: 0.875rem; color: #4b5563; }
        .invoice-meta strong { color: #1f2937; }
        .parties { display: flex; justify-content: space-between; gap: 2rem; margin-bottom: 1.5rem; }
        .parties h2 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin: 0 0 0.4rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.9rem; }
        th, td { padding: 0.6rem 0.75rem; text-align: start; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
        .num { text-align: end; font-variant-numeric: tabular-nums; }
        .totals { margin-inline-start: auto; width: 280px; font-size: 0.9rem; }
        .totals div { display: flex; justify-content: space-between; padding: 0.35rem 0; }
        .totals .grand { border-top: 2px solid #297497; margin-top: 0.4rem; padding-top: 0.6rem; font-weight: 700; font-size: 1.05rem; }
        .print-bar { text-align: center; margin-top: 2rem; }
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
            }
            .invoice-header {
                padding-bottom: 0.75rem;
                margin-bottom: 0.9rem;
                break-inside: avoid;
            }
            .parties {
                margin-bottom: 0.9rem;
                break-inside: avoid;
            }
            table {
                margin-bottom: 0.9rem;
            }
            th, td {
                padding: 0.4rem 0.55rem;
            }
            thead {
                display: table-header-group;
            }
            tr, .totals {
                break-inside: avoid;
            }
            .print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div>
            <h1>{{ config('app.name', 'Vetora') }}</h1>
            <div>{{ __('invoices.title') }} — {{ $invoice->order?->order_number }}</div>
        </div>
        <div class="invoice-meta">
            <div><strong>{{ $invoice->invoice_number }}</strong></div>
            <div>{{ __('invoices.issued_at') }}: {{ $invoice->issued_at->format('Y-m-d H:i') }}</div>
            <div>{{ __('invoices.payment_method') }}: {{ $invoice->payment_method }}</div>
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
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="num">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>{{ __('invoices.subtotal') }}</span><span class="num">{{ number_format((float) $invoice->subtotal_amount, 2) }} {{ $invoice->currency }}</span></div>
        @if ((float) $invoice->discount_total > 0)
            <div><span>{{ __('invoices.discount') }}</span><span class="num">−{{ number_format((float) $invoice->discount_total, 2) }} {{ $invoice->currency }}</span></div>
        @endif
        <div><span>{{ __('invoices.shipping') }}</span><span class="num">{{ number_format((float) $invoice->shipping_total, 2) }} {{ $invoice->currency }}</span></div>
        @if ((float) $invoice->tax_total > 0)
            <div><span>{{ __('invoices.tax') }}</span><span class="num">{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</span></div>
        @endif
        <div class="grand"><span>{{ __('invoices.grand_total') }}</span><span class="num">{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</span></div>
    </div>

    <div class="print-bar">
        <button type="button" onclick="window.print()">{{ __('invoices.print_button') }}</button>
    </div>
</body>
</html>
