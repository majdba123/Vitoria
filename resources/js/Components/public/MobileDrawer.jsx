import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { X, LayoutGrid, Layers, ChevronRight, User, LogOut, HelpCircle } from 'lucide-react';
import { useAuthUser, useI18n, useLocale } from '@/hooks/use-i18n';
import { useFocusTrap } from '@/hooks/use-focus-trap';

const DASHBOARD_ROUTE = { 1: 'admin.dashboard', 2: 'vendor.dashboard', 3: 'syndicate.dashboard', 4: 'employee.dashboard' };

function subLabel(sub, locale) {
    return locale === 'ar' ? (sub.name_ar || sub.name_en || '') : (sub.name_en || sub.name_ar || '');
}

export function MobileDrawer({ open, onClose, categories }) {
    const { nav, common } = useI18n();
    const user = useAuthUser();
    const [expandedId, setExpandedId] = useState(null);
    const locale = useLocale();
    const panelRef = useRef(null);

    useEffect(() => {
        document.body.style.overflow = open ? 'hidden' : '';
        return () => { document.body.style.overflow = ''; };
    }, [open]);

    useFocusTrap(panelRef, open, onClose);

    if (!open) return null;

    const handleLogout = () => {
        window.axios.post('/api/auth/logout').catch(() => {}).finally(() => {
            window.Auth?.clearAll?.();
            window.location.href = route('login');
        });
    };

    return (
        <div className="fixed inset-0 z-[60]" role="dialog" aria-modal="true">
            <div className="absolute inset-0 bg-black/45" onClick={onClose} />
            <div ref={panelRef} className="absolute top-0 flex h-full w-80 max-w-[88vw] flex-col border-border bg-card ltr:right-0 ltr:border-l rtl:left-0 rtl:border-r">
                <div className="flex items-center justify-between border-b border-border/80 px-5 py-4">
                    <span className="text-lg font-extrabold text-foreground">{nav.menu}</span>
                    <button type="button" onClick={onClose} className="rounded-md p-2 text-muted-foreground hover:bg-accent" aria-label={nav.close_menu}>
                        <X className="size-5" />
                    </button>
                </div>

                {user && (
                    <div className="border-b border-border/80 px-5 py-4">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary">
                                {user.avatar_url ? <img src={user.avatar_url} alt="" className="size-full object-cover" /> : <span className="text-sm font-bold text-primary-foreground">{(user.name || '?').charAt(0).toUpperCase()}</span>}
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-bold text-foreground">{user.name}</p>
                                <p className="truncate text-xs text-muted-foreground">{user.email}</p>
                            </div>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <Link href={route('profile')} onClick={onClose} className="flex-1 rounded-md border border-border py-2 text-center text-xs font-bold text-foreground hover:bg-accent/50">{common.profile}</Link>
                            {DASHBOARD_ROUTE[user.type] && (
                                <a href={route(DASHBOARD_ROUTE[user.type])} className="flex-1 rounded-md border border-border py-2 text-center text-xs font-bold text-foreground hover:bg-accent/50">{nav.dashboard}</a>
                            )}
                        </div>
                    </div>
                )}

                <div className="flex-1 overflow-y-auto px-5 py-4">
                    <div className="space-y-1">
                        <Link href={route('products.index')} onClick={onClose} className="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-semibold text-foreground transition-colors hover:bg-accent/50">
                            <LayoutGrid className="size-5 text-muted-foreground" />
                            {nav.products}
                        </Link>
                        <Link href={route('categories.index')} onClick={onClose} className="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-semibold text-foreground transition-colors hover:bg-accent/50">
                            <Layers className="size-5 text-muted-foreground" />
                            {nav.categories}
                        </Link>
                        <Link href={route('faq')} onClick={onClose} className="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-semibold text-foreground transition-colors hover:bg-accent/50">
                            <HelpCircle className="size-5 text-muted-foreground" />
                            {nav.faq}
                        </Link>
                    </div>

                    <div className="mt-4 border-t border-border pt-4">
                        <div className="mb-3 flex items-center justify-between gap-3">
                            <p className="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{nav.categories}</p>
                            <span className="text-[11px] text-muted-foreground">{nav.tap_category_prompt}</span>
                        </div>
                        <div className="space-y-2">
                            {categories.map((c) => {
                                const isOpen = expandedId === c.id;
                                const subs = c.subcategories ?? [];
                                return (
                                    <div key={c.id} className="rounded-md border border-border/80 bg-background/70 p-2">
                                        <button
                                            type="button"
                                            onClick={() => setExpandedId(isOpen ? null : c.id)}
                                            className={`flex w-full items-center gap-3 rounded-md px-2 py-2.5 text-start text-sm font-semibold transition-colors ${isOpen ? 'bg-accent/50 text-primary' : 'text-foreground hover:bg-accent/50'}`}
                                        >
                                            <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted"><Layers className="size-3.5 text-muted-foreground" /></span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate">{c.name}</span>
                                                <span className="mt-0.5 block text-[11px] text-muted-foreground">{(nav.subcategories_count ?? '').replace(':count', String(subs.length))}</span>
                                            </span>
                                            <ChevronRight className={`size-4 shrink-0 text-muted-foreground transition-transform duration-200 rtl:-scale-x-100 ${isOpen ? 'rotate-90' : ''}`} />
                                        </button>
                                        {isOpen && (
                                            <div className="px-2 pb-2 pt-2">
                                                <Link href={`/products?category_id=${c.id}`} onClick={onClose} className="mb-2 flex items-center justify-between rounded-md border border-primary/30 bg-accent/40 px-3 py-2.5 text-sm font-bold text-primary">
                                                    {nav.all_category_products}
                                                    <ChevronRight className="size-4 rtl:-scale-x-100" />
                                                </Link>
                                                {subs.length > 0 ? (
                                                    <div className="space-y-1.5">
                                                        {subs.map((sub) => (
                                                            <Link key={sub.id} href={`/products?category_id=${c.id}&subcategory_id=${sub.id}`} onClick={onClose} className="flex items-center justify-between rounded-md px-3 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-accent/40 hover:text-primary">
                                                                <span className="truncate">{subLabel(sub, locale)}</span>
                                                                <ChevronRight className="size-3.5 shrink-0 text-muted-foreground/50 rtl:-scale-x-100" />
                                                            </Link>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <div className="rounded-md border border-dashed border-border px-3 py-3 text-xs text-muted-foreground">{nav.no_subcategories}</div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {!user ? (
                    <div className="border-t border-border/80 px-5 py-4">
                        <Link href={route('login')} onClick={onClose} className="mb-2 block w-full rounded-md border border-border py-2.5 text-center text-sm font-semibold text-foreground transition-colors hover:bg-accent/50">{nav.sign_in}</Link>
                        <Link href={route('register')} onClick={onClose} className="block w-full rounded-md bg-primary py-2.5 text-center text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">{nav.register}</Link>
                    </div>
                ) : (
                    <div className="border-t border-border/80 px-5 py-4">
                        <button type="button" onClick={() => { handleLogout(); onClose(); }} className="flex w-full items-center justify-center gap-2 rounded-md border border-[var(--color-danger-200)] py-2.5 text-sm font-bold text-[var(--color-danger-strong)] transition-colors hover:bg-[var(--color-danger-soft)]">
                            <LogOut className="size-4" />
                            {nav.sign_out}
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
