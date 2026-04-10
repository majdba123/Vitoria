@props(['color' => 'brand', 'alertId' => 'create-alert'])

<script>
(function() {
    const photoInput = document.getElementById('photo-input');
    const preview = document.getElementById('photo-preview');
    const dropZone = document.getElementById('drop-zone');
    const countEl = document.getElementById('photo-count');
    let selectedFiles = [];

    if (!photoInput || !preview || !dropZone) return;

    // Click to browse
    dropZone.addEventListener('click', () => photoInput.click());

    // Drag & drop
    const hoverBorder = '{{ $color }}' === 'brand' ? 'border-brand-400' : 'border-emerald-400';
    const hoverBg = '{{ $color }}' === 'brand' ? 'bg-brand-50/40' : 'bg-emerald-50/40';
    ['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, ev => {
        ev.preventDefault();
        dropZone.classList.add(hoverBorder, hoverBg);
    }));
    ['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, ev => {
        ev.preventDefault();
        dropZone.classList.remove(hoverBorder, hoverBg);
    }));
    dropZone.addEventListener('drop', ev => {
        const files = Array.from(ev.dataTransfer.files).filter(f => f.type.startsWith('image/'));
        addFiles(files);
    });

    photoInput.addEventListener('change', function () {
        addFiles(Array.from(this.files));
        this.value = '';
    });

    function addFiles(files) {
        if (selectedFiles.length + files.length > 10) {
            showAlert('{{ $alertId }}', 'Maximum 10 photos allowed.');
            return;
        }
        selectedFiles = selectedFiles.concat(files);
        renderPreviews();
    }

    function renderPreviews() {
        if (selectedFiles.length === 0) {
            preview.innerHTML = '';
            countEl.classList.add('hidden');
            return;
        }
        countEl.textContent = selectedFiles.length + ' of 10 photos selected';
        countEl.classList.remove('hidden');

        preview.innerHTML = selectedFiles.map((f, i) => {
            const url = URL.createObjectURL(f);
            f._objectURL = url; // Store URL for cleanup
            const sizeMB = (f.size / 1048576).toFixed(1);
            return `<div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                <div class="aspect-square overflow-hidden">
                    <img src="${url}" class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105" alt="">
                </div>
                <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/0 transition-all group-hover:bg-black/50 group-hover:opacity-100">
                    <button type="button" onclick="window.viewPreviewLarge('${url}')" title="View Large" class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white opacity-0 shadow-lg transition-all hover:bg-blue-600 group-hover:opacity-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                    </button>
                    <button type="button" onclick="window.removePreview(${i})" title="Remove" class="flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white opacity-0 shadow-lg transition-all hover:bg-red-600 group-hover:opacity-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    </button>
                </div>
                <div class="px-2.5 py-2">
                    <p class="truncate text-xs font-medium text-gray-700">${esc(f.name)}</p>
                    <p class="text-[10px] text-gray-400">${sizeMB} MB</p>
                </div>
            </div>`;
        }).join('');
    }

    window.removePreview = function (index) {
        const i = parseInt(index);
        if (i >= 0 && i < selectedFiles.length && selectedFiles[i]) {
            // Revoke object URL to free memory
            if (selectedFiles[i]._objectURL) {
                URL.revokeObjectURL(selectedFiles[i]._objectURL);
            }
            selectedFiles.splice(i, 1);
            renderPreviews();
        }
    };

    window.viewPreviewLarge = function (url) {
        // Remove existing modal if any
        const existingModal = document.getElementById('photo-preview-modal');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.id = 'photo-preview-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4';
        modal.innerHTML = `
            <div class="relative max-h-[90vh] max-w-[90vw]">
                <img src="${url}" class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain shadow-2xl" alt="Product photo">
                <button type="button" onclick="document.getElementById('photo-preview-modal')?.remove()" class="absolute right-2 top-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-gray-900 shadow-lg backdrop-blur-sm transition-all hover:bg-white hover:scale-110" title="Close">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        `;
        const closeModal = () => modal.remove();
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        // Close on Escape key
        const escapeHandler = (e) => { if (e.key === 'Escape') { closeModal(); document.removeEventListener('keydown', escapeHandler); } };
        document.addEventListener('keydown', escapeHandler);
        document.body.appendChild(modal);
    };

    // Expose selectedFiles for form submission
    window.getSelectedPhotos = function() {
        return selectedFiles;
    };

    function esc(t) {
        if (!t) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    function showAlert(id, msg) {
        const el = document.getElementById(id);
        if (el) {
            const msgEl = document.getElementById(id + '-message');
            if (msgEl) msgEl.textContent = msg;
            el.classList.remove('hidden');
        }
    }
})();
</script>

