@extends('layouts.admin')

@section('title', 'تعديل وكيل نقابة - Vetora')
@section('page-title', 'تعديل وكيل نقابة')

@section('content')
<div class="mx-auto max-w-3xl">
    <div id="form-alert" class="mb-4 hidden rounded-xl border px-4 py-3 text-sm font-semibold"></div>
    <form id="syndicate-form" class="card card-body space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">الاسم</label>
                <input name="name" class="form-input" required>
                <p id="name-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">البريد الإلكتروني</label>
                <input name="email" type="email" class="form-input" required>
                <p id="email-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">الهاتف</label>
                <input name="phone" class="form-input" required>
                <p id="phone-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">النوع</label>
                <select name="type" class="form-input" required>
                    <option value="agriculture">زراعي</option>
                    <option value="veterinary">بيطري</option>
                </select>
                <p id="type-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">كلمة مرور جديدة</label>
                <input name="password" type="password" class="form-input" autocomplete="new-password">
                <p id="password-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                <input name="password_confirmation" type="password" class="form-input" autocomplete="new-password">
                <p id="password_confirmation-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">الحالة</label>
                <select name="status" class="form-input" required>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
                <p id="status-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">الشعار</label>
                <input name="logo" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input">
                <div id="logo-preview" class="mt-2 hidden items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/60">
                    <img id="logo-preview-img" src="" alt="" class="h-12 w-12 rounded-lg object-cover">
                    <p class="text-xs font-semibold text-gray-500">الشعار الحالي</p>
                </div>
                <p id="logo-error" class="form-error"></p>
            </div>
        </div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.syndicates.index') }}" class="btn-secondary">إلغاء</a>
            <button id="submit-btn" class="btn-primary">حفظ التعديلات</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const syndicateId = @json($syndicateId);
const form = document.getElementById('syndicate-form');
loadSyndicate();

async function loadSyndicate() {
    try {
        const res = await window.axios.get('/api/admin/syndicates/' + syndicateId, { silent: true });
        const s = res.data.data;
        form.name.value = s.name || '';
        form.email.value = s.email || '';
        form.phone.value = s.phone || '';
        form.type.value = s.type || 'agriculture';
        form.status.value = s.status || 'active';
        if (s.logo_url) {
            document.getElementById('logo-preview-img').src = s.logo_url;
            document.getElementById('logo-preview').classList.remove('hidden');
            document.getElementById('logo-preview').classList.add('flex');
        }
    } catch (error) {
        const parsed = window.showApiError ? window.showApiError(error) : window.ApiErrors.parse(error);
        showAlert(parsed.generalMessage, 'error');
    }
}

form.addEventListener('submit', async function (event) {
    event.preventDefault();
    clearErrors();
    const button = document.getElementById('submit-btn');
    button.disabled = true;
    button.textContent = 'جاري الحفظ...';
    const formData = new FormData(form);
    formData.append('_method', 'PUT');
    if (!form.password.value) {
        formData.delete('password');
        formData.delete('password_confirmation');
    }

    try {
        await window.axios.post('/api/admin/syndicates/' + syndicateId, formData, { silent: true });
        window.location.href = '/admin/syndicates/' + syndicateId;
    } catch (error) {
        const parsed = window.showApiError ? window.showApiError(error) : window.ApiErrors.parse(error);
        window.ApiErrors.showFieldErrors(parsed.fieldErrors);
        showAlert(parsed.generalMessage, 'error');
    } finally {
        button.disabled = false;
        button.textContent = 'حفظ التعديلات';
    }
});

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
    document.getElementById('form-alert').classList.add('hidden');
}

function showAlert(message, type) {
    const box = document.getElementById('form-alert');
    box.textContent = message;
    box.className = type === 'error'
        ? 'mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700'
        : 'mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700';
}
</script>
@endpush
