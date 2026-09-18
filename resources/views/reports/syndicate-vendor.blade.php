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
        /* mPDF does not honour display:block/inline-block on inline <span>, so
           label and value have to be separate cells or they render fused. */
        .meta td { padding: 5px 9px; vertical-align: top; border-bottom: 1px solid #eef1f5; font-size: 10px; }
        .meta td.lbl { width: 17%; color: #64748b; font-weight: 700; }
        .meta td.val { width: 33%; }
        .kpis td { border: 1px solid #d3ddd6; background: #f4f8f6; padding: 7px 9px; width: 25%; vertical-align: top; }
        .kpis .lbl { color: #64748b; font-size: 10px; margin: 0 0 2px; font-weight: 700; }
        .kpis strong { display: block; color: #173f35; font-size: 16px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .report-table { page-break-inside: auto; margin: 3px 0 10px; }
        .report-table thead { display: table-header-group; }
        .report-table tr { page-break-inside: avoid; }
        .report-table th { background: #173f35; color: #fff; font-size: 10px; font-weight: 700; padding: 6px 9px; text-align: {{ $isArabic ? 'right' : 'left' }}; }
        .report-table th.num { text-align: right; }
        .report-table td { border-bottom: 1px solid #e3e9ee; padding: 5px 9px; vertical-align: middle; font-size: 10px; }
        .report-table tbody tr:nth-child(even) td { background: #f6f9f8; }
        /* Bare figures and ISO dates are LTR runs. Money is NOT: forcing it LTR
           reorders "1,234.00 ل.س" against the identically-formatted KPI figures,
           so amounts keep the page direction and only the digits stay tabular. */
        .number { direction: ltr; unicode-bidi: embed; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .money { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        /* Order/invoice identifiers are single LTR tokens; without this mPDF
           breaks them mid-string ("ORD-20260917-41" / "477") across two lines. */
        .code { direction: ltr; unicode-bidi: embed; white-space: nowrap; text-align: {{ $isArabic ? 'right' : 'left' }}; }
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
    <tr><td class="lbl">{{ $labels['syndicate'] }}</td><td class="val dynamic" colspan="3" dir="auto">{{ $syndicate->name }}</td></tr>
    @endif
    <tr><td class="lbl">{{ $labels['store'] }}</td><td class="val dynamic" dir="auto">{{ $data['vendor']['store_name'] }}</td><td class="lbl">{{ $labels['city'] }}</td><td class="val dynamic" dir="auto">{{ $data['vendor']['city']['name'] ?? '—' }}</td></tr>
    <tr><td class="lbl">{{ $labels['type'] }}</td><td class="val">{{ $translatedValue($data['vendor']['business_type']) }}</td><td class="lbl">{{ $labels['status'] }}</td><td class="val">{{ $translatedValue($data['vendor']['status']) }}</td></tr>
    <tr><td class="lbl">{{ $labels['joined'] }}</td><td class="val">{{ $date($data['vendor']['joined_at']) }}</td><td class="lbl">{{ $labels['domain'] }}</td><td class="val">{{ $data['scope']['domain'] ? $translatedValue($data['scope']['domain']) : $scope['all_vendor_activity'] }}</td></tr>
    <tr><td class="lbl">{{ $labels['categories'] }}</td><td class="val dynamic" colspan="3" dir="auto">{{ collect($data['vendor']['categories'])->pluck('name')->join(', ') ?: '—' }}</td></tr>
</table>

<h2>{{ $labels['period'] }}</h2>
<table class="meta"><tr><td class="lbl">{{ $labels['from'] }}</td><td class="val">{{ $period['from'] ?? $labels['not_available'] }}</td><td class="lbl">{{ $labels['to'] }}</td><td class="val">{{ $period['to'] ?? $labels['not_available'] }}</td></tr><tr><td class="lbl">{{ $labels['generated'] }}</td><td class="val" colspan="3"><bdi dir="ltr">{{ $data['generated_at']->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</bdi></td></tr></table>

<h2>{{ $labels['kpis'] }}</h2>
<table class="kpis">
    <tr><td><p class="lbl">{{ $labels['products'] }}</p><strong>{{ $data['kpis']['total_products'] }}</strong></td><td><p class="lbl">{{ $labels['active_products'] }}</p><strong>{{ $data['kpis']['active_products'] }}</strong></td><td><p class="lbl">{{ $labels['orders'] }}</p><strong>{{ $data['kpis']['completed_orders'] }}</strong></td><td><p class="lbl">{{ $labels['units'] }}</p><strong>{{ $data['kpis']['units_sold'] }}</strong></td></tr>
    <tr><td><p class="lbl">{{ $labels['sales'] }}</p><strong>{{ $money($data['kpis']['gross_sales']) }}</strong></td><td><p class="lbl">{{ $labels['refunds'] }}</p><strong class="negative">{{ $money($data['kpis']['refunds']) }}</strong></td><td><p class="lbl">{{ $labels['net'] }}</p><strong>{{ $money($data['finance']['net_earnings'] ?? null) }}</strong></td><td><p class="lbl">{{ $labels['average'] }}</p><strong>{{ $money($data['kpis']['average_completed_order_value']) }}</strong></td></tr>
</table>
<table class="meta"><tr><td class="lbl">{{ $labels['last_sale'] }}</td><td class="val" colspan="3">{{ $date($data['kpis']['last_sale_at']) }}</td></tr></table>
@if(($data['finance']['attribution_complete'] ?? true) === false)<div class="notice">{{ $labels['attribution_notice'] }}</div>@endif

<h2>{{ $labels['sales_summary'] }}</h2>
<table class="report-table"><thead><tr><th style="width:40%">{{ $labels['date'] }}</th><th class="num" style="width:25%">{{ $labels['orders'] }}</th><th class="num" style="width:35%">{{ $labels['sales'] }}</th></tr></thead><tbody>
@forelse($data['trend'] as $row)<tr><td>{{ $row['date'] }}</td><td class="number">{{ $row['orders'] }}</td><td class="money">{{ $money($row['sales']) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['product_performance'] }}</h2>
<table class="report-table"><thead><tr><th style="width:24%">{{ $labels['product'] }}</th><th style="width:15%">{{ $labels['category'] }}</th><th class="num" style="width:9%">{{ $labels['units'] }}</th><th class="num" style="width:12%">{{ $labels['order_count'] }}</th><th class="num" style="width:13%">{{ $labels['gross'] }}</th><th class="num" style="width:13%">{{ $labels['refunds'] }}</th><th class="num" style="width:14%">{{ $labels['last_sale'] }}</th></tr></thead><tbody>
@forelse($data['products'] as $row)<tr><td class="dynamic" dir="auto" style="font-weight:700">{{ $row['name'] }}</td><td class="dynamic" dir="auto">{{ $row['category']['name'] ?? '—' }}</td><td class="number">{{ $row['units_sold'] }}</td><td class="number">{{ $row['order_count'] }}</td><td class="money">{{ $money($row['completed_sales_amount']) }}</td><td class="money negative">{{ $money($row['refunds']) }}</td><td class="number">{{ $date($row['last_sold_at']) }}</td></tr>@empty<tr><td colspan="7">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['orders_summary'] }}</h2>
<table class="report-table"><thead><tr><th style="width:19%">{{ $labels['order'] }}</th><th style="width:12%">{{ $labels['date'] }}</th><th style="width:33%">{{ $labels['items'] }}</th><th class="num" style="width:16%">{{ $labels['amount'] }}</th><th style="width:20%">{{ $labels['return_status'] }}</th></tr></thead><tbody>
@forelse($data['orders'] as $row)<tr><td class="code">{{ $row['order_number'] }}</td><td>{{ $date($row['created_at']) }}</td><td class="dynamic" dir="auto">{{ collect($row['products'])->map(fn ($item) => $item['name'].' x '.$item['quantity'])->join(', ') }}</td><td class="money">{{ $money($row['scoped_sales']) }}</td><td>{{ $translatedValue($row['status']) }}</td></tr>@empty<tr><td colspan="5">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['returns'] }}</h2>
<table class="report-table"><thead><tr><th style="width:19%">{{ $labels['order'] }}</th><th style="width:23%">{{ $labels['product'] }}</th><th class="num" style="width:16%">{{ $labels['amount'] }}</th><th style="width:22%">{{ $labels['return_status'] }}</th><th style="width:20%">{{ $labels['date'] }}</th></tr></thead><tbody>
@forelse($data['returns'] as $row)@foreach($row['items'] as $item)<tr><td class="code">{{ $row['order']['order_number'] ?? '—' }}</td><td class="dynamic" dir="auto">{{ $item['product']['name'] ?? '—' }}</td><td class="money">{{ $money($item['line_total']) }}</td><td>{{ $translatedValue($row['status']) }}</td><td>{{ $date($row['created_at']) }}</td></tr>@endforeach @empty<tr><td colspan="5">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['category_performance'] }}</h2>
<table class="report-table"><thead><tr><th style="width:34%">{{ $labels['category'] }}</th><th class="num" style="width:20%">{{ $labels['products_count'] }}</th><th class="num" style="width:20%">{{ $labels['units'] }}</th><th class="num" style="width:26%">{{ $labels['sales'] }}</th></tr></thead><tbody>
@forelse($data['category_performance'] as $row)<tr><td class="dynamic" dir="auto">{{ $row['name'] }}</td><td class="number">{{ $row['products_count'] }}</td><td class="number">{{ $row['units_sold'] }}</td><td class="money">{{ $money($row['sales']) }}</td></tr>@empty<tr><td colspan="4">—</td></tr>@endforelse
</tbody></table>
</body>
</html>
