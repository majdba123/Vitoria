<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        /* Two-size typography discipline: 16px (LARGE) for the title, section
           headings and headline KPI figures; 10px (SMALL) for every label,
           value, table cell and supporting line. No other sizes are used. */
        body { font-family: dejavusans; color: #172033; font-size: 10px; line-height: 1.55; }
        h1, h2 { color: #173f35; font-size: 16px; font-weight: 700; }
        h1 { margin: 0 0 3px; }
        h2 { border-bottom: 1.5px solid #b9cdc2; padding-bottom: 4px; margin: 12px 0 7px; }
        .muted { color: #64748b; font-size: 10px; }
        .meta, .kpis, .report-table { width: 100%; border-collapse: collapse; }
        .meta td { width: 50%; padding: 5px 12px 5px 0; vertical-align: top; border-bottom: 1px solid #eef1f5; font-size: 10px; }
        .meta td:nth-child(2n) { padding-{{ $isArabic ? 'left' : 'right' }}: 0; }
        .meta .lbl { display: inline-block; min-width: 96px; margin-{{ $isArabic ? 'left' : 'right' }}: 12px; color: #64748b; font-weight: 700; }
        .kpis td { border: 1px solid #d3ddd6; background: #f4f8f6; padding: 7px 9px; width: 25%; vertical-align: top; }
        .kpis .lbl { display: block; color: #64748b; font-size: 10px; margin-bottom: 2px; font-weight: 700; }
        .kpis strong { display: block; color: #173f35; font-size: 16px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .report-table { page-break-inside: auto; margin: 3px 0 10px; }
        .report-table thead { display: table-header-group; }
        .report-table tr { page-break-inside: avoid; }
        .report-table th { background: #173f35; color: #fff; font-size: 10px; font-weight: 700; padding: 6px 9px; text-align: {{ $isArabic ? 'right' : 'left' }}; }
        .report-table th.num { text-align: right; }
        .report-table td { border-bottom: 1px solid #e3e9ee; padding: 5px 9px; vertical-align: middle; font-size: 10px; }
        .report-table tbody tr:nth-child(even) td { background: #f6f9f8; }
        .number { direction: ltr; unicode-bidi: embed; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .negative { color: #a93226; }
        .dynamic { unicode-bidi: plaintext; }
        .notice { border: 1px solid #d6a94a; background: #fff9e8; padding: 7px 9px; margin-top: 6px; font-size: 10px; }
    </style>
</head>
<body>
@php
    $locale = $isArabic ? 'ar' : 'en';
    $labels = trans('reports.vendor.labels', [], $locale);
    $values = trans('reports.values', [], $locale);
    $scope = trans('reports.scope', [], $locale);
    $translatedValue = fn ($value) => $values[$value] ?? $labels['not_available'];
    $currencySymbol = trans('reports.currency', [], $locale);
    $money = fn ($value) => $value === null ? $labels['not_available'] : ($isArabic ? number_format((float) $value, 2).' '.$currencySymbol : $currencySymbol.' '.number_format((float) $value, 2));
    $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '—';
    $period = $data['scope']['period'];
@endphp

<h1>{{ $labels['title'] }}</h1>
<div class="muted dynamic" dir="auto">
    @if($syndicate)
        {{ $syndicate->name }} &#8594; {{ $data['vendor']['store_name'] }}
    @else
        {{ $scope['admin'] }} &#8594; {{ $data['vendor']['store_name'] }}
    @endif
</div>

<h2>{{ $labels['identity'] }}</h2>
<table class="meta">
    @if($syndicate)
    <tr><td colspan="2"><span class="lbl">{{ $labels['syndicate'] }}</span><span class="dynamic" dir="auto">{{ $syndicate->name }}</span></td></tr>
    @endif
    <tr><td><span class="lbl">{{ $labels['store'] }}</span><span class="dynamic" dir="auto">{{ $data['vendor']['store_name'] }}</span></td><td><span class="lbl">{{ $labels['city'] }}</span><span class="dynamic" dir="auto">{{ $data['vendor']['city']['name'] ?? '—' }}</span></td></tr>
    <tr><td><span class="lbl">{{ $labels['type'] }}</span>{{ $translatedValue($data['vendor']['business_type']) }}</td><td><span class="lbl">{{ $labels['status'] }}</span>{{ $translatedValue($data['vendor']['status']) }}</td></tr>
    <tr><td><span class="lbl">{{ $labels['joined'] }}</span>{{ $date($data['vendor']['joined_at']) }}</td><td><span class="lbl">{{ $labels['domain'] }}</span>{{ $data['scope']['domain'] ? $translatedValue($data['scope']['domain']) : $scope['all_vendor_activity'] }}</td></tr>
    <tr><td colspan="2"><span class="lbl">{{ $labels['categories'] }}</span><span class="dynamic" dir="auto">{{ collect($data['vendor']['categories'])->pluck('name')->join(', ') ?: '—' }}</span></td></tr>
</table>

<h2>{{ $labels['period'] }}</h2>
<table class="meta"><tr><td><span class="lbl">{{ $labels['from'] }}</span>{{ $period['from'] ?? $labels['not_available'] }}</td><td><span class="lbl">{{ $labels['to'] }}</span>{{ $period['to'] ?? $labels['not_available'] }}</td></tr><tr><td colspan="2"><span class="lbl">{{ $labels['generated'] }}</span>{{ $data['generated_at']->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td></tr></table>

<h2>{{ $labels['kpis'] }}</h2>
<table class="kpis">
    <tr><td><span class="lbl">{{ $labels['products'] }}</span><strong>{{ $data['kpis']['total_products'] }}</strong></td><td><span class="lbl">{{ $labels['active_products'] }}</span><strong>{{ $data['kpis']['active_products'] }}</strong></td><td><span class="lbl">{{ $labels['orders'] }}</span><strong>{{ $data['kpis']['completed_orders'] }}</strong></td><td><span class="lbl">{{ $labels['units'] }}</span><strong>{{ $data['kpis']['units_sold'] }}</strong></td></tr>
    <tr><td><span class="lbl">{{ $labels['sales'] }}</span><strong>{{ $money($data['kpis']['gross_sales']) }}</strong></td><td><span class="lbl">{{ $labels['refunds'] }}</span><strong class="negative">{{ $money($data['kpis']['refunds']) }}</strong></td><td><span class="lbl">{{ $labels['net'] }}</span><strong>{{ $money($data['finance']['net_earnings'] ?? null) }}</strong></td><td><span class="lbl">{{ $labels['average'] }}</span><strong>{{ $money($data['kpis']['average_completed_order_value']) }}</strong></td></tr>
</table>
<p><span class="lbl">{{ $labels['last_sale'] }}</span>{{ $date($data['kpis']['last_sale_at']) }}</p>
@if(($data['finance']['attribution_complete'] ?? true) === false)<div class="notice">{{ $labels['attribution_notice'] }}</div>@endif

<h2>{{ $labels['sales_summary'] }}</h2>
<table class="report-table"><thead><tr><th style="width:40%">{{ $labels['date'] }}</th><th class="num" style="width:25%">{{ $labels['orders'] }}</th><th class="num" style="width:35%">{{ $labels['sales'] }}</th></tr></thead><tbody>
@forelse($data['trend'] as $row)<tr><td>{{ $row['date'] }}</td><td class="number">{{ $row['orders'] }}</td><td class="number">{{ $money($row['sales']) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['product_performance'] }}</h2>
<table class="report-table"><thead><tr><th style="width:26%">{{ $labels['product'] }}</th><th style="width:16%">{{ $labels['category'] }}</th><th class="num" style="width:10%">{{ $labels['units'] }}</th><th class="num" style="width:10%">{{ $labels['order_count'] }}</th><th class="num" style="width:14%">{{ $labels['gross'] }}</th><th class="num" style="width:14%">{{ $labels['refunds'] }}</th><th class="num" style="width:10%">{{ $labels['last_sale'] }}</th></tr></thead><tbody>
@forelse($data['products'] as $row)<tr><td class="dynamic" dir="auto" style="font-weight:700">{{ $row['name'] }}</td><td class="dynamic" dir="auto">{{ $row['category']['name'] ?? '—' }}</td><td class="number">{{ $row['units_sold'] }}</td><td class="number">{{ $row['order_count'] }}</td><td class="number">{{ $money($row['completed_sales_amount']) }}</td><td class="number negative">{{ $money($row['refunds']) }}</td><td class="number">{{ $date($row['last_sold_at']) }}</td></tr>@empty<tr><td colspan="7">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['orders_summary'] }}</h2>
<table class="report-table"><thead><tr><th style="width:14%">{{ $labels['order'] }}</th><th style="width:12%">{{ $labels['date'] }}</th><th style="width:38%">{{ $labels['items'] }}</th><th class="num" style="width:16%">{{ $labels['amount'] }}</th><th style="width:20%">{{ $labels['return_status'] }}</th></tr></thead><tbody>
@forelse($data['orders'] as $row)<tr><td>{{ $row['order_number'] }}</td><td>{{ $date($row['created_at']) }}</td><td class="dynamic" dir="auto">{{ collect($row['products'])->map(fn ($item) => $item['name'].' x '.$item['quantity'])->join(', ') }}</td><td class="number">{{ $money($row['scoped_sales']) }}</td><td>{{ $translatedValue($row['status']) }}</td></tr>@empty<tr><td colspan="5">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['returns'] }}</h2>
<table class="report-table"><thead><tr><th style="width:14%">{{ $labels['order'] }}</th><th style="width:28%">{{ $labels['product'] }}</th><th class="num" style="width:16%">{{ $labels['amount'] }}</th><th style="width:22%">{{ $labels['return_status'] }}</th><th style="width:20%">{{ $labels['date'] }}</th></tr></thead><tbody>
@forelse($data['returns'] as $row)@foreach($row['items'] as $item)<tr><td>{{ $row['order']['order_number'] ?? '—' }}</td><td>{{ $item['product']['name'] ?? '—' }}</td><td class="number">{{ $money($item['line_total']) }}</td><td>{{ $translatedValue($row['status']) }}</td><td>{{ $date($row['created_at']) }}</td></tr>@endforeach @empty<tr><td colspan="5">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['category_performance'] }}</h2>
<table class="report-table"><thead><tr><th style="width:34%">{{ $labels['category'] }}</th><th class="num" style="width:20%">{{ $labels['products_count'] }}</th><th class="num" style="width:20%">{{ $labels['units'] }}</th><th class="num" style="width:26%">{{ $labels['sales'] }}</th></tr></thead><tbody>
@forelse($data['category_performance'] as $row)<tr><td class="dynamic" dir="auto">{{ $row['name'] }}</td><td class="number">{{ $row['products_count'] }}</td><td class="number">{{ $row['units_sold'] }}</td><td class="number">{{ $money($row['sales']) }}</td></tr>@empty<tr><td colspan="4">—</td></tr>@endforelse
</tbody></table>
</body>
</html>
