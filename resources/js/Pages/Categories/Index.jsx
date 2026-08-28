import { useEffect, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight, Layers, ArrowRight } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { DataState } from '@/Components/public/DataState';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n } from '@/hooks/use-i18n';
import { categoryImageUrl } from '@/lib/category-image';

export default function CategoriesIndex() {
    const { nav, categories: categoriesI18n, home } = useI18n();
    const { url } = usePage();
    const params = new URLSearchParams(url.split('?')[1] ?? '');
    const [type, setType] = useState(params.get('type') ?? '');
    const [status, setStatus] = useState('loading');
    const [rows, setRows] = useState([]);
    const requestSeq = useRef(0);

    const load = (t) => {
        const seq = ++requestSeq.current;
        setStatus('loading');
        window.axios.get('/api/categories', { params: { per_page: 100, type: t || undefined }, silent: true }).then((res) => {
            if (seq !== requestSeq.current) return;
            setRows(res.data?.data ?? []);
            setStatus('ready');

            const nextUrl = new URL(window.location.href);
            if (t) nextUrl.searchParams.set('type', t); else nextUrl.searchParams.delete('type');
            window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);
        }).catch(() => {
            if (seq !== requestSeq.current) return;
            setStatus('error');
        });
    };

    useEffect(() => { load(type); }, []);

    const changeType = (t) => {
        setType(t);
        load(t);
    };

    const typedHref = (path) => (type ? `${path}?type=${encodeURIComponent(type)}` : path);

    const categoryListJsonLd = status === 'ready' && rows.length > 0 ? {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        itemListElement: rows.map((category, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            url: route('categories.show', category.id),
            name: category.name,
        })),
    } : null;

    return (
        <PublicLayout title={categoriesI18n.page_title} description={categoriesI18n.page_subtitle} jsonLd={categoryListJsonLd ? [categoryListJsonLd] : undefined}>
            <div className="catalog-page-band">
                <div className="page-shell py-3">
                    <nav className="page-breadcrumb" aria-label={categoriesI18n.page_heading}>
                        <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                        <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                        <span className="page-breadcrumb-current">{categoriesI18n.page_heading}</span>
                    </nav>
                </div>
            </div>

            <div className="page-shell">
                <div className="commerce-section-header">
                    <div>
                        <h1 className="commerce-title">{categoriesI18n.page_heading}</h1>
                    </div>
                    <div className="flex flex-wrap gap-2" aria-label={nav.all_types}>
                        {[
                            { value: 'agriculture', label: home.type_agriculture_short },
                            { value: 'veterinary', label: home.type_veterinary_short },
                        ].map((opt) => (
                            <button
                                key={opt.value}
                                type="button"
                                onClick={() => changeType(opt.value)}
                                aria-pressed={type === opt.value}
                                className={type === opt.value ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'}
                            >
                                {opt.label}
                            </button>
                        ))}
                    </div>
                </div>

                <DataState
                    status={status}
                    onRetry={() => load(type)}
                    errorMessage={<p className="text-sm text-muted-foreground">{categoriesI18n.load_error}</p>}
                    loadingSkeleton={(
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-40 rounded-xl" />)}
                        </div>
                    )}
                >
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {rows.map((category) => (
                            <article key={category.id} className="category-directory-card">
                                <div className="category-directory-media">
                                    {categoryImageUrl(category) ? (
                                        <img src={categoryImageUrl(category)} alt={category.name} loading="lazy" />
                                    ) : (
                                        <div className="flex h-full items-center justify-center text-primary">
                                            <Layers className="h-9 w-9" />
                                        </div>
                                    )}
                                </div>
                                <Link href={typedHref(`/categories/${category.id}`)} className="flex min-w-0 items-center gap-4 p-5">
                                    <div className="min-w-0 flex-1">
                                        <h2 className="text-lg font-bold text-foreground">{category.name}</h2>
                                    </div>
                                    <ArrowRight className="h-5 w-5 shrink-0 text-muted-foreground rtl:-scale-x-100" />
                                </Link>
                            </article>
                        ))}
                    </div>
                </DataState>
            </div>
        </PublicLayout>
    );
}
