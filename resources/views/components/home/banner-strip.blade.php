{{-- Homepage promotional banners (spec §38/§40+), fed by GET /api/banners. Self-contained: own ids, own script, no shared state with home.blade.php's script block. --}}
<section id="home-banner-strip" class="page-shell pt-2 sm:pt-3 hidden">
    <div id="home-banner-track" class="flex gap-3 overflow-x-auto pb-1" style="scroll-snap-type: x mandatory;"></div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const section = document.getElementById('home-banner-strip');
    const track = document.getElementById('home-banner-track');
    if (!section || !track) {
        return;
    }

    const locale = document.documentElement.lang === 'ar' ? 'ar' : 'en';

    function esc(value) {
        if (!value) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    try {
        const response = await window.axios.get('/api/banners');
        const banners = response.data.data || [];
        if (!banners.length) {
            return;
        }

        track.innerHTML = banners.map((banner) => {
            const title = locale === 'ar' ? (banner.title_ar || banner.title_en) : (banner.title_en || banner.title_ar);
            const image = `<img src="/storage/${esc(banner.image_path)}" alt="${esc(title || '')}" class="h-40 w-full object-cover sm:h-52" style="border-radius: var(--radius-card);" loading="lazy">`;
            const inner = banner.link_url
                ? `<a href="${esc(banner.link_url)}" class="block shrink-0" style="scroll-snap-align: start; width: min(90vw, 32rem);">${image}</a>`
                : `<div class="shrink-0" style="scroll-snap-align: start; width: min(90vw, 32rem);">${image}</div>`;
            return inner;
        }).join('');

        section.classList.remove('hidden');
    } catch (error) {
        // No banners configured, or the request failed — leave the strip hidden rather than showing an error.
    }
});
</script>
@endpush
