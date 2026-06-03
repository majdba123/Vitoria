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
<div class="space-y-6">
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-black text-brand-700 dark:text-brand-300">مساحة عمل النقابة</p>
                <h2 id="syndicate-name" class="mt-2 text-2xl font-black text-gray-900 dark:text-white">جاري تحميل البيانات...</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-gray-500 dark:text-gray-400">
                    تظهر هنا البيانات المرتبطة بنوع النقابة فقط، مع منع عرض بيانات النوع الآخر من خلال استعلامات الخادم.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span id="syndicate-type" class="badge badge-brand">-</span>
                <span id="syndicate-status" class="badge badge-success">-</span>
                <a href="{{ route('syndicate.sales') }}" class="btn-secondary btn-sm">المبيعات</a>
                <a href="{{ route('syndicate.reports') }}" class="btn-primary btn-sm">التقارير</a>
            </div>
        </div>
    </section>

    <section id="overview-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @for ($i = 0; $i < 8; $i++)
            <div class="card card-body">
                <div class="skeleton h-4 w-24 rounded"></div>
                <div class="skeleton mt-4 h-8 w-20 rounded"></div>
            </div>
        @endfor
    </section>

    <section class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-body flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white" id="main-title">السجلات</h3>
                    <p id="main-subtitle" class="mt-1 text-xs text-gray-500">يتم تحميل البيانات حسب نوع النقابة الحالية.</p>
                </div>
                <span id="main-count" class="badge badge-brand hidden"></span>
            </div>
            <div id="main-list" class="card-body space-y-3">
                <p class="py-8 text-center text-sm text-gray-400">جاري تحميل البيانات...</p>
            </div>
            <div id="main-pagination" class="hidden items-center justify-between border-t border-gray-100 px-4 py-3 text-sm dark:border-gray-800">
                <p id="main-page-info" class="text-xs text-gray-500"></p>
                <div class="flex gap-2">
                    <button id="main-prev" type="button" class="btn-secondary btn-xs">السابق</button>
                    <button id="main-next" type="button" class="btn-secondary btn-xs">التالي</button>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card">
                <div class="card-body border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-black text-gray-900 dark:text-white">الأعلى أداء</h3>
                    <p class="mt-1 text-xs text-gray-500">أفضل التصنيفات والمنتجات والتجار حسب المبيعات.</p>
                </div>
                <div id="side-list" class="card-body space-y-3"></div>
            </div>

            <div class="card">
                <div class="card-body border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-black text-gray-900 dark:text-white">ملخص سريع</h3>
                </div>
                <div id="quick-summary" class="card-body space-y-2"></div>
            </div>
        </div>
    </section>
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
    let overview = {};

    document.getElementById('main-prev')?.addEventListener('click', function () {
        if (currentPage > 1) loadSection(currentPage - 1);
    });

    document.getElementById('main-next')?.addEventListener('click', function () {
        if (currentPage < lastPage) loadSection(currentPage + 1);
    });

    try {
        const overviewRes = await window.axios.get('/api/syndicate/overview');
        overview = overviewRes.data.data || {};
        renderHeader(overview.syndicate || {});
        renderOverview(overview);
        await loadSection(1);
    } catch (error) {
        showLoadError(error, 'تعذر تحميل بيانات النقابة.');
    }

    async function loadSection(page) {
        list.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">جاري تحميل البيانات...</p>';
        document.getElementById('main-count').classList.add('hidden');

        try {
            const params = ['categories', 'vendors', 'products', 'orders'].includes(endpoint) ? { page, per_page: 15 } : {};
            const sectionRes = await window.axios.get('/api/syndicate/' + endpoint, { params });
            const payload = sectionRes.data.data || {};
            const meta = sectionRes.data.meta || payload.meta || {};

            currentPage = meta.current_page || 1;
            lastPage = meta.last_page || 1;
            renderSection(section, payload, meta);
            renderPagination(meta);
        } catch (error) {
            showLoadError(error, 'تعذر تحميل البيانات.');
        }
    }

    function renderHeader(syndicate) {
        document.getElementById('syndicate-name').textContent = syndicate.name || 'نقابة Vetora';
        document.getElementById('syndicate-type').textContent = typeLabel(syndicate.type);
        document.getElementById('syndicate-status').textContent = syndicate.status === 'inactive' ? 'غير نشط' : 'نشط';
        document.getElementById('syndicate-status').className = syndicate.status === 'inactive' ? 'badge badge-danger' : 'badge badge-success';
    }

    function renderOverview(data) {
        const sales = data.sales_stats || {};
        const orders = data.order_stats || {};
        const cards = [
            ['التصنيفات', data.total_categories || 0, 'fa-solid fa-layer-group'],
            ['التجار', data.total_merchants || 0, 'fa-solid fa-store'],
            ['المنتجات', data.total_products || 0, 'fa-solid fa-box-open'],
            ['الطلبات المكتملة', orders.completed_orders || 0, 'fa-solid fa-circle-check'],
            ['المبيعات المكتملة', money(sales.completed_sales), 'fa-solid fa-chart-line'],
            ['طلبات قيد الانتظار', orders.pending_orders || 0, 'fa-solid fa-clock'],
            ['متوسط الطلب', money(sales.average_order_value), 'fa-solid fa-receipt'],
            ['البودكاست', data.total_podcasts || 0, 'fa-solid fa-microphone-lines'],
        ];

        document.getElementById('overview-grid').innerHTML = cards.map(([label, value, icon]) => `
            <div class="card card-body">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-black text-gray-500">${esc(label)}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                        <i class="${icon}" aria-hidden="true"></i>
                    </span>
                </div>
                <p class="mt-4 text-2xl font-black text-gray-900 dark:text-white">${esc(value)}</p>
            </div>
        `).join('');

        const sideRows = [
            ...(data.top_selling_categories || []).map(row => ['تصنيف', row.name, row.sales_total]),
            ...(data.top_selling_products || []).map(row => ['منتج', row.name, row.sales_total]),
            ...(data.top_merchants_by_sales || []).map(row => ['تاجر', row.store_name, row.sales_total]),
        ].slice(0, 10);

        document.getElementById('side-list').innerHTML = sideRows.length ? sideRows.map(row => `
            <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-900 dark:text-white">${esc(row[1] || 'بدون اسم')}</p>
                    <p class="text-xs text-gray-500">${esc(row[0])}</p>
                </div>
                <span class="badge badge-brand">${esc(money(row[2]))}</span>
            </div>
        `).join('') : emptyState('لا توجد بيانات أداء بعد.');

        document.getElementById('quick-summary').innerHTML = [
            ['مبيعات اليوم', money(sales.sales_today)],
            ['مبيعات هذا الشهر', money(sales.sales_this_month)],
            ['طلبات هذا الشهر', orders.orders_this_month || 0],
            ['طلبات اليوم', orders.orders_today || 0],
        ].map(row => `
            <div class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800">
                <span class="text-gray-500">${esc(row[0])}</span>
                <span class="font-black text-gray-900 dark:text-white">${esc(row[1])}</span>
            </div>
        `).join('');
    }

    function renderSection(name, payload, meta) {
        document.getElementById('main-title').textContent = sectionLabels[name] || 'السجلات';
        document.getElementById('main-subtitle').textContent = subtitleFor(name);

        if (name === 'dashboard' || name === 'sales') {
            list.innerHTML = renderReportBlocks(overview);
            return;
        }

        if (name === 'reports') {
            list.innerHTML = renderReportBlocks(payload);
            return;
        }

        const rows = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload) ? payload : []);
        const countBadge = document.getElementById('main-count');
        if (meta.total != null) {
            countBadge.textContent = `${meta.total} سجل`;
            countBadge.classList.remove('hidden');
        }

        if (!rows.length) {
            list.innerHTML = emptyState('لا توجد بيانات متاحة حاليا.');
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
            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <h4 class="text-sm font-black text-gray-900 dark:text-white">${esc(title)}</h4>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    ${Object.entries(stats || {}).slice(0, 10).map(([key, value]) => `
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800">
                            <span class="text-gray-500">${esc(labelFor(key))}</span>
                            <span class="font-black text-gray-900 dark:text-white">${esc(displayValue(value))}</span>
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
            <div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="truncate text-sm font-black text-gray-900 dark:text-white">${esc(title)}</p>
                    <p class="mt-1 text-xs leading-6 text-gray-500">${esc(subtitle)}</p>
                </div>
                <span class="badge badge-brand self-start sm:self-center">${esc(metric)}</span>
            </div>
        `;
    }

    function rowSubtitle(type, row) {
        if (type === 'vendors') {
            return (row.categories || []).map(category => category.name || category).join('، ') || businessType(row.business_type);
        }

        if (type === 'products') {
            return [row.vendor?.store_name, row.subcategory?.category?.name, row.is_active ? 'نشط' : 'غير نشط'].filter(Boolean).join(' · ');
        }

        if (type === 'orders') {
            return [row.user?.name || row.customer, row.vendor?.store_name || row.merchant, orderStatus(row.status)].filter(Boolean).join(' · ');
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

        if (type === 'products') {
            return row.is_active ? 'نشط' : 'غير نشط';
        }

        return row.quantity ?? row.status ?? '';
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

    function showLoadError(error, fallback) {
        const parsed = window.showApiError ? window.showApiError(error) : { generalMessage: fallback };
        list.innerHTML = `<p class="py-8 text-center text-sm text-red-500">${esc(parsed.generalMessage || fallback)}</p>`;
        pagination.classList.add('hidden');
        pagination.classList.remove('flex');
    }

    function emptyState(message) {
        return `<p class="py-8 text-center text-sm text-gray-400">${esc(message)}</p>`;
    }

    function subtitleFor(name) {
        const subtitles = {
            dashboard: 'ملخص شامل للطلبات والمبيعات والمنتجات.',
            categories: 'التصنيفات المسموحة لهذا النوع فقط.',
            vendors: 'التجار المرتبطون بنوع النقابة أو بتصنيفات هذا النوع.',
            products: 'المنتجات المرتبطة بتصنيفات هذا النوع.',
            podcasts: 'البودكاست المرتبط بنوع النقابة عند توفره.',
            orders: 'الطلبات المرتبطة بمنتجات هذا النوع.',
            sales: 'تحليل المبيعات حسب نوع النقابة.',
            reports: 'تقرير تشغيلي مختصر حسب نوع النقابة.',
        };

        return subtitles[name] || 'بيانات النقابة الحالية.';
    }

    function money(value) {
        return `${Number(value || 0).toLocaleString('ar-SY')} SYP`;
    }

    function typeLabel(type) {
        if (type === 'agriculture') return 'زراعي';
        if (type === 'veterinary') return 'بيطري';
        return 'نقابة';
    }

    function businessType(value) {
        if (value === 'agriculture') return 'زراعي';
        if (value === 'veterinary') return 'بيطري';
        if (value === 'both') return 'زراعي وبيطري';
        return value || '';
    }

    function orderStatus(value) {
        const statuses = {
            pending: 'قيد الانتظار',
            confirmed: 'قيد المعالجة',
            completed: 'مكتمل',
            cancelled: 'ملغي',
        };

        return statuses[value] || value || '';
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
            orders_today: 'طلبات اليوم',
            orders_this_month: 'طلبات الشهر',
            total_products: 'إجمالي المنتجات',
            active_products: 'منتجات نشطة',
            inactive_products: 'منتجات غير نشطة',
            products_with_images: 'منتجات لديها صور',
            products_without_images: 'منتجات بلا صور',
            total_categories: 'إجمالي التصنيفات',
            categories_with_products: 'تصنيفات لديها منتجات',
            categories_without_products: 'تصنيفات بلا منتجات',
            categories_with_merchants: 'تصنيفات لديها تجار',
            categories_without_merchants: 'تصنيفات بلا تجار',
            total_merchants: 'إجمالي التجار',
            active_merchants: 'تجار نشطون',
            inactive_merchants: 'تجار غير نشطين',
            merchants_with_products: 'تجار لديهم منتجات',
            merchants_without_products: 'تجار بلا منتجات',
            total_podcasts: 'إجمالي البودكاست',
            active_podcasts: 'بودكاست منشور',
            inactive_podcasts: 'بودكاست غير منشور',
        };

        return labels[key] || key.replaceAll('_', ' ');
    }
});
</script>
@endpush
