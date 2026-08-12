@props(['color' => 'brand', 'alertId' => 'create-alert'])

<script>
(function () {
    const photoInput = document.getElementById('photo-input');
    const preview = document.getElementById('photo-preview');
    const dropZone = document.getElementById('drop-zone');
    const countEl = document.getElementById('photo-count');
    let selectedFiles = [];

    if (!photoInput || !preview || !dropZone || !countEl) {
        return;
    }

    const hoverBorder = '{{ $color }}' === 'brand' ? 'border-brand-400' : 'border-emerald-400';
    const hoverBg = '{{ $color }}' === 'brand' ? 'bg-brand-50/40' : 'bg-emerald-50/40';

    dropZone.addEventListener('click', () => photoInput.click());

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropZone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropZone.classList.add(hoverBorder, hoverBg);
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropZone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropZone.classList.remove(hoverBorder, hoverBg);
        });
    });

    dropZone.addEventListener('drop', (event) => {
        const files = Array.from(event.dataTransfer.files || []).filter((file) => file.type.startsWith('image/'));
        addFiles(files);
    });

    photoInput.addEventListener('change', function () {
        addFiles(Array.from(this.files || []));
        this.value = '';
    });

    function addFiles(files) {
        if (selectedFiles.length + files.length > 10) {
            showAlert('{{ $alertId }}', 'Maximum 10 photos allowed.');
            return;
        }

        const nextStartOrder = selectedFiles.length + 1;

        files.forEach((file, index) => {
            file._imageType = file._imageType || (selectedFiles.length === 0 && index === 0 ? 'primary' : 'back');
            file._sortOrder = file._sortOrder || (nextStartOrder + index);
        });

        selectedFiles = selectedFiles.concat(files);
        renderPreviews();
    }

    function renderPreviews() {
        if (selectedFiles.length === 0) {
            preview.innerHTML = '';
            countEl.classList.add('hidden');
            return;
        }

        countEl.textContent = `${selectedFiles.length} of 10 photos selected`;
        countEl.classList.remove('hidden');

        preview.innerHTML = selectedFiles.map((file, index) => {
            if (!file._objectURL) {
                file._objectURL = URL.createObjectURL(file);
            }

            const sizeMB = (file.size / 1048576).toFixed(1);
            const currentType = file._imageType || 'front';
            const typeLabel = currentType === 'primary' ? 'Primary' : currentType === 'front' ? 'Front' : 'Back';

            return `
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                    <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                        <img src="${file._objectURL}" class="h-full w-full object-cover" alt="">
                        <div class="absolute left-3 top-3 inline-flex items-center rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-semibold text-gray-700 shadow-sm">
                            ${typeLabel} • #${file._sortOrder || index + 1}
                        </div>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-800">${escapeHtml(file.name)}</p>
                                <p class="text-xs text-gray-400">${sizeMB} MB</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" data-photo-view="${index}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.6-5.4a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </button>
                                <button type="button" data-photo-remove="${index}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Photo Type</label>
                                <select data-photo-type-index="${index}" class="form-select text-sm">
                                    <option value="primary" ${currentType === 'primary' ? 'selected' : ''}>Primary</option>
                                    <option value="front" ${currentType === 'front' ? 'selected' : ''}>Front</option>
                                    <option value="back" ${currentType === 'back' ? 'selected' : ''}>Back</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Sort Order</label>
                                <input type="number" min="1" value="${file._sortOrder || index + 1}" data-photo-sort-index="${index}" class="form-input text-sm">
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        preview.querySelectorAll('[data-photo-type-index]').forEach((select) => {
            select.addEventListener('change', function () {
                const index = Number.parseInt(this.getAttribute('data-photo-type-index') || '', 10);
                if (!Number.isNaN(index) && selectedFiles[index]) {
                    selectedFiles[index]._imageType = this.value;
                    renderPreviews();
                }
            });
        });

        preview.querySelectorAll('[data-photo-sort-index]').forEach((input) => {
            input.addEventListener('input', function () {
                const index = Number.parseInt(this.getAttribute('data-photo-sort-index') || '', 10);
                const nextValue = Number.parseInt(this.value || '', 10);

                if (!Number.isNaN(index) && selectedFiles[index]) {
                    selectedFiles[index]._sortOrder = Number.isNaN(nextValue) || nextValue < 1 ? index + 1 : nextValue;
                }
            });
        });

        preview.querySelectorAll('[data-photo-remove]').forEach((button) => {
            button.addEventListener('click', function () {
                removePreview(Number.parseInt(this.getAttribute('data-photo-remove') || '', 10));
            });
        });

        preview.querySelectorAll('[data-photo-view]').forEach((button) => {
            button.addEventListener('click', function () {
                const index = Number.parseInt(this.getAttribute('data-photo-view') || '', 10);
                if (!Number.isNaN(index) && selectedFiles[index]?._objectURL) {
                    viewPreviewLarge(selectedFiles[index]._objectURL);
                }
            });
        });
    }

    function removePreview(index) {
        if (index < 0 || index >= selectedFiles.length || !selectedFiles[index]) {
            return;
        }

        if (selectedFiles[index]._objectURL) {
            URL.revokeObjectURL(selectedFiles[index]._objectURL);
        }

        selectedFiles.splice(index, 1);

        selectedFiles.forEach((file, fileIndex) => {
            if (!file._sortOrder || file._sortOrder < 1) {
                file._sortOrder = fileIndex + 1;
            }
        });

        renderPreviews();
    }

    function viewPreviewLarge(url) {
        const existingModal = document.getElementById('photo-preview-modal');
        if (existingModal) {
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'photo-preview-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm';
        modal.innerHTML = `
            <div class="relative max-h-[90vh] max-w-[90vw]">
                <img src="${url}" class="max-h-[90vh] max-w-[90vw] object-contain" style="border-radius: var(--radius-feature); box-shadow: var(--shadow-3);" alt="Product photo">
                <button type="button" data-preview-close class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-gray-900 transition-colors hover:bg-gray-100" style="position: absolute; top: 0.5rem; inset-inline-end: 0.5rem;" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        `;

        modal.addEventListener('click', (event) => {
            if (event.target === modal || event.target.closest('[data-preview-close]')) {
                modal.remove();
            }
        });

        document.body.appendChild(modal);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    function showAlert(id, message) {
        const el = document.getElementById(id);
        if (!el) {
            return;
        }

        const messageEl = document.getElementById(`${id}-message`);
        if (messageEl) {
            messageEl.textContent = message;
        }

        el.classList.remove('hidden');
    }

    window.getSelectedPhotos = function () {
        return selectedFiles;
    };

    window.getSelectedPhotoPayload = function () {
        return selectedFiles.map((file, index) => ({
            file,
            image_type: file._imageType || 'front',
            sort_order: Number.parseInt(`${file._sortOrder || index + 1}`, 10) || index + 1,
        }));
    };
})();
</script>
