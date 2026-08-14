import { Head, usePage } from '@inertiajs/react';

/**
 * One place for every page-level SEO/AEO tag: title (via Inertia's own title
 * callback in app.jsx/ssr.jsx), meta description, canonical link, robots
 * directive, Open Graph + Twitter Card tags, and optional JSON-LD structured
 * data. Reads `origin` + `url` from shared Inertia props so canonical/OG URLs
 * are correct on both the server-rendered and client-rendered pass.
 */
export function SeoHead({ title, description, canonical, noindex = false, image, type = 'website', jsonLd }) {
    const { props, url } = usePage();
    const origin = props.origin ?? '';
    const canonicalUrl = canonical ?? `${origin}${url.split('?')[0]}`;
    const structuredData = jsonLd ? (Array.isArray(jsonLd) ? jsonLd : [jsonLd]) : [];

    return (
        <Head title={title}>
            {description && <meta name="description" content={description} />}
            <link rel="canonical" href={canonicalUrl} />
            <meta name="robots" content={noindex ? 'noindex, nofollow' : 'index, follow'} />

            <meta property="og:type" content={type} />
            <meta property="og:site_name" content="Vetora" />
            <meta property="og:url" content={canonicalUrl} />
            {title && <meta property="og:title" content={title} />}
            {description && <meta property="og:description" content={description} />}
            {image && <meta property="og:image" content={image} />}
            <meta property="og:locale" content={props.locale === 'ar' ? 'ar_SY' : 'en_US'} />

            <meta name="twitter:card" content={image ? 'summary_large_image' : 'summary'} />
            {title && <meta name="twitter:title" content={title} />}
            {description && <meta name="twitter:description" content={description} />}
            {image && <meta name="twitter:image" content={image} />}

            {structuredData.map((schema, index) => (
                <script key={index} type="application/ld+json">{JSON.stringify(schema)}</script>
            ))}
        </Head>
    );
}
