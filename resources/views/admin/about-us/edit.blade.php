@extends('layouts.admin')

@section('title', __('admin.about_us') . ' (Footer) — ' . __('SyriaZone'))
@section('page-title', __('admin.about_us'))

@section('content')
<div class="space-y-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.about_edit_desc') }}</p>

    <div class="card">
        <div class="card-body">
            <form id="footer-settings-form" class="space-y-5" data-saving="{{ __('common.saving') }}" data-save-btn="{{ __('admin.about_save_btn') }}" data-failed="{{ __('common.failed_save') }}">
                <div>
                    <label for="about_description" class="form-label">{{ __('admin.about_description') }}</label>
                    <textarea id="about_description" name="about_description" rows="4" maxlength="2000" class="form-input" placeholder="Short description for the footer...">{{ old('about_description', $about_description ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.about_description_hint') }}</p>
                    <p id="err-about_description" class="mt-1 hidden text-xs text-red-500"></p>
                </div>

                <div class="border-t border-gray-200 pt-5 dark:border-gray-700">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('admin.about_social') }}</h3>
                </div>
                <div>
                    <label for="facebook_url" class="form-label">{{ __('admin.about_facebook') }}</label>
                    <input type="url" id="facebook_url" name="facebook_url" class="form-input" placeholder="https://facebook.com/..." value="{{ old('facebook_url', $facebook_url ?? '') }}">
                    <p id="err-facebook_url" class="mt-1 hidden text-xs text-red-500"></p>
                </div>

                <div>
                    <label for="instagram_url" class="form-label">{{ __('admin.about_instagram') }}</label>
                    <input type="url" id="instagram_url" name="instagram_url" class="form-input" placeholder="https://instagram.com/..." value="{{ old('instagram_url', $instagram_url ?? '') }}">
                    <p id="err-instagram_url" class="mt-1 hidden text-xs text-red-500"></p>
                </div>

                <div>
                    <label for="twitter_url" class="form-label">{{ __('admin.about_twitter') }}</label>
                    <input type="url" id="twitter_url" name="twitter_url" class="form-input" placeholder="https://twitter.com/..." value="{{ old('twitter_url', $twitter_url ?? '') }}">
                    <p id="err-twitter_url" class="mt-1 hidden text-xs text-red-500"></p>
                </div>

                <div class="border-t border-gray-200 pt-5 dark:border-gray-700">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('admin.about_contact') }}</h3>
                    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.about_contact_hint') }}</p>
                </div>
                <div>
                    <label for="contact_email" class="form-label">{{ __('admin.about_contact_email') }}</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-input" placeholder="support@syriazone.com" value="{{ old('contact_email', $contact_email ?? '') }}">
                    <p id="err-contact_email" class="mt-1 hidden text-xs text-red-500"></p>
                </div>
                <div>
                    <label for="contact_address" class="form-label">{{ __('admin.about_contact_address') }}</label>
                    <input type="text" id="contact_address" name="contact_address" class="form-input" placeholder="e.g. Damascus, Syria" value="{{ old('contact_address', $contact_address ?? '') }}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.about_max_500') }}</p>
                    <p id="err-contact_address" class="mt-1 hidden text-xs text-red-500"></p>
                </div>

                <div id="footer-settings-success" class="hidden items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ __('admin.about_saved') }}</span>
                </div>
                <div id="footer-settings-error" class="hidden items-center gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <span id="footer-settings-error-msg">{{ __('admin.about_error') }}</span>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" id="footer-settings-submit" class="btn-primary btn-sm">{{ __('admin.about_save_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('footer-settings-form');
    var submitBtn = document.getElementById('footer-settings-submit');
    var successEl = document.getElementById('footer-settings-success');
    var errorEl = document.getElementById('footer-settings-error');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        successEl.classList.add('hidden');
        errorEl.classList.add('hidden');
        document.querySelectorAll('[id^="err-"]').forEach(function (el) { el.classList.add('hidden'); el.textContent = ''; });

        var payload = {
            about_description: document.getElementById('about_description').value.trim() || null,
            facebook_url: document.getElementById('facebook_url').value.trim() || null,
            instagram_url: document.getElementById('instagram_url').value.trim() || null,
            twitter_url: document.getElementById('twitter_url').value.trim() || null,
            contact_email: document.getElementById('contact_email').value.trim() || null,
            contact_address: document.getElementById('contact_address').value.trim() || null
        };

        submitBtn.disabled = true;
        var savingText = form.getAttribute('data-saving') || 'Saving...';
        var saveBtnText = form.getAttribute('data-save-btn') || 'Save changes';
        submitBtn.textContent = savingText;

        var headers = {};
        if (window.Auth && window.Auth.getToken) {
            if (window.Auth.applyToken) window.Auth.applyToken();
            var token = window.Auth.getToken();
            if (token) headers['Authorization'] = 'Bearer ' + token;
        }

        window.axios.put('/api/admin/footer-settings', payload, { headers: headers })
            .then(function () {
                successEl.classList.remove('hidden');
                successEl.classList.add('flex');
                setTimeout(function () { successEl.classList.add('hidden'); successEl.classList.remove('flex'); }, 4000);
            })
            .catch(function (err) {
                if (err.response && err.response.status === 422 && err.response.data.errors) {
                    var errors = err.response.data.errors;
                    Object.keys(errors).forEach(function (key) {
                        var el = document.getElementById('err-' + key);
                        if (el) { el.textContent = errors[key][0]; el.classList.remove('hidden'); }
                    });
                } else {
                    errorEl.classList.remove('hidden');
                    errorEl.classList.add('flex');
                    var msgEl = document.getElementById('footer-settings-error-msg');
                    if (msgEl) msgEl.textContent = err.response && err.response.data && err.response.data.message ? err.response.data.message : 'Failed to save.';
                }
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = saveBtnText;
            });
    });
});
</script>
@endpush
