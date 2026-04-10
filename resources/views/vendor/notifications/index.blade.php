@extends('layouts.vendor')

@section('title', 'Notifications — SyriaZone Vendor')
@section('page-title', 'سجل الإشعارات')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400">عرض جميع الإشعارات المرسلة إليك.</p>
        <button type="button" id="vendor-notif-mark-all-read" class="btn-primary btn-sm">
            تحديد الكل كمقروء
        </button>
    </div>

    <div id="vendor-notif-loading" class="card py-14 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-emerald-500 dark:border-gray-700 dark:border-t-emerald-400"></div>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Loading...</p>
    </div>

    <div id="vendor-notif-empty" class="hidden card py-14 text-center">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">No notifications.</p>
    </div>

    <div id="vendor-notif-list-wrap" class="hidden overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <ul id="vendor-notif-list" class="divide-y divide-gray-100 dark:divide-gray-800"></ul>
        <div id="vendor-notif-pagination" class="flex items-center justify-between border-t border-gray-100 px-4 py-3 dark:border-gray-800">
            <p id="vendor-notif-page-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div class="flex gap-2">
                <button type="button" id="vendor-notif-prev" class="btn-secondary btn-xs">السابق</button>
                <button type="button" id="vendor-notif-next" class="btn-secondary btn-xs">Next</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const state = { page: 1 };
    const context = 'vendor';

    function linkUrl(actionType, actionId) {
        if (!actionType || actionId == null) return null;
        const id = String(actionId);
        if (context === 'vendor') {
            if (actionType === 'product') return '{{ url("/products") }}/' + id;
            if (actionType === 'order') return '{{ url("/vendor/orders") }}/' + id;
        }
        if (actionType === 'product') return '{{ url("/products") }}/' + id;
        if (actionType === 'order') return '{{ url("/vendor/orders") }}/' + id;
        return null;
    }

    function esc(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function load() {
        document.getElementById('vendor-notif-loading').classList.remove('hidden');
        document.getElementById('vendor-notif-empty').classList.add('hidden');
        document.getElementById('vendor-notif-list-wrap').classList.add('hidden');

        window.axios.get('/api/notifications', { params: { page: state.page, per_page: 15 } }).then(function (res) {
            document.getElementById('vendor-notif-loading').classList.add('hidden');
            const data = res.data || {};
            const items = Array.isArray(data.data) ? data.data : [];
            const meta = data.meta || {};
            const currentPage = meta.current_page || 1;
            const lastPage = meta.last_page || 1;
            const total = meta.total || 0;

            if (items.length === 0) {
                document.getElementById('vendor-notif-empty').classList.remove('hidden');
                return;
            }

            const listEl = document.getElementById('vendor-notif-list');
            listEl.innerHTML = items.map(function (n) {
                const isUnread = !n.read_at;
                let time = '';
                try {
                    if (n.sent_at) time = new Date(n.sent_at).toLocaleDateString(undefined, { dateStyle: 'short', timeStyle: 'short' });
                } catch (e) {}
                const body = n.body != null ? esc(String(n.body)) : '';
                const sender = n.sender_name != null ? esc(String(n.sender_name)) : '';
                const href = linkUrl(n.action_type, n.action_id != null ? n.action_id : null);
                const tag = href ? 'a' : 'div';
                const attrs = href ? ' href="' + esc(href) + '" class="block"' : ' class="block"';
                return '<li class="' + (isUnread ? 'bg-emerald-50/30 dark:bg-emerald-500/5' : '') + '">' +
                    '<' + tag + attrs + ' data-nid="' + esc(String(n.id)) + '">' +
                    '<div class="flex items-start gap-3 px-4 py-3.5">' +
                    (isUnread ? '<div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500 dark:bg-emerald-400"></div>' : '<div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-gray-300 dark:bg-gray-600"></div>') +
                    '<div class="min-w-0 flex-1">' +
                    '<p class="text-sm font-medium text-gray-900 dark:text-white">' + body + '</p>' +
                    '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">' + time + (sender ? ' · ' + sender : '') + '</p>' +
                    (isUnread ? '<button type="button" class="vendor-notif-mark-one mt-2 text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400" data-id="' + esc(String(n.id)) + '">تحديد كمقروء</button>' : '') +
                    '</div>' +
                    (href ? '<svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>' : '') +
                    '</div></' + tag + '></li>';
            }).join('');

            document.getElementById('vendor-notif-list-wrap').classList.remove('hidden');
            document.getElementById('vendor-notif-page-info').textContent = 'Page ' + currentPage + ' of ' + lastPage + (total ? ' (' + total + ')' : '');
            document.getElementById('vendor-notif-prev').disabled = currentPage <= 1;
            document.getElementById('vendor-notif-next').disabled = currentPage >= lastPage;

            listEl.querySelectorAll('.vendor-notif-mark-one').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = btn.getAttribute('data-id');
                    if (!id) return;
                    btn.disabled = true;
                    window.axios.patch('/api/notifications/' + id + '/read').then(function () {
                        load();
                    }).finally(function () { btn.disabled = false; });
                });
            });
        }).catch(function () {
            document.getElementById('vendor-notif-loading').classList.add('hidden');
            document.getElementById('vendor-notif-empty').classList.remove('hidden');
            document.getElementById('vendor-notif-empty').querySelector('p').textContent = 'Failed to load notifications.';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        load();
        document.getElementById('vendor-notif-mark-all-read').addEventListener('click', function () {
            this.disabled = true;
            window.axios.post('/api/notifications/mark-all-read').then(load).finally(function () {
                document.getElementById('vendor-notif-mark-all-read').disabled = false;
            });
        });
        document.getElementById('vendor-notif-prev').addEventListener('click', function () {
            if (state.page > 1) { state.page--; load(); }
        });
        document.getElementById('vendor-notif-next').addEventListener('click', function () {
            state.page++; load();
        });
    });
})();
</script>
@endpush
