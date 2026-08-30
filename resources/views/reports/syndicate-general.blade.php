<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans; color:#172033; font-size:10px; line-height:1.55; }
        h1 { color:#173f35; font-size:21px; margin:0; }
        h2 { color:#173f35; font-size:14px; border-bottom:1px solid #d7dee7; padding-bottom:5px; margin:18px 0 8px; }
        .header,.kpis,.table { width:100%; border-collapse:collapse; }
        .header td { vertical-align:middle; padding-bottom:10px; border-bottom:1px solid #d7dee7; }
        .kpis td { width:33.33%; border:1px solid #d7dee7; background:#f7f9fb; padding:8px; vertical-align:top; }
        .kpis strong { display:block; color:#173f35; font-size:14px; margin-top:3px; }
        .table { page-break-inside:auto; }
        .table thead { display:table-header-group; }
        .table tr { page-break-inside:avoid; }
        .table th { background:#173f35; color:#fff; padding:6px; text-align:{{ $isArabic ? 'right' : 'left' }}; }
        .table td { border-bottom:1px solid #e5eaf0; padding:6px; vertical-align:top; }
        .number { direction:ltr; text-align:right; white-space:nowrap; }
        .dynamic { unicode-bidi:plaintext; }
        .muted { color:#64748b; }
    </style>
</head>
<body>
@php
    $l = $isArabic ? [
        'title'=>'التقرير العام لمبيعات النقابة','period'=>'فترة التقرير','generated'=>'تاريخ الإنشاء','vendors'=>'التجار ضمن النطاق','active'=>'التجار النشطون','orders'=>'الطلبات المكتملة','units'=>'الوحدات المباعة','sales'=>'إجمالي المبيعات المكتملة','refunds'=>'المبالغ المستردة','top_vendors'=>'أعلى التجار مبيعاً','top_products'=>'أفضل المنتجات','categories'=>'أداء التصنيفات','trend'=>'اتجاه المبيعات','geography'=>'التوزيع الجغرافي','vendor_table'=>'أداء التجار','vendor'=>'التاجر','city'=>'المدينة','product'=>'المنتج','category'=>'التصنيف','date'=>'التاريخ','not_available'=>'غير محدد',
    ] : [
        'title'=>'General Syndicate Sales Report','period'=>'Report period','generated'=>'Generated at','vendors'=>'Relevant Vendors','active'=>'Active Vendors','orders'=>'Completed orders','units'=>'Units sold','sales'=>'Gross completed sales','refunds'=>'Refunds','top_vendors'=>'Top Vendors by sales','top_products'=>'Top products','categories'=>'Category performance','trend'=>'Sales trend','geography'=>'Geographic distribution','vendor_table'=>'Vendor performance','vendor'=>'Vendor','city'=>'City','product'=>'Product','category'=>'Category','date'=>'Date','not_available'=>'Not specified',
    ];
    $money = fn ($value) => number_format((float) $value, 2).' '.($isArabic ? 'ل.س' : 'SYP');
    $period = $data['period'];
@endphp
<table class="header"><tr><td width="22%"><img src="{{ public_path('images/vetora-logo-transparent.png') }}" style="width:88px" alt="Vetora"></td><td width="78%"><h1>{{ $l['title'] }}</h1><div class="muted dynamic" dir="auto">{{ $syndicate->name }} — {{ $isArabic ? ($data['scope']['domain'] === 'veterinary' ? 'بيطري' : 'زراعي') : ucfirst($data['scope']['domain']) }}</div></td></tr></table>
<p><strong>{{ $l['period'] }}:</strong> {{ $period['from'] ?? $l['not_available'] }} — {{ $period['to'] ?? $l['not_available'] }} &nbsp; <strong>{{ $l['generated'] }}:</strong> {{ $data['generated_at']->format('Y-m-d H:i') }}</p>
<table class="kpis"><tr><td>{{ $l['vendors'] }}<strong>{{ $data['kpis']['vendors'] }}</strong></td><td>{{ $l['active'] }}<strong>{{ $data['kpis']['active_vendors'] }}</strong></td><td>{{ $l['orders'] }}<strong>{{ $data['kpis']['completed_orders'] }}</strong></td></tr><tr><td>{{ $l['units'] }}<strong>{{ $data['kpis']['units_sold'] }}</strong></td><td>{{ $l['sales'] }}<strong>{{ $money($data['kpis']['gross_sales']) }}</strong></td><td>{{ $l['refunds'] }}<strong>{{ $money($data['kpis']['refunds']) }}</strong></td></tr></table>

<h2>{{ $l['vendor_table'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['vendor'] }}</th><th>{{ $l['city'] }}</th><th>{{ $l['orders'] }}</th><th>{{ $l['units'] }}</th><th>{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['vendor_performance'] as $row)<tr><td class="dynamic" dir="auto">{{ $row->store_name }}</td><td class="dynamic" dir="auto">{{ $row->city ?? '—' }}</td><td class="number">{{ $row->completed_orders }}</td><td class="number">{{ $row->units_sold }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="5">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['top_products'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['product'] }}</th><th>{{ $l['units'] }}</th><th>{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['top_products'] as $row)<tr><td class="dynamic" dir="auto">{{ $isArabic ? ($row->name_ar ?: $row->name_en) : ($row->name_en ?: $row->name_ar) }}</td><td class="number">{{ $row->units_sold }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['categories'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['category'] }}</th><th>{{ $l['units'] }}</th><th>{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['categories'] as $row)<tr><td class="dynamic" dir="auto">{{ $row->name }}</td><td class="number">{{ $row->units_sold }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['trend'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['date'] }}</th><th>{{ $l['orders'] }}</th><th>{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['trend'] as $row)<tr><td>{{ $row->sale_date }}</td><td class="number">{{ $row->completed_orders }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>

<h2>{{ $l['geography'] }}</h2>
<table class="table"><thead><tr><th>{{ $l['city'] }}</th><th>{{ $l['vendors'] }}</th><th>{{ $l['sales'] }}</th></tr></thead><tbody>@forelse($data['geography'] as $row)<tr><td class="dynamic" dir="auto">{{ $row->city ?? '—' }}</td><td class="number">{{ $row->vendors }}</td><td class="number">{{ $money($row->gross_sales) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>
</body>
</html>
