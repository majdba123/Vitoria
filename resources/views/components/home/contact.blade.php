<section id="contact" class="scroll-mt-20 py-10 sm:py-14">
    <div class="workspace-shell">
        <div class="surface-card overflow-hidden">
            <div class="grid gap-0 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <div class="border-b border-gray-200/70 bg-gray-50/70 px-6 py-8 dark:border-gray-800 dark:bg-gray-900/40 lg:border-b-0 lg:border-e lg:px-8">
                    <span class="eyebrow">{{ __('home.contact') }}</span>
                    <h2 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">{{ __('home.contact_title') }}</h2>
                    <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">{{ __('home.contact_subtitle') }}</p>
                </div>

                <form id="contact-form" class="space-y-5 px-6 py-8 sm:px-8">
                    <div>
                        <label for="contact-name" class="form-label">{{ __('home.contact_name_label') }}</label>
                        <input type="text" id="contact-name" name="name" maxlength="255" placeholder="{{ __('home.contact_name_placeholder') }}" class="form-input">
                        <p id="contact-err-name" class="form-error"></p>
                    </div>
                    <div>
                        <label for="contact-email" class="form-label">{{ __('home.contact_email_label') }} <span class="text-red-500">*</span></label>
                        <input type="email" id="contact-email" name="email" required maxlength="255" placeholder="{{ __('home.contact_email_placeholder') }}" class="form-input">
                        <p id="contact-err-email" class="form-error"></p>
                    </div>
                    <div>
                        <label for="contact-message" class="form-label">{{ __('home.contact_message_label') }} <span class="text-red-500">*</span></label>
                        <textarea id="contact-message" name="message" required rows="5" maxlength="5000" placeholder="{{ __('home.contact_message_placeholder') }}" class="form-textarea"></textarea>
                        <p id="contact-err-message" class="form-error"></p>
                    </div>
                    <div id="contact-success" class="alert-shell hidden border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ __('home.contact_success') }}</span>
                    </div>
                    <div id="contact-error" class="alert-shell hidden border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        <span id="contact-error-msg">{{ __('home.contact_error_default') }}</span>
                    </div>
                    <button type="submit" id="contact-submit" class="btn-primary w-full justify-center">
                        <span class="contact-btn-text">{{ __('home.contact_send') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
