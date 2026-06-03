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
const esc = (v) => { const d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; };
const fmtDate = (v) => v ? new Date(v).toLocaleDateString('ar-SY', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';
load();

async function load() {
    const card = document.getElementById('details-card');
    try {
        const res = await window.axios.get('/api/admin/syndicates/' + syndicateId, { silent: true });
        const s = res.data.data;
        document.getElementById('edit-link').href = '/admin/syndicates/' + s.id + '/edit';
        card.innerHTML = `
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-50 text-2xl font-black text-brand-700">
                    ${s.logo_url ? `<img src="${esc(s.logo_url)}" class="h-full w-full object-cover" alt="">` : esc((s.name || '?').charAt(0))}
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white">${esc(s.name)}</h2>
                    <p class="mt-1 text-sm text-gray-500">${esc(s.email || '—')} · ${esc(s.phone || 'لا يوجد هاتف')}</p>
                </div>
                <span class="badge ${s.is_active ? 'badge-success' : 'badge-danger'}">${s.is_active ? 'نشط' : 'غير نشط'}</span>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                ${metric('النوع', s.type === 'agriculture' ? 'زراعي' : 'بيطري')}
                ${metric('التصنيفات', Number(s.categories_count || 0))}
                ${metric('التجار', Number(s.vendors_count || 0))}
                ${metric('الطلبات', Number(s.orders_count || 0))}
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                ${metric('حساب المستخدم', '#' + s.user_id)}
                ${metric('تاريخ الإنشاء', fmtDate(s.created_at))}
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
</script>
@endpush
