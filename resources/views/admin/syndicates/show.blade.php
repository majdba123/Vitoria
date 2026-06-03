@extends('layouts.admin')

@section('title', 'تفاصيل وكيل النقابة - Vetora')
@section('page-title', 'تفاصيل وكيل النقابة')

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap justify-end gap-2">
        <a id="edit-link" class="btn-primary btn-sm" href="#">تعديل</a>
        <a href="{{ route('admin.syndicates.index') }}" class="btn-secondary btn-sm">عودة</a>
    </div>

    <div id="details-card" class="card card-body">
        <div class="py-12 text-center text-sm text-gray-400">جاري تحميل التفاصيل...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const syndicateId = @json($syndicateId);
const esc = value => { const div = document.createElement('div'); div.textContent = value == null ? '' : String(value); return div.innerHTML; };
const fmtDate = value => value ? new Date(value).toLocaleDateString('ar-SY', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';
const fmtMoney = value => `${Number(value || 0).toLocaleString('ar-SY')} SYP`;
load();

async function load() {
    const card = document.getElementById('details-card');
    try {
        const res = await window.axios.get('/api/admin/syndicates/' + syndicateId, { silent: true });
        const s = res.data.data;
        document.getElementById('edit-link').href = '/admin/syndicates/' + s.id + '/edit';
        const typeLabel = s.type === 'agriculture' ? 'زراعي' : 'بيطري';
        const statusLabel = s.is_active ? 'نشط' : 'غير نشط';
        const userName = s.user?.name || '—';
        const userEmail = s.user?.email || s.email || '—';
        const userPhone = s.user?.phone_number || s.phone || '—';

        card.innerHTML = `
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-50 text-2xl font-black text-brand-700 ring-1 ring-brand-100">
                    <img src="${esc(s.logo_url)}" class="h-full w-full object-cover" alt="" onerror="this.src='{{ asset('images/syndicate-placeholder.svg') }}'">
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-2xl font-black text-gray-900 dark:text-white">${esc(s.name)}</h2>
                        <span class="badge ${s.is_active ? 'badge-success' : 'badge-danger'}">${esc(statusLabel)}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">${esc(userEmail)} · ${esc(userPhone)}</p>
                    <p class="mt-2 text-xs font-bold text-brand-700 dark:text-brand-300">${esc(typeLabel)}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                ${metric('التصنيفات المتاحة', Number(s.categories_count || 0))}
                ${metric('التجار المرتبطون', Number(s.vendors_count || 0))}
                ${metric('المنتجات', Number(s.products_count || 0))}
                ${metric('الطلبات المكتملة', Number(s.completed_orders_count || 0))}
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                ${metric('إجمالي المبيعات', fmtMoney(s.total_sales))}
                ${metric('كل الطلبات', Number(s.orders_count || 0))}
                ${metric('تاريخ الإنشاء', fmtDate(s.created_at))}
                ${metric('آخر تحديث', fmtDate(s.updated_at))}
            </div>

            <div class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <h3 class="text-sm font-black text-gray-900 dark:text-white">حساب المستخدم المرتبط</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    ${mini('الاسم', userName)}
                    ${mini('البريد الإلكتروني', userEmail)}
                    ${mini('الهاتف', userPhone)}
                </div>
            </div>
        `;
    } catch (error) {
        const parsed = window.showApiError ? window.showApiError(error) : window.ApiErrors.parse(error);
        card.innerHTML = `<p class="py-12 text-center text-sm text-red-500">${esc(parsed.generalMessage)}</p>`;
    }
}

function metric(label, value) {
    return `<div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <p class="text-xs font-bold text-gray-500">${esc(label)}</p>
        <p class="mt-2 text-lg font-black text-gray-900 dark:text-white">${esc(value)}</p>
    </div>`;
}

function mini(label, value) {
    return `<div>
        <p class="text-[11px] font-bold text-gray-400">${esc(label)}</p>
        <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">${esc(value)}</p>
    </div>`;
}
</script>
@endpush
