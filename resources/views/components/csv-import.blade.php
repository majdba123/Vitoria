@props([
    'id',
    'label',
    'templateUrl',
    'importUrl',
])

<div class="flex flex-wrap gap-2">
    <a href="{{ $templateUrl }}" class="btn-secondary btn-sm">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3"/></svg>
        Download Template
    </a>
    <button type="button" id="{{ $id }}-import-trigger" class="btn-secondary btn-sm">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Import CSV
    </button>
    <input type="file" id="{{ $id }}-import-input" accept=".csv,text/csv" class="hidden">
</div>

<div id="{{ $id }}-import-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
        <h3 class="text-base font-semibold text-gray-900">Import {{ $label }} from CSV</h3>
        <p class="mt-1 text-sm text-gray-500">Selected file: <span id="{{ $id }}-import-filename" class="font-medium text-gray-700"></span></p>

        <div id="{{ $id }}-import-idle" class="mt-4 text-sm text-gray-500">
            Choose a CSV file (built from the downloaded template), then click "Start Import".
        </div>

        <div id="{{ $id }}-import-loading" class="mt-4 hidden py-6 text-center">
            <div class="mx-auto h-6 w-6 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
            <p class="mt-2 text-sm text-gray-500">Processing file...</p>
        </div>

        <div id="{{ $id }}-import-result" class="mt-4 hidden">
            <p class="text-sm text-gray-700">
                <span id="{{ $id }}-import-created" class="font-semibold text-emerald-600"></span> created,
                <span id="{{ $id }}-import-failed" class="font-semibold text-rose-600"></span> failed
                out of <span id="{{ $id }}-import-total" class="font-semibold"></span> rows.
            </p>
            <ul id="{{ $id }}-import-errors" class="mt-2 max-h-56 list-disc space-y-1 overflow-y-auto pl-5 text-xs text-rose-700"></ul>
        </div>

        <div class="mt-3">
            <x-alert type="error" id="{{ $id }}-import-alert" />
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" id="{{ $id }}-import-close" class="btn-secondary btn-sm">Close</button>
            <button type="button" id="{{ $id }}-import-start" class="btn-primary btn-sm">Start Import</button>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        window.initCsvImport = function (id, importUrl) {
            const trigger = document.getElementById(id + '-import-trigger');
            const input = document.getElementById(id + '-import-input');
            const modal = document.getElementById(id + '-import-modal');
            const filenameEl = document.getElementById(id + '-import-filename');
            const idle = document.getElementById(id + '-import-idle');
            const loading = document.getElementById(id + '-import-loading');
            const resultBox = document.getElementById(id + '-import-result');
            const createdEl = document.getElementById(id + '-import-created');
            const failedEl = document.getElementById(id + '-import-failed');
            const totalEl = document.getElementById(id + '-import-total');
            const errorsEl = document.getElementById(id + '-import-errors');
            const closeBtn = document.getElementById(id + '-import-close');
            const startBtn = document.getElementById(id + '-import-start');
            const alertBox = document.getElementById(id + '-import-alert');
            const alertMessage = document.getElementById(id + '-import-alert-message');

            if (!trigger) {
                return;
            }

            function esc(value) {
                const element = document.createElement('div');
                element.textContent = value || '';
                return element.innerHTML;
            }

            function resetModal() {
                input.value = '';
                filenameEl.textContent = '';
                idle.classList.remove('hidden');
                loading.classList.add('hidden');
                resultBox.classList.add('hidden');
                alertBox.classList.add('hidden');
                startBtn.disabled = false;
            }

            trigger.addEventListener('click', function () {
                resetModal();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                input.click();
            });

            input.addEventListener('change', function () {
                filenameEl.textContent = input.files[0] ? input.files[0].name : '';
            });

            closeBtn.addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            startBtn.addEventListener('click', async function () {
                if (!input.files[0]) {
                    alertMessage.textContent = 'Please choose a CSV file first.';
                    alertBox.classList.remove('hidden');
                    return;
                }

                const formData = new FormData();
                formData.append('file', input.files[0]);

                idle.classList.add('hidden');
                resultBox.classList.add('hidden');
                alertBox.classList.add('hidden');
                loading.classList.remove('hidden');
                startBtn.disabled = true;

                try {
                    const response = await window.axios.post(importUrl, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                    });

                    const data = response.data.data || {};
                    loading.classList.add('hidden');
                    resultBox.classList.remove('hidden');
                    createdEl.textContent = data.created ?? 0;
                    failedEl.textContent = data.failed ?? 0;
                    totalEl.textContent = data.total_rows ?? 0;
                    errorsEl.innerHTML = (data.errors || []).slice(0, 50).map(function (item) {
                        const messages = Object.values(item.errors || {}).flat().join(' ');
                        return '<li>Row ' + item.row + ': ' + esc(messages) + '</li>';
                    }).join('');

                    window.dispatchEvent(new CustomEvent('csv-import:done', { detail: { id: id } }));
                } catch (error) {
                    loading.classList.add('hidden');
                    alertMessage.textContent = (error.response && error.response.data && error.response.data.message) || 'Import failed. Please check the file and try again.';
                    alertBox.classList.remove('hidden');
                } finally {
                    startBtn.disabled = false;
                }
            });
        };
    </script>
    @endpush
@endonce

<script>
document.addEventListener('DOMContentLoaded', function () {
    window.initCsvImport(@js($id), @js($importUrl));
});
</script>
