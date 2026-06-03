@extends('layouts.syndicate')

@php($section = $section ?? 'dashboard')
@php($sectionLabels = [
    'dashboard' => 'نظرة عامة',
    'categories' => 'التصنيفات',
    'vendors' => 'التجار',
    'products' => 'المنتجات',
    'podcasts' => 'البودكاست',
    'orders' => 'الطلبات',
    'sales' => 'المبيعات',
    'reports' => 'التقارير',
])

@section('title', ($sectionLabels[$section] ?? 'لوحة النقابة') . ' - Vetora')
@section('page-title', $sectionLabels[$section] ?? 'لوحة النقابة')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-black text-gray-900 dark:text-white" id="page-heading">{{ $sectionLabels[$section] ?? 'لوحة النقابة' }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">كل البيانات المعروضة مفلترة تلقائياً حسب نوع النقابة ولا تتضمن بيانات من النوع الآخر.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('syndicate.sales') }}" class="btn-secondary btn-sm">المبيعات</a>
            <a href="{{ route('syndicate.reports') }}" class="btn-primary btn-sm">التقارير</a>
        </div>
    </div>

    <div id="overview-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"></div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-body border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white" id="main-title">السجلات</h3>
            </div>
            <div id="main-list" class="card-body space-y-3">
                <p class="py-8 text-center text-sm text-gray-400">جارٍ تحميل البيانات...</p>
            </div>
            <div id="main-pagination" class="hidden items-center justify-between border-t border-gray-100 px-4 py-3 text-sm dark:border-gray-800">
                <p id="main-page-info" class="text-xs text-gray-500"></p>
                <div class="flex gap-2">
                    <button id="main-prev" type="button" class="btn-secondary btn-xs">السابق</button>
                    <button id="main-next" type="button" class="btn-secondary btn-xs">التالي</button>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">الأعلى أداءً</h3>
            </div>
            <div id="side-list" class="card-body space-y-3"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('syndicate-ready', async function () {
    const section = @json($section);
    const sectionLabels = @json($sectionLabels);
    const endpoint = section === 'dashboard' || section === 'sales' ? 'overview' : (section === 'reports' ? 'reports' : section);
    const list = document.getElementById('main-list');
    const pagination = document.getElementById('main-pagination');
    const esc = (value) => { const d = document.createElement('div'); d.textContent = value == null ? '' : String(value); return d.innerHTML; };
    let currentPage = 1;
    let lastPage = 1;

    document.getElementById('main-prev')?.addEventListener('click', function () {
        if (currentPage > 1) loadSection(currentPage - 1);
    });

    document.getElementById('main-next')?.addEventListener('click', function () {
        if (currentPage < lastPage) loadSection(currentPage + 1);
    });

    try {
        const overviewRes = await window.axios.get('/api/syndicate/overview');
        renderOverview(overviewRes.data.data || {});
        await loadSection(1);
    } catch (error) {
        const parsed = window.showApiError ? window.showApiError(error) : { generalMessage: 'تعذر تحميل بيانات النقابة.' };
        list.innerHTML = `<p class="py-8 text-center text-sm text-red-500">${esc(parsed.generalMessage)}</p>`;
    }

    async function loadSection(page) {
        list.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">جارٍ تحميل البيانات...</p>';

        try {
            const params = ['categories', 'vendors', 'products', 'orders'].includes(endpoint)
                ? { page, per_page: 15 }
                : {};
            const sectionRes = await window.axios.get('/api/syndicate/' + endpoint, { params });
            const payload = sectionRes.data.data || {};
            const meta = sectionRes.data.meta || payload.meta || {};

            currentPage = meta.current_page || 1;
            lastPage = meta.last_page || 1;
            renderSection(section, payload);
            renderPagination(meta);
        } catch (error) {
            const parsed = window.showApiError ? window.showApiError(error) : { generalMessage: 'تعذر تحميل البيانات.' };
            list.innerHTML = `<p class="py-8 text-center text-sm text-red-500">${esc(parsed.generalMessage)}</p>`;
            pagination.classList.add('hidden');
            pagination.classList.remove('flex');
        }
    }

    function renderOverview(data) {
        const sales = data.sales_stats || {};
        const orders = data.order_stats || {};
        const cards = [
            ['التصنيفات', data.total_categories || 0],
            ['التجار', data.total_merchants || 0],
            ['المنتجات', data.total_products || 0],
            ['المبيعات المكتملة', money(sales.completed_sales)],
            ['الطلبات المكتملة', orders.completed_orders || 0],
            ['طلبات قيد الانتظار', orders.pending_orders || 0],
            ['متوسط الطلب', money(sales.average_order_value)],
            ['البودكاست', data.total_podcasts || 0],
        ];

        document.getElementById('overview-grid').innerHTML = cards.map(([label, value]) => `
            <div class="card card-body">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">${esc(label)}</p>
                <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">${esc(value)}</p>
            </div>
        `).join('');

        const side = [
            ...(data.top_selling_categories || []).map(row => ['تصنيف', row.name, row.sales_total]),
            ...(data.top_selling_products || []).map(row => ['منتج', row.name, row.sales_total]),
            ...(data.top_merchants_by_sales || []).map(row => ['تاجر', row.store_name, row.sales_total]),
        ].slice(0, 10);

        document.getElementById('side-list').innerHTML = side.length ? side.map(row => `
            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">${esc(row[1])}</p>
                    <p class="text-xs text-gray-500">${esc(row[0])}</p>
                </div>
                <span class="badge badge-brand">${esc(money(row[2]))}</span>
            </div>
        `).join('') : '<p class="py-6 text-center text-sm text-gray-400">لا توجد بيانات تحليلية بعد.</p>';
    }

    function renderSection(name, payload) {
        const rows = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload) ? payload : []);
        document.getElementById('main-title').textContent = sectionLabels[name] || 'السجلات';

        if (name === 'dashboard' || name === 'sales' || name === 'reports') {
            const data = name === 'reports' ? payload : (payload || {});
            list.innerHTML = renderReportBlocks(data);
            return;
        }

        if (!rows.length) {
            list.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">لا توجد سجلات مطابقة حالياً.</p>';
            return;
        }

        list.innerHTML = rows.map(row => renderRow(name, row)).join('');
    }

    function renderReportBlocks(data) {
        const blocks = [
            ['المبيعات', data.sales_stats || data.sales || {}],
            ['الطلبات', data.order_stats || data.orders || {}],
            ['المنتجات', data.product_stats || data.products || {}],
            ['التصنيفات', data.category_stats || data.categories || {}],
            ['التجار', data.merchant_stats || data.merchants || {}],
            ['البودكاست', data.podcast_stats || data.podcasts || {}],
        ];

        return blocks.map(([title, stats]) => `
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">${esc(title)}</h4>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    ${Object.entries(stats || {}).map(([key, value]) => `
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800">
                            <span class="text-gray-500">${esc(labelFor(key))}</span>
                            <span class="font-bold text-gray-900 dark:text-white">${esc(displayValue(value))}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('');
    }

    function renderRow(type, row) {
        const title = row.name || row.store_name || row.order_number || row.product_name || ('سجل #' + row.id);
        const subtitle = rowSubtitle(type, row);
        const metric = rowMetric(type, row);

        return `
            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">${esc(title)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${esc(subtitle)}</p>
                </div>
                <span class="badge badge-brand">${esc(metric)}</span>
            </div>
        `;
    }

    function rowSubtitle(type, row) {
        if (type === 'vendors') {
            return (row.categories || []).map(category => category.name || category).join('، ') || businessType(row.business_type);
        }

        if (type === 'products') {
            return [row.vendor?.store_name, row.subcategory?.category?.name].filter(Boolean).join(' · ');
        }

        if (type === 'orders') {
            return [row.user?.name || row.customer, row.vendor?.store_name || row.merchant, row.status].filter(Boolean).join(' · ');
        }

        return row.type_label || businessType(row.business_type) || row.status || row.category?.name || '';
    }

    function rowMetric(type, row) {
        if (type === 'categories') {
            return `${Number(row.products_count || 0)} منتج · ${Number(row.vendors_count || 0)} تاجر`;
        }

        if (type === 'vendors') {
            return `${Number(row.products_count || 0)} منتج`;
        }

        if (type === 'orders') {
            return money(row.total_amount);
        }

        return row.quantity ?? row.status ?? row.is_active ?? '';
    }

    function renderPagination(meta) {
        if (!meta.last_page || meta.last_page <= 1) {
            pagination.classList.add('hidden');
            pagination.classList.remove('flex');
            return;
        }

        pagination.classList.remove('hidden');
        pagination.classList.add('flex');
        document.getElementById('main-page-info').textContent = `صفحة ${meta.current_page} من ${meta.last_page} (${meta.total})`;
        document.getElementById('main-prev').disabled = meta.current_page <= 1;
        document.getElementById('main-next').disabled = meta.current_page >= meta.last_page;
    }

    function money(value) {
        return Number(value || 0).toLocaleString('ar-SY');
    }

    function businessType(value) {
        if (value === 'agriculture') return 'زراعي';
        if (value === 'veterinary') return 'بيطري';
        if (value === 'both') return 'زراعي وبيطري';
        return value || '';
    }

    function displayValue(value) {
        if (Array.isArray(value)) return `${value.length} عنصر`;
        if (value && typeof value === 'object') return value.total ?? value.count ?? Object.keys(value).length;
        if (typeof value === 'number') return money(value);
        return value ?? 0;
    }

    function labelFor(key) {
        const labels = {
            total_sales: 'إجمالي المبيعات',
            completed_sales: 'المبيعات المكتملة',
            pending_sales: 'المبيعات المعلقة',
            cancelled_sales: 'المبيعات الملغاة',
            refunded_sales: 'المبيعات المستردة',
            sales_today: 'مبيعات اليوم',
            sales_this_week: 'مبيعات الأسبوع',
            sales_this_month: 'مبيعات الشهر',
            sales_this_year: 'مبيعات السنة',
            average_order_value: 'متوسط قيمة الطلب',
            total_orders: 'إجمالي الطلبات',
            pending_orders: 'طلبات معلقة',
            processing_orders: 'طلبات قيد المعالجة',
            completed_orders: 'طلبات مكتملة',
            cancelled_orders: 'طلبات ملغاة',
            refunded_orders: 'طلبات مستردة',
            failed_orders: 'طلبات فاشلة',
            total_products: 'إجمالي المنتجات',
            active_products: 'منتجات نشطة',
            inactive_products: 'منتجات غير نشطة',
            total_categories: 'إجمالي التصنيفات',
            categories_with_products: 'تصنيفات لديها منتجات',
            categories_without_products: 'تصنيفات بلا منتجات',
            categories_with_merchants: 'تصنيفات لديها تجار',
            categories_without_merchants: 'تصنيفات بلا تجار',
            total_merchants: 'إجمالي التجار',
            active_merchants: 'تجار نشطون',
            inactive_merchants: 'تجار غير نشطين',
            total_podcasts: 'إجمالي البودكاست',
        };

        return labels[key] || key.replaceAll('_', ' ');
    }
});
</script>
@endpush
