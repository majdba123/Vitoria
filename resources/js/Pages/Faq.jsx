import { Link } from '@inertiajs/react';
import { ArrowRight, ChevronRight } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { useI18n } from '@/hooks/use-i18n';

export default function Faq() {
    const { home, nav } = useI18n();
    const items = home.faq ?? [];

    const faqJsonLd = items.length > 0 ? {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: items.map((item) => ({
            '@type': 'Question',
            name: item.q,
            acceptedAnswer: { '@type': 'Answer', text: item.a },
        })),
    } : null;

    return (
        <PublicLayout title={home.faq_title} description={home.faq_subtitle} jsonLd={faqJsonLd ? [faqJsonLd] : undefined}>
            <div className="bg-transparent">
                <div className="catalog-page-band">
                    <div className="page-shell py-3">
                        <nav className="page-breadcrumb">
                            <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <span className="page-breadcrumb-current">{home.faq_title}</span>
                        </nav>
                    </div>
                </div>

                <div className="page-shell storefront-section" aria-labelledby="faq-heading">
                    <div className="mx-auto max-w-3xl text-center">
                        <p className="commerce-kicker">{home.faq_kicker}</p>
                        <h1 id="faq-heading" className="commerce-title mt-2">{home.faq_title}</h1>
                        <p className="commerce-copy mt-2">{home.faq_subtitle}</p>
                    </div>

                    {items.length > 0 ? (
                        <div className="mx-auto mt-8 max-w-3xl divide-y divide-border border-y border-border">
                            {items.map((item, index) => (
                                <details key={index} id={`faq-${index + 1}`} className="group py-4">
                                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-start text-sm font-bold text-foreground marker:content-none focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                        {item.q}
                                        <ArrowRight className="h-4 w-4 shrink-0 rotate-90 text-muted-foreground transition-transform rtl:-rotate-90 group-open:rotate-[270deg]" />
                                    </summary>
                                    <p className="mt-3 text-sm leading-7 text-muted-foreground">{item.a}</p>
                                </details>
                            ))}
                        </div>
                    ) : (
                        <p className="mt-8 text-center text-sm text-muted-foreground">{home.faq_subtitle}</p>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
