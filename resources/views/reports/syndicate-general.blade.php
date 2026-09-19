<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        /* Two-size typography discipline: 16px (LARGE) for the title, section
           headings and headline KPI figures; 10px (SMALL) for every label,
           value, table cell and supporting line. No other sizes are used. */
        body { font-family: dejavusans; color:#172033; font-size:10px; line-height:1.55; }
        h1, h2 { color:#173f35; font-size:16px; font-weight:700; }
        h1 { margin:0 0 3px; }
        h2 { border-bottom:1.5px solid #b9cdc2; padding-bottom:4px; margin:12px 0 7px; }
        .header,.kpis,.table { width:100%; border-collapse:collapse; }
        .header td { vertical-align:middle; padding-bottom:7px; border-bottom:1.5px solid #173f35; }
        .kpis td { width:33.33%; border:1px solid #d3ddd6; background:#f4f8f6; padding:7px 9px; vertical-align:top; }
        /* mPDF does not honour display:block/inline-block on inline <span>, so
           label and value have to be separate cells or they render fused. */
        .kpis .lbl { color:#64748b; font-size:10px; margin:0 0 2px; font-weight:700; }
        .kpis strong { display:block; color:#173f35; font-size:16px; font-weight:700; font-variant-numeric:tabular-nums; }
        .meta { width:100%; border-collapse:collapse; }
        .meta td { padding:5px 9px; vertical-align:top; font-size:10px; }
        .meta td.lbl { width:17%; color:#64748b; font-weight:700; }
        .meta td.val { width:33%; }
        .table { page-break-inside:auto; margin:3px 0 10px; }
        .table thead { display:table-header-group; }
        .table tr { page-break-inside:avoid; }
        .table th { background:#173f35; color:#fff; font-size:10px; font-weight:700; padding:6px 9px; text-align:{{ $isArabic ? 'right' : 'left' }}; }
        .table th.num { text-align:right; }
        .table td { border-bottom:1px solid #e3e9ee; padding:5px 9px; vertical-align:middle; font-size:10px; }
        .table tbody tr:nth-child(even) td { background:#f6f9f8; }
        /* Bare figures and ISO dates are LTR runs. Money is NOT: forcing it LTR
           reorders "1,234.00 ل.س" against the identically-formatted KPI figures,
           so amounts keep the page direction and only the digits stay tabular. */
        .number { direction:ltr; unicode-bidi:embed; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .money { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .negative { color:#a93226; }
        .dynamic { unicode-bidi:plaintext; }
        .muted { color:#64748b; font-size:10px; }
    </style>
</head>
<body>
@php
    $locale = $isArabic ? 'ar' : 'en';
    $l = trans('reports.general.labels', [], $locale);
    $values = trans('reports.values', [], $locale);
    $currencySymbol = trans('reports.currency', [], $locale);
    $money = fn ($value) => $isArabic ? number_format((float) $value, 2).' '.$currencySymbol : $currencySymbol.' '.number_format((float) $value, 2);
    $period = $data['period'];
    $logoPath = $syndicate->logo ? \Illuminate\Support\Facades\Storage::disk('public')->path($syndicate->logo) : null;
    if (! $logoPath || ! is_file($logoPath)) {
        $logoPath = public_path('images/vetora-logo-transparent.png');
    }
@endphp
<table class="header"><tr><td width="16%"><img src="{{ $logoPath }}" style="max-width:72px;max-height:52px;width:auto;height:auto" alt="{{ $syndicate->name }}"></td><td width="84%"><h1>{{ $l['title'] }}</h1><div class="muted dynamic" dir="auto">{{ $syndicate->name }} — {{ $values[$data['scope']['domain']] ?? $l['not_available'] }}</div></td></tr></table>
<table class="meta"><tr><td class="lbl">{{ $l['period'] }}</td><td class="val"><bdi dir="ltr">{{ $period['from'] ?? $l['not_available'] }} — {{ $period['to'] ?? $l['not_available'] }}</bdi></td><td class="lbl">{{ $l['generated'] }}</td><td class="val"><bdi dir="ltr">{{ $data['generated_at']->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</bdi></td></tr></table>
<table class="kpis"><tr><td><p class="lbl">{{ $l['vendors'] }}</p><strong>{{ $data['kpis']['vendors'] }}</strong></td><td><p class="lbl">{{ $l['active'] }}</p><strong>{{ $data['kpis']['active_vendors'] }}</strong></td><td><p class="lbl">{{ $l['orders'] }}</p><strong>{{ $data['kpis']['completed_orders'] }}</strong></td></tr><tr><td><p class="lbl">{{ $l['units'] }}</p><strong>{{ $data['kpis']['units_sold'] }}</strong></td><td><p class="lbl">{{ $l['sales'] }}</p><strong>{{ $money($data['kpis']['gross_sales']) }}</strong></td><td><p class="lbl">{{ $l['refunds'] }}</p><strong class="{{ (float) $data['kpis']['refunds'] > 0 ? 'negative' : '' }}">{{ $money($data['kpis']['refunds']) }}</strong></td></tr></table>

<h2>{{ $l['vendor_table'] }}</h2>
<table class="table"><thead><tr><th style="width:30%">{{ $l['vendor'] }}</th><th style="width:18%">{{ $l['city'] }}</th><th class="num" style="width:16%">{{ $l['orders'] }}</th><th class="num" style="width:16%">{{ $l['units'] }}</th><th class="num" style="width:20%">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['vendor_performance'] as $row)<tr><td class="dynamic" dir="auto" style="font-weight:700">{{ $row->store_name }}</td><td class="dynamic" dir="auto">{{ $row->city ?? '—' }}</td><td class="number">{{ $row->completed_orders }}</td><td class="number">{{ $row->units_sold }}</td><td class="money">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="5">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['top_products'] }}</h2>
<table class="table"><thead><tr><th style="width:50%">{{ $l['product'] }}</th><th class="num" style="width:20%">{{ $l['units'] }}</th><th class="num" style="width:30%">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['top_products'] as $row)<tr><td class="dynamic" dir="auto" style="font-weight:700">{{ $isArabic ? ($row->name_ar ?: $row->name_en) : ($row->name_en ?: $row->name_ar) }}</td><td class="number">{{ $row->units_sold }}</td><td class="money">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['categories'] }}</h2>
<table class="table"><thead><tr><th style="width:40%">{{ $l['category'] }}</th><th class="num" style="width:25%">{{ $l['units'] }}</th><th class="num" style="width:35%">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['categories'] as $row)<tr><td class="dynamic" dir="auto">{{ $row->name }}</td><td class="number">{{ $row->units_sold }}</td><td class="money">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['trend'] }}</h2>
<table class="table"><thead><tr><th style="width:40%">{{ $l['date'] }}</th><th class="num" style="width:25%">{{ $l['orders'] }}</th><th class="num" style="width:35%">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['trend'] as $row)<tr><td>{{ $row->sale_date }}</td><td class="number">{{ $row->completed_orders }}</td><td class="money">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['geography'] }}</h2>
<table class="table"><thead><tr><th style="width:40%">{{ $l['city'] }}</th><th class="num" style="width:25%">{{ $l['vendors'] }}</th><th class="num" style="width:35%">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['geography'] as $row)<tr><td class="dynamic" dir="auto">{{ $row->city ?? '—' }}</td><td class="number">{{ $row->vendors }}</td><td class="money">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>
</body>
</html>
