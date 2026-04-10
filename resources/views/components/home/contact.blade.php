{{-- ═══ Contact Us ═══ --}}
<section id="contact" class="scroll-mt-20 border-t border-gray-200 bg-white py-14 dark:border-gray-800 dark:bg-gray-950 sm:py-16">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <div class="text-center">
                <h2 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white sm:text-3xl">Contact Us</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Send us a message and we’ll get back to you as soon as we can.</p>
            </div>

            <form id="contact-form" class="mt-10 space-y-5 rounded-2xl border border-gray-200/80 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-gray-900/50 sm:p-8">
                <div>
                    <label for="contact-name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Your name (optional)</label>
                    <input type="text" id="contact-name" name="name" maxlength="255" placeholder="Your name"
                        class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500">
                    <p id="contact-err-name" class="mt-1 hidden text-xs text-red-500"></p>
                </div>
                <div>
                    <label for="contact-email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="contact-email" name="email" required maxlength="255" placeholder="you@example.com"
                        class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500">
                    <p id="contact-err-email" class="mt-1 hidden text-xs text-red-500"></p>
                </div>
                <div>
                    <label for="contact-message" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Message <span class="text-red-500">*</span></label>
                    <textarea id="contact-message" name="message" required rows="4" maxlength="5000" placeholder="Your message..."
                        class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500"></textarea>
                    <p id="contact-err-message" class="mt-1 hidden text-xs text-red-500"></p>
                </div>
                <div id="contact-success" class="hidden items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Your message has been sent. We’ll get back to you soon.</span>
                </div>
                <div id="contact-error" class="hidden items-center gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <span id="contact-error-msg">Something went wrong. Please try again.</span>
                </div>
                <button type="submit" id="contact-submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gray-900 py-3.5 text-sm font-bold text-white transition-all hover:bg-brand-600 active:scale-[.97] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">
                    <span class="contact-btn-text">Send Message</span>
                </button>
            </form>
        </div>
    </div>
</section>
