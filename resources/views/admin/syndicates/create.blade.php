@extends('layouts.admin')

@section('title', 'إضافة وكيل نقابة - Vetora')
@section('page-title', 'إضافة وكيل نقابة')

@section('content')
<div class="mx-auto max-w-3xl">
    <div id="form-alert" class="mb-4 hidden rounded-xl border px-4 py-3 text-sm font-semibold"></div>
    <form id="syndicate-form" enctype="multipart/form-data" class="card card-body space-y-5">
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
                <label class="form-label">كلمة المرور</label>
                <input name="password" type="password" class="form-input" required autocomplete="new-password">
                <p id="password-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">تأكيد كلمة المرور</label>
                <input name="password_confirmation" type="password" class="form-input" required autocomplete="new-password">
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
                <label class="form-label">الشعار <span class="text-xs font-normal text-gray-400">(اختياري)</span></label>
                <input name="logo" type="file" accept="image/*" class="form-input">
                <p class="mt-1 text-xs text-gray-500">JPG أو PNG أو WebP بحد أقصى 2MB.</p>
                <p id="logo-error" class="form-error"></p>
            </div>
        </div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.syndicates.index') }}" class="btn-secondary">إلغاء</a>
            <button id="submit-btn" class="btn-primary">إنشاء وكيل النقابة</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('syndicate-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    const form = event.target;
    const button = document.getElementById('submit-btn');
    clearErrors();
    button.disabled = true;
    button.textContent = 'جاري الحفظ...';

    try {
        const res = await window.axios.post('/api/admin/syndicates', syndicateFormData(form), { silent: true });
        window.location.href = '/admin/syndicates/' + res.data.data.id;
    } catch (error) {
        const parsed = window.showApiError ? window.showApiError(error) : window.ApiErrors.parse(error);
        window.ApiErrors.showFieldErrors(parsed.fieldErrors);
        showAlert(parsed.generalMessage, 'error');
    } finally {
        button.disabled = false;
        button.textContent = 'إنشاء وكيل النقابة';
    }
});

function syndicateFormData(form) {
    const formData = new FormData(form);
    const logo = form.querySelector('input[name="logo"]');
    if (!logo || !logo.files || logo.files.length === 0) {
        formData.delete('logo');
    }

    return formData;
}

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
