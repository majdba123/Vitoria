@extends('layouts.admin')

@section('title', 'إدارة النقابات - Vetora')
@section('page-title', 'إدارة النقابات')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-black text-gray-900 dark:text-white">وكلاء النقابات</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">إدارة وكلاء الزراعة والبيطرة وصلاحيات الوصول حسب النوع.</p>
        </div>
        <a href="{{ route('admin.syndicates.create') }}" class="btn-primary btn-sm">إضافة وكيل نقابة</a>
    </div>

    <div class="card card-body">
        <div class="grid gap-3 lg:grid-cols-4">
            <div>
                <label class="form-label">النوع</label>
                <select id="filter-type" class="form-input">
                    <option value="">كل الأنواع</option>
                    <option value="agriculture">زراعي</option>
                    <option value="veterinary">بيطري</option>
                </select>
            </div>
            <div>
                <label class="form-label">الحالة</label>
                <select id="filter-status" class="form-input">
                    <option value="">كل الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="form-label">بحث</label>
                <input id="filter-search" class="form-input" placeholder="ابحث بالاسم أو البريد أو الهاتف">
            </div>
        </div>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
            <button id="clear-filters" class="btn-secondary btn-sm">مسح الفلاتر</button>
            <button id="apply-filters" class="btn-primary btn-sm">تطبيق</button>
        </div>
    </div>

    <div id="syndicates-alert" class="hidden rounded-xl border px-4 py-3 text-sm font-semibold"></div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-bold text-gray-500">الاسم</th>
                        <th class="px-4 py-3 text-start text-xs font-bold text-gray-500">الحساب</th>
                        <th class="px-4 py-3 text-start text-xs font-bold text-gray-500">النوع</th>
                        <th class="px-4 py-3 text-start text-xs font-bold text-gray-500">الحالة</th>
                        <th class="px-4 py-3 text-start text-xs font-bold text-gray-500">البيانات</th>
                        <th class="px-4 py-3 text-start text-xs font-bold text-gray-500">تاريخ الإنشاء</th>
                        <th class="px-4 py-3 text-end text-xs font-bold text-gray-500">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="syndicates-body" class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">جاري تحميل النقابات...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="pagination" class="hidden items-center justify-between border-t border-gray-100 px-4 py-3 text-sm dark:border-gray-800">
            <p id="page-info" class="text-xs text-gray-500"></p>
            <div class="flex gap-2">
                <button id="prev-page" class="btn-secondary btn-xs">السابق</button>
                <button id="next-page" class="btn-secondary btn-xs">التالي</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('syndicates-body');
    const alertBox = document.getElementById('syndicates-alert');
    const pagination = document.getElementById('pagination');
    let currentPage = 1;
    let lastPage = 1;
    let searchTimer = null;

    const esc = (v) => { const d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; };
    const fmtDate = (v) => v ? new Date(v).toLocaleDateString('ar-SY', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
    const typeText = (type) => type === 'agriculture' ? 'زراعي' : (type === 'veterinary' ? 'بيطري' : type);
    const statusBadge = (row) => `<span class="badge ${row.is_active ? 'badge-success' : 'badge-danger'}">${row.is_active ? 'نشط' : 'غير نشط'}</span>`;

    document.getElementById('apply-filters').addEventListener('click', () => { currentPage = 1; load(); });
    document.getElementById('clear-filters').addEventListener('click', () => {
        document.getElementById('filter-type').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-search').value = '';
        currentPage = 1;
        load();
    });
    document.getElementById('filter-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; load(); }, 350);
    });
    document.getElementById('prev-page').addEventListener('click', () => { if (currentPage > 1) { currentPage--; load(); } });
    document.getElementById('next-page').addEventListener('click', () => { if (currentPage < lastPage) { currentPage++; load(); } });

    load();

    async function load() {
        hideAlert();
        body.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">جاري تحميل النقابات...</td></tr>';
        const params = new URLSearchParams({ page: String(currentPage), per_page: '15' });
        ['type', 'status', 'search'].forEach(key => {
            const value = document.getElementById('filter-' + key).value;
            if (value) params.append(key, value);
        });

        try {
            const res = await window.axios.get('/api/admin/syndicates?' + params.toString(), { silent: true });
            const rows = res.data.data || [];
            const meta = res.data.meta || {};
            currentPage = meta.current_page || 1;
            lastPage = meta.last_page || 1;
            renderRows(rows);
            renderPagination(meta);
        } catch (error) {
            showAlert(window.showApiError ? window.showApiError(error).generalMessage : 'تعذر تحميل النقابات.', 'error');
            body.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-sm text-red-500">تعذر تحميل البيانات.</td></tr>';
        }
    }

    function renderRows(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">لا توجد نقابات مطابقة للفلاتر الحالية.</td></tr>';
            return;
        }

        body.innerHTML = rows.map(row => `
            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/50">
                <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-sm font-black text-brand-700">
                            ${row.logo_url ? `<img src="${esc(row.logo_url)}" class="h-full w-full object-cover" alt="">` : esc((row.name || '?').charAt(0))}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-gray-900 dark:text-white">${esc(row.name)}</p>
                            <p class="text-xs text-gray-500">${esc(row.phone || 'لا يوجد هاتف')}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-200">
                    <p class="font-semibold">${esc(row.email || row.user?.email || '—')}</p>
                    <p class="text-xs text-gray-400">#${esc(row.user_id)}</p>
                </td>
                <td class="px-4 py-4"><span class="badge badge-brand">${esc(typeText(row.type))}</span></td>
                <td class="px-4 py-4">${statusBadge(row)}</td>
                <td class="px-4 py-4">
                    <div class="grid min-w-48 grid-cols-2 gap-1 text-xs text-gray-500">
                        <span>${Number(row.categories_count || 0)} تصنيف</span>
                        <span>${Number(row.vendors_count || 0)} تاجر</span>
                        <span>${Number(row.products_count || 0)} منتج</span>
                        <span>${Number(row.orders_count || 0)} طلب</span>
                    </div>
                </td>
                <td class="px-4 py-4 text-sm text-gray-500">${fmtDate(row.created_at)}</td>
                <td class="px-4 py-4">
                    <div class="flex justify-end gap-2">
                        <a href="/admin/syndicates/${row.id}" class="btn-secondary btn-xs">عرض</a>
                        <a href="/admin/syndicates/${row.id}/edit" class="btn-primary btn-xs">تعديل</a>
                        <button onclick="toggleSyndicate(${row.id})" class="btn-secondary btn-xs">${row.is_active ? 'تعطيل' : 'تفعيل'}</button>
                        <button onclick="deleteSyndicate(${row.id})" class="btn-danger btn-xs">حذف</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(meta) {
        if ((meta.last_page || 1) <= 1) {
            pagination.classList.add('hidden');
            pagination.classList.remove('flex');
            return;
        }

        pagination.classList.remove('hidden');
        pagination.classList.add('flex');
        document.getElementById('page-info').textContent = `صفحة ${meta.current_page} من ${meta.last_page} (${meta.total})`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    function showAlert(message, type = 'success') {
        alertBox.textContent = message;
        alertBox.className = type === 'success'
            ? 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700'
            : 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700';
        alertBox.classList.remove('hidden');
    }

    function hideAlert() {
        alertBox.classList.add('hidden');
    }

    window.toggleSyndicate = async function (id) {
        try {
            await window.axios.patch('/api/admin/syndicates/' + id + '/toggle-active', {}, { silent: true });
            showAlert('تم تحديث حالة النقابة بنجاح.');
            load();
        } catch (error) {
            showAlert(window.showApiError ? window.showApiError(error).generalMessage : 'تعذر تحديث الحالة.', 'error');
        }
    };

    window.deleteSyndicate = async function (id) {
        if (!confirm('هل تريد حذف وكيل النقابة؟ لا يمكن التراجع عن هذا الإجراء.')) return;
        try {
            await window.axios.delete('/api/admin/syndicates/' + id, { silent: true });
            showAlert('تم حذف وكيل النقابة بنجاح.');
            load();
        } catch (error) {
            showAlert(window.showApiError ? window.showApiError(error).generalMessage : 'تعذر حذف وكيل النقابة.', 'error');
        }
    };
});
</script>
@endpush
