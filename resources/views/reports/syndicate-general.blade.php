<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans; color:#172033; font-size:10px; line-height:1.55; }
        h1 { color:#173f35; font-size:21px; margin:0; }
        h2 { color:#173f35; font-size:14px; border-bottom:1px solid #d7dee7; padding-bottom:5px; margin:18px 0 8px; }
        .header,.kpis,.table { width:100%; border-collapse:collapse; }
        .header td { vertical-align:middle; padding-bottom:8px; border-bottom:1.5px solid #173f35; }
        .kpis td { width:33.33%; border:1px solid #d7dee7; background:#f7f9fb; padding:7px 8px; vertical-align:top; }
        .kpis .lbl { display:block; color:#64748b; font-size:9px; }
        .kpis strong { display:block; color:#173f35; font-size:13px; margin-top:2px; font-variant-numeric:tabular-nums; }
        .meta td { padding:4px 10px 4px 0; vertical-align:top; }
        .meta .lbl { display:inline-block; min-width:84px; color:#64748b; font-weight:700; }
        .table { page-break-inside:auto; margin-bottom:2px; }
        .table thead { display:table-header-group; }
        .table tr { page-break-inside:avoid; }
        .table th { background:#173f35; color:#fff; padding:5px 8px; text-align:{{ $isArabic ? 'right' : 'left' }}; }
        .table th.num { text-align:right; }
        .table td { border-bottom:1px solid #e5eaf0; padding:5px 8px; vertical-align:top; }
        .number { direction:ltr; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .dynamic { unicode-bidi:plaintext; }
        .muted { color:#64748b; }
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
<table class="meta"><tr><td><span class="lbl">{{ $l['period'] }}</span>{{ $period['from'] ?? $l['not_available'] }} — {{ $period['to'] ?? $l['not_available'] }}</td><td><span class="lbl">{{ $l['generated'] }}</span>{{ $data['generated_at']->format('Y-m-d H:i') }}</td></tr></table>
<table class="kpis"><tr><td><span class="lbl">{{ $l['vendors'] }}</span><strong>{{ $data['kpis']['vendors'] }}</strong></td><td><span class="lbl">{{ $l['active'] }}</span><strong>{{ $data['kpis']['active_vendors'] }}</strong></td><td><span class="lbl">{{ $l['orders'] }}</span><strong>{{ $data['kpis']['completed_orders'] }}</strong></td></tr><tr><td><span class="lbl">{{ $l['units'] }}</span><strong>{{ $data['kpis']['units_sold'] }}</strong></td><td><span class="lbl">{{ $l['sales'] }}</span><strong>{{ $money($data['kpis']['gross_sales']) }}</strong></td><td><span class="lbl">{{ $l['refunds'] }}</span><strong>{{ $money($data['kpis']['refunds']) }}</strong></td></tr></table>

<h2>{{ $l['vendor_table'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['vendor'] }}</th><th>{{ $l['city'] }}</th><th class="num">{{ $l['orders'] }}</th><th class="num">{{ $l['units'] }}</th><th class="num">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['vendor_performance'] as $row)<tr><td class="dynamic" dir="auto">{{ $row->store_name }}</td><td class="dynamic" dir="auto">{{ $row->city ?? '—' }}</td><td class="number">{{ $row->completed_orders }}</td><td class="number">{{ $row->units_sold }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="5">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['top_products'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['product'] }}</th><th class="num">{{ $l['units'] }}</th><th class="num">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['top_products'] as $row)<tr><td class="dynamic" dir="auto">{{ $isArabic ? ($row->name_ar ?: $row->name_en) : ($row->name_en ?: $row->name_ar) }}</td><td class="number">{{ $row->units_sold }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['categories'] }}</h2>
<table class="table"><thead><tr><th style="width:40%">{{ $l['category'] }}</th><th class="num">{{ $l['units'] }}</th><th class="num">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['categories'] as $row)<tr><td class="dynamic" dir="auto">{{ $row->name }}</td><td class="number">{{ $row->units_sold }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['trend'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['date'] }}</th><th class="num">{{ $l['orders'] }}</th><th class="num">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['trend'] as $row)<tr><td>{{ $row->sale_date }}</td><td class="number">{{ $row->completed_orders }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['geography'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['city'] }}</th><th class="num">{{ $l['vendors'] }}</th><th class="num">{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['geography'] as $row)<tr><td class="dynamic" dir="auto">{{ $row->city ?? '—' }}</td><td class="number">{{ $row->vendors }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>
</body>
</html>
