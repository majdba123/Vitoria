@extends('layouts.admin')

@section('title', 'Contact Messages — SyriaZone Admin')
@section('page-title', 'Contact Messages')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400">View and reply to contact form submissions from the landing page.</p>
        <div class="flex items-center gap-2">
            <select id="cm-filter-status" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none transition-colors focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="" {{ ($filterStatus ?? '') === '' ? 'selected' : '' }}>All statuses</option>
                <option value="pending" {{ ($filterStatus ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="replied" {{ ($filterStatus ?? '') === 'replied' ? 'selected' : '' }}>Replied</option>
            </select>
        </div>
    </div>

    <div id="cm-loading" class="card py-14 text-center {{ isset($initialMessages) ? 'hidden' : '' }}">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700 dark:border-t-brand-400"></div>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Loading...</p>
    </div>

    <div id="cm-empty" class="card py-14 text-center {{ isset($initialMessages) && count($initialMessages) === 0 ? '' : 'hidden' }}">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">No contact messages yet.</p>
    </div>

    <div id="cm-list-wrap" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 {{ (!isset($initialMessages) || count($initialMessages) === 0) ? 'hidden' : '' }}">
        <ul id="cm-list" class="divide-y divide-gray-100 dark:divide-gray-800">
            @isset($initialMessages)
                @foreach($initialMessages as $m)
                <li class="px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $m['name'] ?? ($m['user']['name'] ?? '—') }} &lt;{{ $m['email'] ?? ($m['user']['email'] ?? '—') }}&gt;</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $m['created_at'] ? \Carbon\Carbon::parse($m['created_at'])->format('M j, Y, g:i A') : '—' }}</p>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ e($m['message'] ?? '') }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if(($m['status'] ?? '') === 'replied')
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">Replied</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">Replied</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">Pending</span>
                                <button type="button" class="cm-reply-btn rounded-lg border border-brand-500 px-2.5 py-1.5 text-xs font-bold text-brand-600 hover:bg-brand-50 dark:border-brand-400 dark:text-brand-400 dark:hover:bg-brand-500/10" data-id="{{ $m['id'] }}" data-message="{{ e($m['message'] ?? '') }}" data-email="{{ e($m['email'] ?? $m['user']['email'] ?? '') }}">Reply</button>
                            @endif
                        </div>
                    </div>
                </li>
                @endforeach
            @endisset
        </ul>
        @php
            $hasPagination = isset($meta) && (($meta['last_page'] ?? 1) > 1);
            $currentPage = isset($meta) ? ($meta['current_page'] ?? 1) : 1;
            $lastPage = isset($meta) ? ($meta['last_page'] ?? 1) : 1;
        @endphp
        <div id="cm-pagination" class="flex items-center justify-between border-t border-gray-100 px-4 py-3 dark:border-gray-800 {{ $hasPagination ? '' : 'hidden' }}">
            <p id="cm-page-info" class="text-xs text-gray-500 dark:text-gray-400">
                @if(isset($meta))
                    Page {{ $currentPage }} of {{ $lastPage }}
                    @if(!empty($meta['total']))
                        ({{ $meta['total'] }})
                    @endif
                @endif
            </p>
            <div class="flex gap-2">
                <button type="button" id="cm-prev" class="btn-secondary btn-xs" @if($currentPage <= 1) disabled @endif>Prev</button>
                <button type="button" id="cm-next" class="btn-secondary btn-xs" @if($currentPage >= $lastPage) disabled @endif>Next</button>
            </div>
        </div>
    </div>
</div>

{{-- Reply modal --}}
<div id="cm-reply-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4" aria-hidden="true">
    <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900" role="dialog" aria-labelledby="cm-reply-title">
        <h3 id="cm-reply-title" class="text-lg font-bold text-gray-900 dark:text-white">Reply to message</h3>
        <div id="cm-reply-original" class="mt-3 rounded-xl border border-gray-100 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300"></div>
        <form id="cm-reply-form" class="mt-4 space-y-3">
            <input type="hidden" id="cm-reply-id" name="id">
            <div>
                <label for="cm-reply-text" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Your reply</label>
                <textarea id="cm-reply-text" name="admin_reply" rows="4" required maxlength="5000" placeholder="Type your reply..."
                    class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                <p id="cm-reply-err" class="mt-1 hidden text-xs text-red-500"></p>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" id="cm-reply-cancel" class="btn-secondary btn-sm">Cancel</button>
                <button type="submit" id="cm-reply-submit" class="btn-primary btn-sm">Send reply</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const state = { page: {{ $currentPage ?? 1 }} };
    const baseUrl = '/api/admin/contact-messages';

    function esc(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function attrEsc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function getAuthHeaders() {
        if (window.Auth && window.Auth.applyToken) window.Auth.applyToken();
        const token = window.Auth && window.Auth.getToken && window.Auth.getToken();
        return token ? { Authorization: 'Bearer ' + token } : {};
    }

    function bindReplyButtons() {
        document.querySelectorAll('.cm-reply-btn').forEach(function (btn) {
            if (btn._cmBound) return;
            btn._cmBound = true;
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-id');
                var message = btn.getAttribute('data-message');
                var email = btn.getAttribute('data-email');
                document.getElementById('cm-reply-id').value = id;
                document.getElementById('cm-reply-original').innerHTML = '<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">From: ' + esc(email) + '</p><p class="mt-1.5">' + esc(message) + '</p>';
                document.getElementById('cm-reply-text').value = '';
                document.getElementById('cm-reply-err').classList.add('hidden');
                document.getElementById('cm-reply-modal').classList.remove('hidden');
                document.getElementById('cm-reply-modal').classList.add('flex');
            });
        });
    }

    function load() {
        const loadingEl = document.getElementById('cm-loading');
        const emptyEl = document.getElementById('cm-empty');
        const listWrapEl = document.getElementById('cm-list-wrap');
        const filterEl = document.getElementById('cm-filter-status');
        if (!loadingEl || !emptyEl || !listWrapEl) return;

        loadingEl.classList.remove('hidden');
        emptyEl.classList.add('hidden');
        listWrapEl.classList.add('hidden');

        const params = new URLSearchParams({ page: state.page, per_page: 15 });
        if (filterEl && filterEl.value) params.set('status', filterEl.value);

        const url = (window.location.origin || '') + baseUrl + '?' + params.toString();
        window.axios.get(url, { headers: getAuthHeaders(), withCredentials: true }).then(function (res) {
            loadingEl.classList.add('hidden');
            var data = res && res.data;
            if (!data || typeof data !== 'object') {
                emptyEl.classList.remove('hidden');
                var p = emptyEl.querySelector('p');
                if (p) p.textContent = 'Unexpected response from server.';
                return;
            }
            var items = Array.isArray(data.data) ? data.data : [];
            var meta = data.meta && typeof data.meta === 'object' ? data.meta : {};
            var currentPage = meta.current_page || 1;
            var lastPage = meta.last_page || 1;
            var total = meta.total || 0;

            if (items.length === 0) {
                emptyEl.classList.remove('hidden');
                return;
            }

            try {
                const listEl = document.getElementById('cm-list');
            listEl.innerHTML = items.map(function (m) {
                const date = m.created_at ? new Date(m.created_at).toLocaleDateString(undefined, { dateStyle: 'short', timeStyle: 'short' }) : '—';
                const fromName = m.name || (m.user && m.user.name) || '—';
                const fromEmail = m.email || (m.user && m.user.email) || '—';
                const statusBadge = m.status === 'replied'
                    ? '<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">Replied</span>'
                    : '<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">Pending</span>';
                const replyBtn = m.status === 'pending'
                    ? '<button type="button" class="cm-reply-btn rounded-lg border border-brand-500 px-2.5 py-1.5 text-xs font-bold text-brand-600 hover:bg-brand-50 dark:border-brand-400 dark:text-brand-400 dark:hover:bg-brand-500/10" data-id="' + m.id + '" data-message="' + attrEsc(m.message) + '" data-email="' + attrEsc(fromEmail) + '">Reply</button>'
                    : '<span class="text-xs text-gray-400 dark:text-gray-500">Replied</span>';
                return '<li class="px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-800/50">' +
                    '<div class="flex flex-wrap items-start justify-between gap-2">' +
                    '<div class="min-w-0 flex-1">' +
                    '<p class="text-sm font-medium text-gray-900 dark:text-white">' + esc(fromName) + ' &lt;' + esc(fromEmail) + '&gt;</p>' +
                    '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">' + date + '</p>' +
                    '<p class="mt-2 text-sm text-gray-700 dark:text-gray-300 line-clamp-2">' + esc(m.message) + '</p>' +
                    '</div>' +
                    '<div class="flex shrink-0 items-center gap-2">' +
                    statusBadge + ' ' + replyBtn +
                    '</div></div></li>';
            }).join('');

            listWrapEl.classList.remove('hidden');
            var pageInfo = document.getElementById('cm-page-info');
            var prevBtn = document.getElementById('cm-prev');
            var nextBtn = document.getElementById('cm-next');
            if (pageInfo) pageInfo.textContent = 'Page ' + currentPage + ' of ' + lastPage + (total ? ' (' + total + ')' : '');
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= lastPage;

            bindReplyButtons();
            } catch (err) {
                console.error('Contact messages render error:', err);
                emptyEl.classList.remove('hidden');
                var p = emptyEl.querySelector('p');
                if (p) p.textContent = 'Failed to load contact messages.';
            }
        }).catch(function (err) {
            loadingEl.classList.add('hidden');
            emptyEl.classList.remove('hidden');
            var msg = 'Failed to load contact messages.';
            if (err && err.response) {
                if (err.response.status === 401) msg = 'Please sign in again.';
                else if (err.response.data && err.response.data.message) msg = err.response.data.message;
            }
            if (err && err.message) console.error('Contact messages request error:', err.message);
            var p = emptyEl.querySelector('p');
            if (p) p.textContent = msg;
        });
    }

    document.getElementById('cm-filter-status').addEventListener('change', function () {
        state.page = 1;
        load();
    });

    document.getElementById('cm-prev').addEventListener('click', function () {
        if (state.page > 1) { state.page--; load(); }
    });
    document.getElementById('cm-next').addEventListener('click', function () {
        state.page++; load();
    });

    document.getElementById('cm-reply-cancel').addEventListener('click', function () {
        document.getElementById('cm-reply-modal').classList.add('hidden');
        document.getElementById('cm-reply-modal').classList.remove('flex');
    });

    document.getElementById('cm-reply-modal').addEventListener('click', function (e) {
        if (e.target === this) {
            document.getElementById('cm-reply-modal').classList.add('hidden');
            document.getElementById('cm-reply-modal').classList.remove('flex');
        }
    });

    document.getElementById('cm-reply-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('cm-reply-id').value;
        const text = document.getElementById('cm-reply-text').value.trim();
        const errEl = document.getElementById('cm-reply-err');
        const submitBtn = document.getElementById('cm-reply-submit');
        if (!text) {
            errEl.textContent = 'Please enter a reply.';
            errEl.classList.remove('hidden');
            return;
        }
        errEl.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        window.axios.patch(baseUrl + '/' + id + '/reply', { admin_reply: text }, { headers: getAuthHeaders() })
            .then(function () {
                document.getElementById('cm-reply-modal').classList.add('hidden');
                document.getElementById('cm-reply-modal').classList.remove('flex');
                window.location.reload();
            })
            .catch(function (err) {
                const msg = err.response && err.response.data && err.response.data.errors && err.response.data.errors.admin_reply ? err.response.data.errors.admin_reply[0] : (err.response && err.response.data && err.response.data.message) || 'Failed to send reply.';
                errEl.textContent = msg;
                errEl.classList.remove('hidden');
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send reply';
            });
    });

    function start() {
        bindReplyButtons();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
@endpush
