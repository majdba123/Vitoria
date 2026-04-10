@extends('layouts.admin')

@section('title', 'Send Notification — SyriaZone Admin')
@section('page-title', 'Send Notification')

@section('content')
<div class="mx-auto max-w-2xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Dashboard</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <a href="{{ route('admin.notifications.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">سجل الإشعارات</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900 dark:text-gray-100">Send Notification</span>
    </nav>

    <div class="card">
        <div class="card-body border-b border-gray-100 dark:border-gray-800">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Send notification</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Send a public notification to all users or a private notification to selected users. Delivered in real time via WebSocket.</p>
        </div>

        <div class="card-body">
            <x-alert type="error" id="send-alert" />
            <x-alert type="success" id="send-success" />

            <form id="send-notification-form" class="mt-1 space-y-6" novalidate>
                <div>
                    <label for="title" class="form-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" class="form-input" placeholder="Notification title" required maxlength="255" />
                    <p class="form-error" id="title-error"></p>
                </div>

                <div>
                    <label for="body" class="form-label">Message <span class="text-red-500">*</span></label>
                    <textarea id="body" name="body" class="form-input min-h-[120px]" placeholder="Notification message" required maxlength="10000" rows="4"></textarea>
                    <p class="form-error" id="body-error"></p>
                </div>

                <div>
                    <label for="type" class="form-label">Type <span class="text-red-500">*</span></label>
                    <select id="type" name="type" class="form-select">
                        <option value="public">Public — All users</option>
                        <option value="private">Private — Selected users only</option>
                    </select>
                    <p class="form-error" id="type-error"></p>
                </div>

                <div id="recipients-wrap" class="hidden">
                    <label for="user_ids" class="form-label">Recipients <span class="text-red-500">*</span></label>
                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Select one or more users. They will receive the notification in real time.</p>
                    <select id="user_ids" name="user_ids[]" class="form-input" multiple size="8">
                        <option value="">Loading users…</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hold Ctrl/Cmd to select multiple.</p>
                    <p class="form-error" id="user_ids-error"></p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 dark:border-gray-800 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" id="send-btn" class="btn-primary">
                        <span id="send-btn-text">Send notification</span>
                        <svg id="send-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('send-notification-form');
    const typeSelect = document.getElementById('type');
    const recipientsWrap = document.getElementById('recipients-wrap');
    const userIdsSelect = document.getElementById('user_ids');

    function toggleRecipients() {
        const isPrivate = typeSelect.value === 'private';
        recipientsWrap.classList.toggle('hidden', !isPrivate);
        if (isPrivate && userIdsSelect.options.length === 1 && userIdsSelect.options[0].value === '') {
            loadUsers();
        }
    }

    typeSelect.addEventListener('change', toggleRecipients);

    async function loadUsers() {
        userIdsSelect.innerHTML = '<option value="">Loading…</option>';
        try {
            const res = await window.axios.get('/api/admin/users', { params: { per_page: 500 } });
            const users = res.data.data || [];
            userIdsSelect.innerHTML = users.map(u => `<option value="${u.id}">${u.name} (${u.phone_number || u.email || '—'})</option>`).join('') || '<option value="">No users found</option>';
        } catch (e) {
            userIdsSelect.innerHTML = '<option value="">Failed to load users</option>';
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);

        const payload = {
            title: form.title.value.trim(),
            body: form.body.value.trim(),
            type: form.type.value,
        };
        if (payload.type === 'private') {
            payload.user_ids = Array.from(userIdsSelect.selectedOptions).map(o => parseInt(o.value, 10)).filter(Boolean);
        }

        try {
            await window.axios.post('/api/admin/notifications/send', payload);
            showAlert('send-success', 'Notification sent successfully. It will be delivered in real time to recipients.');
            form.title.value = '';
            form.body.value = '';
            userIdsSelect.querySelectorAll('option:checked').forEach(o => { o.selected = false; });
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(loading) {
        document.getElementById('send-btn').disabled = loading;
        document.getElementById('send-spinner').classList.toggle('hidden', !loading);
        document.getElementById('send-btn-text').textContent = loading ? 'Sending…' : 'Send notification';
    }

    function clearErrors() {
        document.getElementById('send-alert').classList.add('hidden');
        document.getElementById('send-success').classList.add('hidden');
        document.querySelectorAll('[id$="-error"]').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        document.getElementById(id + '-message').textContent = message;
        box.classList.remove('hidden');
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            for (const [field, messages] of Object.entries(errors)) {
                const msg = Array.isArray(messages) ? messages[0] : messages;
                const normalField = field.replace(/\.\d+$/, '');
                const el = document.getElementById(normalField + '-error');
                if (el) { el.textContent = msg; el.classList.remove('hidden'); }
            }
        } else {
            showAlert('send-alert', error.response?.data?.message || 'An unexpected error occurred.');
        }
    }

    toggleRecipients();
});
</script>
@endpush
