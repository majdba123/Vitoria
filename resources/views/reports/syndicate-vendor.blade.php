<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans; color: #172033; font-size: 10px; line-height: 1.55; }
        h1 { color: #173f35; font-size: 20px; margin: 0 0 2px; }
        h2 { color: #173f35; font-size: 13px; border-bottom: 1px solid #d7dee7; padding-bottom: 4px; margin: 14px 0 6px; }
        .muted { color: #64748b; }
        .meta, .kpis, .report-table { width: 100%; border-collapse: collapse; }
        .meta td { width: 50%; padding: 4px 10px 4px 0; vertical-align: top; border-bottom: 1px solid #eef1f5; }
        .meta td:nth-child(2n) { padding-{{ $isArabic ? 'left' : 'right' }}: 0; }
        .meta .lbl { display: inline-block; min-width: 84px; color: #64748b; font-weight: 700; }
        .kpis td { border: 1px solid #d7dee7; background: #f7f9fb; padding: 7px 8px; width: 25%; vertical-align: top; }
        .kpis .lbl { display: block; color: #64748b; font-size: 9px; }
        .kpis strong { display: block; color: #173f35; font-size: 13px; margin-top: 2px; font-variant-numeric: tabular-nums; }
        .report-table { page-break-inside: auto; margin-bottom: 2px; }
        .report-table thead { display: table-header-group; }
        .report-table tr { page-break-inside: avoid; }
        .report-table th { background: #173f35; color: #fff; padding: 5px 8px; text-align: {{ $isArabic ? 'right' : 'left' }}; }
        .report-table th.num { text-align: right; }
        .report-table td { border-bottom: 1px solid #e5eaf0; padding: 5px 8px; vertical-align: top; }
        .number { direction: ltr; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .dynamic { unicode-bidi: plaintext; }
        .notice { border: 1px solid #d6a94a; background: #fff9e8; padding: 7px 8px; margin-top: 6px; }
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
<div class="muted dynamic" dir="auto">{{ $data['vendor']['store_name'] }} — {{ $syndicate ? str_replace(':domain', $translatedValue($data['scope']['domain']), $scope['syndicate']) : $scope['admin'] }}</div>

<h2>{{ $labels['identity'] }}</h2>
<table class="meta">
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
    <tr><td><span class="lbl">{{ $labels['sales'] }}</span><strong>{{ $money($data['kpis']['gross_sales']) }}</strong></td><td><span class="lbl">{{ $labels['refunds'] }}</span><strong>{{ $money($data['kpis']['refunds']) }}</strong></td><td><span class="lbl">{{ $labels['net'] }}</span><strong>{{ $money($data['finance']['net_earnings'] ?? null) }}</strong></td><td><span class="lbl">{{ $labels['average'] }}</span><strong>{{ $money($data['kpis']['average_completed_order_value']) }}</strong></td></tr>
</table>
<p><span class="lbl">{{ $labels['last_sale'] }}</span>{{ $date($data['kpis']['last_sale_at']) }}</p>
@if(($data['finance']['attribution_complete'] ?? true) === false)<div class="notice">{{ $labels['attribution_notice'] }}</div>@endif

<h2>{{ $labels['sales_summary'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['date'] }}</th><th class="num">{{ $labels['orders'] }}</th><th class="num">{{ $labels['sales'] }}</th></tr></thead><tbody>
@forelse($data['trend'] as $row)<tr><td>{{ $row['date'] }}</td><td class="number">{{ $row['orders'] }}</td><td class="number">{{ $money($row['sales']) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['product_performance'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['product'] }}</th><th>{{ $labels['category'] }}</th><th class="num">{{ $labels['units'] }}</th><th class="num">{{ $labels['order_count'] }}</th><th class="num">{{ $labels['gross'] }}</th><th class="num">{{ $labels['refunds'] }}</th><th class="num">{{ $labels['last_sale'] }}</th></tr></thead><tbody>
@forelse($data['products'] as $row)<tr><td class="dynamic" dir="auto">{{ $row['name'] }}</td><td class="dynamic" dir="auto">{{ $row['category']['name'] ?? '—' }}</td><td class="number">{{ $row['units_sold'] }}</td><td class="number">{{ $row['order_count'] }}</td><td class="number">{{ $money($row['completed_sales_amount']) }}</td><td class="number">{{ $money($row['refunds']) }}</td><td class="number">{{ $date($row['last_sold_at']) }}</td></tr>@empty<tr><td colspan="7">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['orders_summary'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['order'] }}</th><th>{{ $labels['date'] }}</th><th>{{ $labels['items'] }}</th><th class="num">{{ $labels['amount'] }}</th><th>{{ $labels['return_status'] }}</th></tr></thead><tbody>
@forelse($data['orders'] as $row)<tr><td>{{ $row['order_number'] }}</td><td>{{ $date($row['created_at']) }}</td><td class="dynamic" dir="auto">{{ collect($row['products'])->map(fn ($item) => $item['name'].' x '.$item['quantity'])->join(', ') }}</td><td class="number">{{ $money($row['scoped_sales']) }}</td><td>{{ $translatedValue($row['status']) }}</td></tr>@empty<tr><td colspan="5">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['returns'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['order'] }}</th><th>{{ $labels['product'] }}</th><th class="num">{{ $labels['amount'] }}</th><th>{{ $labels['return_status'] }}</th><th>{{ $labels['date'] }}</th></tr></thead><tbody>
@forelse($data['returns'] as $row)@foreach($row['items'] as $item)<tr><td>{{ $row['order']['order_number'] ?? '—' }}</td><td>{{ $item['product']['name'] ?? '—' }}</td><td class="number">{{ $money($item['line_total']) }}</td><td>{{ $translatedValue($row['status']) }}</td><td>{{ $date($row['created_at']) }}</td></tr>@endforeach @empty<tr><td colspan="5">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['category_performance'] }}</h2>
<table class="report-table"><thead><tr><th style="width:40%">{{ $labels['category'] }}</th><th class="num">{{ $labels['products_count'] }}</th><th class="num">{{ $labels['units'] }}</th><th class="num">{{ $labels['sales'] }}</th></tr></thead><tbody>
@forelse($data['category_performance'] as $row)<tr><td class="dynamic" dir="auto">{{ $row['name'] }}</td><td class="number">{{ $row['products_count'] }}</td><td class="number">{{ $row['units_sold'] }}</td><td class="number">{{ $money($row['sales']) }}</td></tr>@empty<tr><td colspan="4">—</td></tr>@endforelse
</tbody></table>
</body>
</html>
