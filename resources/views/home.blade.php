@extends('layouts.app')

@section('title', 'SyriaZone — Your Marketplace for Everything')

@section('content')
    <x-home.categories />
    <x-home.subcategories />
    <x-home.promo-banner />
    <x-home.products />
    <x-home.best-selling-products />
    <x-home.most-favorited-products />
    <x-home.trust-badges />
    <x-home.contact />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const $ = id => document.getElementById(id);
    function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.style.opacity = '1'; entry.target.style.transform = 'translateY(0)'; observer.unobserve(entry.target); }
        });
    }, { threshold: 0.08 });

    // ═══ CATEGORIES ═══
    async function loadCategories() {
        try {
            const res = await window.axios.get('/api/categories');
            const cats = res.data.data || [];
            $('cats-loading')?.classList.add('hidden');
            if (!cats.length) return;

            let allSubs = [];
            cats.forEach(c => { (c.subcategories || []).forEach(s => { allSubs.push({ ...s, category_name: c.name }); }); });
            renderSubcategories(allSubs);

            const grid = $('cats-grid');
            if (!grid) return;
            grid.innerHTML = cats.map((cat, i) => {
                const logo = cat.logo ? `/storage/${cat.logo}` : '';
                const subs = cat.subcategories || [];
                return `
                <a href="/categories/${cat.id}" class="cat-card group overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900" style="opacity:0;transform:translateY(20px);transition:opacity .5s ease ${i*.06}s,transform .5s ease ${i*.06}s;">
                    <div class="flex items-center gap-4 p-4 sm:p-5">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 ring-1 ring-brand-200/50 transition-transform duration-300 group-hover:scale-110 sm:h-20 sm:w-20 dark:from-brand-500/10 dark:to-brand-500/5 dark:ring-brand-500/20">
                            ${logo ? `<img src="${esc(logo)}" alt="" class="h-full w-full rounded-2xl object-cover">` : `<svg class="h-8 w-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/></svg>`}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-gray-900 group-hover:text-brand-600 sm:text-base dark:text-white dark:group-hover:text-brand-400">${esc(cat.name)}</h3>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">${subs.length} subcategor${subs.length === 1 ? 'y' : 'ies'}</p>
                        </div>
                        <svg class="h-5 w-5 shrink-0 text-gray-300 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-brand-500 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </div>
                    ${subs.length ? `<div class="border-t border-gray-100 bg-gray-50/50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/30"><div class="flex flex-wrap gap-1.5">${subs.slice(0,4).map(s => `<span class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-medium text-gray-600 ring-1 ring-gray-200/80 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">${s.image?`<img src="/storage/${esc(s.image)}" class="h-3.5 w-3.5 rounded object-cover" alt="">`:''} ${esc(s.name)}</span>`).join('')}${subs.length>4?`<span class="text-[11px] text-gray-400 px-2 py-1.5">+${subs.length-4} more</span>`:''}</div></div>` : ''}
                </a>`;
            }).join('');
            grid.querySelectorAll('.cat-card').forEach(el => observer.observe(el));
        } catch(e) { $('cats-loading') && ($('cats-loading').innerHTML = '<p class="text-sm text-gray-400">Could not load.</p>'); }
    }

    // ═══ SUBCATEGORIES ═══
    function renderSubcategories(subs) {
        $('subs-loading')?.classList.add('hidden');
        const track = $('subs-track');
        if (!track || !subs.length) return;
        track.innerHTML = subs.map(s => `
            <a href="/subcategories/${s.id}" class="group flex w-44 shrink-0 items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-3.5 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:border-brand-200 sm:w-48 dark:border-gray-800 dark:bg-gray-800/50 dark:hover:border-brand-500">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-200/50 dark:bg-gray-700 dark:ring-gray-600">
                    ${s.image ? `<img src="/storage/${esc(s.image)}" class="h-full w-full object-cover" alt="">` : `<svg class="h-6 w-6 text-gray-300 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg>`}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-700 group-hover:text-brand-600 dark:text-gray-300 dark:group-hover:text-brand-400">${esc(s.name)}</p>
                    <p class="truncate text-[10px] text-gray-400 dark:text-gray-500">${esc(s.category_name)}</p>
                </div>
            </a>
        `).join('');
    }

    // ═══ PRODUCTS (last 5) ═══
    async function loadProducts() {
        $('products-loading')?.classList.remove('hidden');
        try {
            const res = await window.axios.get('/api/products?per_page=5');
            const { data } = res.data;
            if (!data.length) { $('products-empty')?.classList.remove('hidden'); }
            else {
                const grid = $('products-grid');
                if (!grid) return;
                function starStars(rating) {
                    const r = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
                    const filled = '<svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                    const empty = '<svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                    let h = ''; for (let i = 0; i < 5; i++) h += i < r ? filled : empty; return h;
                }
                grid.innerHTML = data.map((p, i) => {
                    const photo = p.first_photo_url || '', inStock = p.quantity > 0;
                    const isFav = window._favIds && window._favIds.has(p.id);
                    const revCount = parseInt(p.review_count, 10) || 0;
                    return `
                    <div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900" style="opacity:0;transform:translateY(16px);transition:opacity .4s ease ${i*.05}s,transform .4s ease ${i*.05}s;">
                        <div class="relative">
                            <a href="/products/${p.id}"><div class="relative aspect-[4/5] overflow-hidden bg-gray-50 dark:bg-gray-800">
                                ${photo ? `<img src="${esc(photo)}" alt="${esc(p.name)}" class="h-full w-full object-contain p-4 transition-transform duration-500 hover:scale-105" loading="lazy">` : `<div class="flex h-full items-center justify-center"><svg class="h-12 w-12 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}
                                ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">Sold Out</span></div>' : ''}
                                ${p.has_active_discount ? `<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                            </div></a>
                            <button data-fav-btn="${p.id}" onclick="event.stopPropagation();window.toggleFav(${p.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav?'text-red-500':'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav?'currentColor':'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
                        </div>
                        <div class="p-3 sm:p-4">
                            <a href="/products/${p.id}"><h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(p.name)}</h3></a>
                            <div class="mt-1.5 flex items-center gap-1.5 text-amber-400">${starStars(p.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${revCount ? revCount + (revCount === 1 ? ' review' : ' reviews') : ''}</span></div>
                            <div class="mt-2.5 flex items-baseline gap-1">
                                <span class="text-lg font-black ${p.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${parseFloat(p.has_active_discount ? p.discounted_price : p.price).toLocaleString()}</span><span class="text-[11px] text-gray-400">SYP</span>
                                ${p.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(p.price).toLocaleString()} SYP</span>` : ''}
                            </div>
                            <button onclick="window.addToCart(${p.id},\`${esc(p.name)}\`,${p.price},\`${esc(photo)}\`)" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition-all duration-200 ${inStock ? 'bg-gray-900 text-white hover:bg-brand-600 active:scale-[.97] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'}" ${!inStock?'disabled':''}>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                                ${inStock ? 'Add to Cart' : 'Sold Out'}
                            </button>
                        </div>
                    </div>`;
                }).join('');
                grid.querySelectorAll('.product-card').forEach(el => observer.observe(el));
            }
        } catch(e) { $('products-empty')?.classList.remove('hidden'); }
        $('products-loading')?.classList.add('hidden');
    }

    function starStars(rating) {
        const r = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
        const filled = '<svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        const empty = '<svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        let h = ''; for (let i = 0; i < 5; i++) h += i < r ? filled : empty; return h;
    }

    function renderProductCards(data, gridEl, emptyEl, loadingEl, startOpacity = 0) {
        if (!gridEl) return;
        if (loadingEl) loadingEl.classList.add('hidden');
        if (!Array.isArray(data) || data.length === 0) {
            if (emptyEl) emptyEl.classList.remove('hidden');
            return;
        }
        if (emptyEl) emptyEl.classList.add('hidden');
        gridEl.innerHTML = data.map((p, i) => {
            const photo = p.first_photo_url || '', inStock = p.quantity > 0;
            const isFav = window._favIds && window._favIds.has(p.id);
            const revCount = parseInt(p.review_count, 10) || 0;
            return `
            <div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900" style="opacity:0;transform:translateY(16px);transition:opacity .4s ease ${(startOpacity + i)*.05}s,transform .4s ease ${(startOpacity + i)*.05}s;">
                <div class="relative">
                    <a href="/products/${p.id}"><div class="relative aspect-[4/5] overflow-hidden bg-gray-50 dark:bg-gray-800">
                        ${photo ? `<img src="${esc(photo)}" alt="${esc(p.name)}" class="h-full w-full object-contain p-4 transition-transform duration-500 hover:scale-105" loading="lazy">` : `<div class="flex h-full items-center justify-center"><svg class="h-12 w-12 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}
                        ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">Sold Out</span></div>' : ''}
                        ${p.has_active_discount ? `<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                    </div></a>
                    <button data-fav-btn="${p.id}" onclick="event.stopPropagation();window.toggleFav(${p.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav?'text-red-500':'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav?'currentColor':'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
                </div>
                <div class="p-3 sm:p-4">
                    <a href="/products/${p.id}"><h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(p.name)}</h3></a>
                    <div class="mt-1.5 flex items-center gap-1.5 text-amber-400">${starStars(p.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${revCount ? revCount + (revCount === 1 ? ' review' : ' reviews') : ''}</span></div>
                    <div class="mt-2.5 flex items-baseline gap-1">
                        <span class="text-lg font-black ${p.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${parseFloat(p.has_active_discount ? p.discounted_price : p.price).toLocaleString()}</span><span class="text-[11px] text-gray-400">SYP</span>
                        ${p.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(p.price).toLocaleString()} SYP</span>` : ''}
                    </div>
                    <button onclick="window.addToCart(${p.id},\`${esc(p.name)}\`,${p.price},\`${esc(photo)}\`)" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition-all duration-200 ${inStock ? 'bg-gray-900 text-white hover:bg-brand-600 active:scale-[.97] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'}" ${!inStock?'disabled':''}>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                        ${inStock ? 'Add to Cart' : 'Sold Out'}
                    </button>
                </div>
            </div>`;
        }).join('');
        gridEl.querySelectorAll('.product-card').forEach(el => observer.observe(el));
    }

    async function loadBestSelling() {
        const loadingEl = $('best-selling-loading'), gridEl = $('best-selling-grid'), emptyEl = $('best-selling-empty');
        if (!gridEl) return;
        try {
            const res = await window.axios.get('/api/products?per_page=5&sort=best_selling');
            const data = res.data.data || [];
            renderProductCards(data, gridEl, emptyEl, loadingEl);
        } catch (e) { if (emptyEl) emptyEl.classList.remove('hidden'); }
        if (loadingEl) loadingEl.classList.add('hidden');
    }

    async function loadMostFavorited() {
        const loadingEl = $('most-favorited-loading'), gridEl = $('most-favorited-grid'), emptyEl = $('most-favorited-empty');
        if (!gridEl) return;
        try {
            const res = await window.axios.get('/api/products?per_page=5&sort=most_favorited');
            const data = res.data.data || [];
            renderProductCards(data, gridEl, emptyEl, loadingEl);
        } catch (e) { if (emptyEl) emptyEl.classList.remove('hidden'); }
        if (loadingEl) loadingEl.classList.add('hidden');
    }

    // ═══ CONTACT FORM ═══
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        const user = window.Auth && window.Auth.getUser && window.Auth.getUser();
        if (user) {
            const nameEl = document.getElementById('contact-name');
            const emailEl = document.getElementById('contact-email');
            if (nameEl && !nameEl.value) nameEl.value = user.name || '';
            if (emailEl && !emailEl.value) emailEl.value = user.email || '';
        }
        contactForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = document.getElementById('contact-submit');
            const btnText = submitBtn && submitBtn.querySelector('.contact-btn-text');
            const name = (document.getElementById('contact-name') && document.getElementById('contact-name').value) || '';
            const email = document.getElementById('contact-email') && document.getElementById('contact-email').value;
            const message = document.getElementById('contact-message') && document.getElementById('contact-message').value;
            document.getElementById('contact-success').classList.add('hidden');
            document.getElementById('contact-error').classList.add('hidden');
            ['name', 'email', 'message'].forEach(k => { const el = document.getElementById('contact-err-' + k); if (el) { el.classList.add('hidden'); el.textContent = ''; } });
            if (!email || !message) return;
            submitBtn.disabled = true;
            if (btnText) btnText.textContent = 'Sending...';
            try {
                await window.axios.post('/api/contact', { name: name.trim() || null, email: email.trim(), message: message.trim() });
                document.getElementById('contact-success').classList.remove('hidden');
                document.getElementById('contact-success').classList.add('flex');
                contactForm.reset();
                if (user) {
                    const nameEl = document.getElementById('contact-name');
                    const emailEl = document.getElementById('contact-email');
                    if (nameEl) nameEl.value = user.name || '';
                    if (emailEl) emailEl.value = user.email || '';
                }
            } catch (err) {
                if (err.response && err.response.status === 422 && err.response.data.errors) {
                    const errors = err.response.data.errors;
                    Object.keys(errors).forEach(key => {
                        const el = document.getElementById('contact-err-' + key);
                        if (el) { el.textContent = errors[key][0]; el.classList.remove('hidden'); }
                    });
                } else {
                    document.getElementById('contact-error').classList.remove('hidden');
                    document.getElementById('contact-error').classList.add('flex');
                    const msgEl = document.getElementById('contact-error-msg');
                    if (msgEl) msgEl.textContent = err.response?.data?.message || 'Something went wrong. Please try again.';
                }
            } finally {
                submitBtn.disabled = false;
                if (btnText) btnText.textContent = 'Send Message';
            }
        });
    }

    // ═══ INIT ═══
    await Promise.all([loadCategories(), loadProducts(), loadBestSelling(), loadMostFavorited()]);
});
</script>
@endpush
