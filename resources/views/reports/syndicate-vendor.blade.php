<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans; color: #172033; font-size: 10px; line-height: 1.55; }
        h1 { color: #173f35; font-size: 22px; margin: 0 0 4px; }
        h2 { color: #173f35; font-size: 14px; border-bottom: 1px solid #d7dee7; padding-bottom: 5px; margin: 18px 0 8px; }
        .muted { color: #64748b; }
        .meta, .kpis, .report-table { width: 100%; border-collapse: collapse; }
        .meta td { width: 50%; padding: 4px 0; vertical-align: top; }
        .kpis td { border: 1px solid #d7dee7; background: #f7f9fb; padding: 8px; width: 25%; vertical-align: top; }
        .kpis strong { display: block; color: #173f35; font-size: 14px; margin-top: 3px; }
        .report-table { page-break-inside: auto; }
        .report-table thead { display: table-header-group; }
        .report-table tr { page-break-inside: avoid; }
        .report-table th { background: #173f35; color: #fff; padding: 6px; text-align: {{ $isArabic ? 'right' : 'left' }}; }
        .report-table td { border-bottom: 1px solid #e5eaf0; padding: 6px; vertical-align: top; }
        .number { direction: ltr; text-align: right; white-space: nowrap; }
        .dynamic { unicode-bidi: plaintext; }
        .notice { border: 1px solid #d6a94a; background: #fff9e8; padding: 8px; margin-top: 8px; }
    </style>
</head>
<body>
@php
    $labels = $isArabic ? [
        'title' => 'تقرير أداء التاجر', 'identity' => 'بيانات التاجر', 'period' => 'فترة التقرير', 'from' => 'من تاريخ', 'to' => 'إلى تاريخ',
        'generated' => 'تاريخ الإنشاء', 'store' => 'المتجر', 'owner' => 'اسم المالك/النشاط', 'type' => 'نوع النشاط', 'city' => 'المدينة',
        'status' => 'الحالة', 'joined' => 'تاريخ التسجيل', 'domain' => 'نطاق النقابة', 'categories' => 'التصنيفات', 'kpis' => 'ملخص المؤشرات',
        'products' => 'المنتجات التابعة للنطاق', 'active_products' => 'المنتجات النشطة', 'orders' => 'الطلبات المكتملة', 'units' => 'الوحدات المباعة',
        'sales' => 'إجمالي المبيعات المكتملة', 'refunds' => 'المبالغ المستردة', 'net' => 'صافي مستحقات التاجر', 'average' => 'متوسط قيمة الطلب المكتمل',
        'last_sale' => 'تاريخ آخر عملية بيع', 'sales_summary' => 'ملخص المبيعات', 'product_performance' => 'أداء المنتجات', 'product' => 'المنتج',
        'category' => 'التصنيف', 'order_count' => 'عدد الطلبات', 'gross' => 'إجمالي المبيعات', 'orders_summary' => 'ملخص الطلبات',
        'order' => 'الطلب', 'date' => 'التاريخ', 'items' => 'المنتجات / العناصر التابعة للنطاق', 'amount' => 'المبلغ المكتمل', 'returns' => 'المرتجعات والمبالغ المستردة',
        'return_status' => 'الحالة', 'category_performance' => 'الأداء حسب التصنيف', 'products_count' => 'عدد المنتجات', 'not_available' => 'غير متاح',
        'attribution_notice' => 'لا يمكن احتساب صافي أرباح هذا النطاق بدقة بسبب وجود طلبات مختلطة النطاق؛ لذلك لم يتم عرض قيمة تقديرية.',
    ] : [
        'title' => 'Vendor Performance Report', 'identity' => 'Vendor identity', 'period' => 'Report period', 'from' => 'From', 'to' => 'To',
        'generated' => 'Generated at', 'store' => 'Store', 'owner' => 'Owner/business name', 'type' => 'Business type', 'city' => 'City', 'status' => 'Status',
        'joined' => 'Registration date', 'domain' => 'Syndicate scope', 'categories' => 'Categories', 'kpis' => 'KPI summary', 'products' => 'Relevant products',
        'active_products' => 'Active products', 'orders' => 'Completed orders', 'units' => 'Units sold', 'sales' => 'Gross completed sales', 'refunds' => 'Refunds',
        'net' => 'Net Vendor Earnings', 'average' => 'Average completed order value', 'last_sale' => 'Last sale date', 'sales_summary' => 'Sales summary',
        'product_performance' => 'Product performance', 'product' => 'Product', 'category' => 'Category', 'order_count' => 'Completed orders', 'gross' => 'Gross sales',
        'orders_summary' => 'Orders summary', 'order' => 'Order', 'date' => 'Date', 'items' => 'Relevant products/items', 'amount' => 'Completed amount',
        'returns' => 'Returns and refunds', 'return_status' => 'Status', 'category_performance' => 'Category performance', 'products_count' => 'Products count',
        'not_available' => 'Not available', 'attribution_notice' => 'Net earnings cannot be attributed accurately because the period contains mixed-domain orders, so no estimate is shown.',
    ];
    $values = $isArabic ? ['both' => 'زراعي وبيطري', 'agriculture' => 'زراعي', 'veterinary' => 'بيطري', 'active' => 'نشط', 'inactive' => 'غير نشط', 'completed' => 'مكتمل', 'cancelled' => 'ملغي', 'refunded' => 'مسترد', 'pending' => 'قيد الانتظار'] : [];
    $translatedValue = fn ($value) => $values[$value] ?? $value;
    $money = fn ($value) => $value === null ? $labels['not_available'] : number_format((float) $value, 2).' '.($isArabic ? 'ل.س' : 'SYP');
    $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '—';
    $period = $data['scope']['period'];
@endphp

<h1>{{ $labels['title'] }}</h1>
<div class="muted dynamic" dir="auto">{{ $data['vendor']['store_name'] }} — {{ $isArabic ? 'النقابة '.$translatedValue($data['scope']['domain']) : $syndicate->name }}</div>

<h2>{{ $labels['identity'] }}</h2>
<table class="meta">
    <tr><td><strong>{{ $labels['store'] }}:</strong> <span class="dynamic" dir="auto">{{ $data['vendor']['store_name'] }}</span></td><td><strong>{{ $labels['owner'] }}:</strong> <span class="dynamic" dir="auto">{{ $data['vendor']['owner']['name'] ?? '—' }}</span></td></tr>
    <tr><td><strong>{{ $labels['type'] }}:</strong> {{ $translatedValue($data['vendor']['business_type']) }}</td><td><strong>{{ $labels['city'] }}:</strong> <span class="dynamic" dir="auto">{{ $data['vendor']['city']['name'] ?? '—' }}</span></td></tr>
    <tr><td><strong>{{ $labels['status'] }}:</strong> {{ $translatedValue($data['vendor']['status']) }}</td><td><strong>{{ $labels['joined'] }}:</strong> {{ $date($data['vendor']['joined_at']) }}</td></tr>
    <tr><td><strong>{{ $labels['domain'] }}:</strong> {{ $translatedValue($data['scope']['domain']) }}</td><td><strong>{{ $labels['categories'] }}:</strong> <span class="dynamic" dir="auto">{{ collect($data['vendor']['categories'])->pluck('name')->join(', ') ?: '—' }}</span></td></tr>
</table>

<h2>{{ $labels['period'] }}</h2>
<table class="meta"><tr><td><strong>{{ $labels['from'] }}:</strong> {{ $period['from'] ?? $labels['not_available'] }}</td><td><strong>{{ $labels['to'] }}:</strong> {{ $period['to'] ?? $labels['not_available'] }}</td></tr><tr><td colspan="2"><strong>{{ $labels['generated'] }}:</strong> {{ $data['generated_at']->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td></tr></table>

<h2>{{ $labels['kpis'] }}</h2>
<table class="kpis">
    <tr><td>{{ $labels['products'] }}<br><strong>{{ $data['kpis']['total_products'] }}</strong></td><td>{{ $labels['active_products'] }}<br><strong>{{ $data['kpis']['active_products'] }}</strong></td><td>{{ $labels['orders'] }}<br><strong>{{ $data['kpis']['completed_orders'] }}</strong></td><td>{{ $labels['units'] }}<br><strong>{{ $data['kpis']['units_sold'] }}</strong></td></tr>
    <tr><td>{{ $labels['sales'] }}<br><strong>{{ $money($data['kpis']['gross_sales']) }}</strong></td><td>{{ $labels['refunds'] }}<br><strong>{{ $money($data['kpis']['refunds']) }}</strong></td><td>{{ $labels['net'] }}<br><strong>{{ $money($data['finance']['net_earnings'] ?? null) }}</strong></td><td>{{ $labels['average'] }}<br><strong>{{ $money($data['kpis']['average_completed_order_value']) }}</strong></td></tr>
</table>
<p><strong>{{ $labels['last_sale'] }}:</strong> {{ $date($data['kpis']['last_sale_at']) }}</p>
@if(($data['finance']['attribution_complete'] ?? true) === false)<div class="notice">{{ $labels['attribution_notice'] }}</div>@endif

<h2>{{ $labels['sales_summary'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['date'] }}</th><th>{{ $labels['orders'] }}</th><th>{{ $labels['sales'] }}</th></tr></thead><tbody>
@forelse($data['trend'] as $row)<tr><td>{{ $row['date'] }}</td><td class="number">{{ $row['orders'] }}</td><td class="number">{{ $money($row['sales']) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['product_performance'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['product'] }}</th><th>{{ $labels['category'] }}</th><th>{{ $labels['units'] }}</th><th>{{ $labels['order_count'] }}</th><th>{{ $labels['gross'] }}</th><th>{{ $labels['refunds'] }}</th><th>{{ $labels['last_sale'] }}</th></tr></thead><tbody>
@forelse($data['products'] as $row)<tr><td class="dynamic" dir="auto">{{ $row['name'] }}</td><td class="dynamic" dir="auto">{{ $row['category']['name'] ?? '—' }}</td><td class="number">{{ $row['units_sold'] }}</td><td class="number">{{ $row['order_count'] }}</td><td class="number">{{ $money($row['completed_sales_amount']) }}</td><td class="number">{{ $money($row['refunds']) }}</td><td>{{ $date($row['last_sold_at']) }}</td></tr>@empty<tr><td colspan="7">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['orders_summary'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['order'] }}</th><th>{{ $labels['date'] }}</th><th>{{ $labels['items'] }}</th><th>{{ $labels['amount'] }}</th><th>{{ $labels['return_status'] }}</th></tr></thead><tbody>
@forelse($data['orders'] as $row)<tr><td>{{ $row['order_number'] }}</td><td>{{ $date($row['created_at']) }}</td><td class="dynamic" dir="auto">{{ collect($row['products'])->map(fn ($item) => $item['name'].' x '.$item['quantity'])->join(', ') }}</td><td class="number">{{ $money($row['scoped_sales']) }}</td><td>{{ $translatedValue($row['status']) }}</td></tr>@empty<tr><td colspan="5">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['returns'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['order'] }}</th><th>{{ $labels['product'] }}</th><th>{{ $labels['amount'] }}</th><th>{{ $labels['return_status'] }}</th><th>{{ $labels['date'] }}</th></tr></thead><tbody>
@forelse($data['returns'] as $row)@foreach($row['items'] as $item)<tr><td>{{ $row['order']['order_number'] ?? '—' }}</td><td>{{ $item['product']['name'] ?? '—' }}</td><td class="number">{{ $money($item['line_total']) }}</td><td>{{ $row['status'] }}</td><td>{{ $date($row['created_at']) }}</td></tr>@endforeach @empty<tr><td colspan="5">—</td></tr>@endforelse
</tbody></table>

<h2>{{ $labels['category_performance'] }}</h2>
<table class="report-table"><thead><tr><th>{{ $labels['category'] }}</th><th>{{ $labels['products_count'] }}</th><th>{{ $labels['units'] }}</th><th>{{ $labels['sales'] }}</th></tr></thead><tbody>
@forelse($data['category_performance'] as $row)<tr><td>{{ $row['name'] }}</td><td class="number">{{ $row['products_count'] }}</td><td class="number">{{ $row['units_sold'] }}</td><td class="number">{{ $money($row['sales']) }}</td></tr>@empty<tr><td colspan="4">—</td></tr>@endforelse
</tbody></table>
</body>
</html>
