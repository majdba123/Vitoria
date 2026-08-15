import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { LayoutGrid, ChevronDown, ChevronRight, Layers, ArrowRight } from 'lucide-react';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { useFocusTrap } from '@/hooks/use-focus-trap';

function categoryImageUrl(category) {
    if (category.image_url) return category.image_url;
    if (category.logo) return `/storage/${category.logo}`;
    if (category.icon) return `/storage/${category.icon}`;
    return null;
}

function subLabel(sub, locale) {
    return locale === 'ar' ? (sub.name_ar || sub.name_en || '') : (sub.name_en || sub.name_ar || '');
}

export function CategoryMegaMenu({ categories }) {
    const { nav } = useI18n();
    const [open, setOpen] = useState(false);
    const [activeId, setActiveId] = useState(null);
    const ref = useRef(null);
    const triggerRef = useRef(null);
    const panelRef = useRef(null);
    const wasOpen = useRef(false);
    const locale = useLocale();

    useEffect(() => {
        if (categories.length && activeId === null) setActiveId(categories[0].id);
    }, [categories, activeId]);

    useEffect(() => {
        if (!open) return;
        const onClick = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('click', onClick);
        return () => document.removeEventListener('click', onClick);
    }, [open]);

    useFocusTrap(panelRef, open, () => setOpen(false));

    useEffect(() => {
        if (open) {
            wasOpen.current = true;
        } else if (wasOpen.current) {
            wasOpen.current = false;
            triggerRef.current?.focus();
        }
    }, [open]);

    const active = categories.find((c) => c.id === activeId);
    const subs = active?.subcategories ?? [];

    return (
        <div className="relative shrink-0" ref={ref}>
            <button
                type="button"
                ref={triggerRef}
                onClick={() => setOpen((v) => !v)}
                className="nav-primary-link font-semibold"
                aria-haspopup="true"
                aria-expanded={open}
            >
                <LayoutGrid className="h-4 w-4" />
                {nav.categories}
                <ChevronDown className={`h-3.5 w-3.5 text-muted-foreground transition-transform duration-200 ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div ref={panelRef} className="dropdown-panel absolute top-full z-50 mt-2 w-[780px] ltr:left-0 rtl:right-0">
                    <div className="flex" style={{ minHeight: 340 }}>
                        <div className="w-64 shrink-0 overflow-y-auto border-e border-border/80 bg-muted/40 py-2">
                            {categories.length === 0 && <p className="px-5 py-8 text-center text-xs text-muted-foreground">{nav.loading_categories}</p>}
                            {categories.map((c) => (
                                <button
                                    key={c.id}
                                    type="button"
                                    onClick={() => setActiveId(c.id)}
                                    className={`group flex w-full items-center gap-3 rounded-md px-4 py-2.5 text-start text-sm transition-colors ${c.id === activeId ? 'bg-card text-primary' : 'hover:bg-card'}`}
                                >
                                    <div className="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-background ring-1 ring-border/60">
                                        {categoryImageUrl(c) ? <img src={categoryImageUrl(c)} alt="" className="size-full object-cover" /> : <Layers className="size-4 text-muted-foreground" />}
                                    </div>
                                    <span className="flex-1 truncate font-medium text-foreground group-hover:text-primary">{c.name}</span>
                                    <ChevronRight className="size-3.5 shrink-0 text-muted-foreground/50 rtl:-scale-x-100" />
                                </button>
                            ))}
                        </div>
                        <div className="flex-1 overflow-y-auto p-5">
                            {!active ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">{nav.select_category_prompt}</p>
                            ) : (
                                <>
                                    <div className="mb-5 flex items-center justify-between gap-3">
                                        <div>
                                            <h3 className="text-base font-bold text-foreground">{active.name}</h3>
                                            <p className="mt-1 text-xs text-muted-foreground">{(nav.subcategories_count ?? '').replace(':count', String(subs.length))}</p>
                                        </div>
                                        <Link href={`/products?category_id=${active.id}`} onClick={() => setOpen(false)} className="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                            {nav.all_category_products}
                                            <ArrowRight className="size-3.5 rtl:-scale-x-100" />
                                        </Link>
                                    </div>
                                    {subs.length === 0 ? (
                                        <div className="rounded-md border border-dashed border-border px-4 py-6 text-sm text-muted-foreground">{nav.no_subcategories}</div>
                                    ) : (
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {subs.map((sub) => (
                                                <Link
                                                    key={sub.id}
                                                    href={`/products?category_id=${active.id}&subcategory_id=${sub.id}`}
                                                    onClick={() => setOpen(false)}
                                                    className="group flex items-center justify-between gap-3 rounded-md border border-border bg-card px-4 py-3 text-sm font-medium text-foreground transition hover:border-primary/40 hover:bg-accent/40 hover:text-primary"
                                                >
                                                    <span className="truncate">{subLabel(sub, locale)}</span>
                                                    <ArrowRight className="size-4 shrink-0 text-muted-foreground/50 transition group-hover:text-primary rtl:-scale-x-100" />
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
