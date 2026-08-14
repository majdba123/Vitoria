import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { useI18n, useLocale } from '@/hooks/use-i18n';

export default function PageShow({ page }) {
    const { nav, common } = useI18n();
    const locale = useLocale();

    const title = page ? (locale === 'ar' ? page.title_ar : page.title_en) : '';
    const body = page ? (locale === 'ar' ? page.content_ar : page.content_en) : '';
    // meta_title/meta_description are the admin-authored SEO fields (see Admin/Pages/Index.jsx);
    // fall back to the display title/truncated body only when an editor hasn't set them.
    const metaTitle = page?.meta_title || title;
    const metaDescription = page ? (page.meta_description || (body || '').replace(/\s+/g, ' ').trim().slice(0, 160)) : undefined;
    const updatedAt = page?.updated_at ? new Date(String(page.updated_at).replace(' ', 'T')) : null;
    const updatedLabel = updatedAt && !Number.isNaN(updatedAt.getTime()) ? updatedAt.toLocaleDateString(locale, { dateStyle: 'long' }) : null;

    const webPageJsonLd = page ? {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: title,
        description: metaDescription,
        datePublished: page.created_at || undefined,
        dateModified: page.updated_at || undefined,
    } : null;

    return (
        <PublicLayout title={metaTitle || 'Vetora'} description={metaDescription} noindex={!page} jsonLd={webPageJsonLd ? [webPageJsonLd] : undefined}>
            <div className="page-shell py-10">
                <nav className="page-breadcrumb mb-6">
                    <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                    <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                    <span className="page-breadcrumb-current">{title}</span>
                </nav>

                {!page && (
                    <div className="py-16 text-center text-sm text-muted-foreground">{common.not_found}</div>
                )}

                {page && (
                    <article className="max-w-3xl">
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">{title}</h1>
                        {updatedLabel && <p className="mt-2 text-xs font-medium text-muted-foreground">{common.last_updated}: {updatedLabel}</p>}
                        <div className="prose mt-6 max-w-none whitespace-pre-wrap text-muted-foreground">{body}</div>
                    </article>
                )}
            </div>
        </PublicLayout>
    );
}
