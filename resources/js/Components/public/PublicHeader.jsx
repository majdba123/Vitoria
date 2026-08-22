import { useEffect, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Search, ShoppingBag, Menu } from 'lucide-react';
import { LanguageSwitcher } from '@/Components/workspace/LanguageSwitcher';
import { ThemeToggle } from '@/Components/workspace/ThemeToggle';
import { NotificationsMenu } from '@/Components/public/NotificationsMenu';
import { ProfileMenu } from '@/Components/public/ProfileMenu';
import { CategoryMegaMenu } from '@/Components/public/CategoryMegaMenu';
import { MobileDrawer } from '@/Components/public/MobileDrawer';
import { useCart } from '@/hooks/use-cart';
import { useAuthUser, useI18n } from '@/hooks/use-i18n';

export function PublicHeader() {
    const { nav } = useI18n();
    const user = useAuthUser();
    const { url } = usePage();
    const { itemsCount, openCart } = useCart();
    const [search, setSearch] = useState('');
    const [categories, setCategories] = useState([]);
    const [mobileOpen, setMobileOpen] = useState(false);
    const mobileTriggerRef = useRef(null);

    const closeMobileDrawer = () => {
        setMobileOpen(false);
        mobileTriggerRef.current?.focus();
    };

    useEffect(() => {
        window.axios.get('/api/categories', { params: { per_page: 100 }, silent: true }).then((res) => {
            setCategories(res.data?.data ?? []);
        }).catch(() => {});
    }, []);

    const submitSearch = (event) => {
        event.preventDefault();
        const q = search.trim();
        window.location.href = route('products.index') + (q ? `?search=${encodeURIComponent(q)}` : '');
    };

    return (
        <>
            <header className="site-header">
                <nav className="site-header-main" aria-label={nav.primary_navigation ?? nav.menu}>
                    <Link href={route('home')} className="flex shrink-0 items-center">
                        <img src="/images/vetora-logo-transparent.png" alt="Vetora" className="h-9 w-auto object-contain sm:h-10" />
                    </Link>

                    <div className="hidden shrink-0 items-center gap-1 lg:flex">
                        <CategoryMegaMenu categories={categories} />
                        <Link href={route('products.index')} aria-current={url.startsWith('/products') ? 'page' : undefined} className="nav-primary-link min-h-11">{nav.products}</Link>
                        <Link href={route('faq')} aria-current={url.startsWith('/faq') ? 'page' : undefined} className="nav-primary-link min-h-11">{nav.faq}</Link>
                        <Link href={route('contact')} aria-current={url.startsWith('/contact') ? 'page' : undefined} className="nav-primary-link min-h-11">{nav.contact_us}</Link>
                    </div>

                    <form onSubmit={submitSearch} className="site-header-search" role="search">
                        <Search className="pointer-events-none" />
                        <input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={nav.search_products}
                            aria-label={nav.search_products}
                        />
                    </form>

                    <div className="flex-1 lg:hidden" />

                    <div className="flex shrink-0 items-center gap-1">
                        <div className="hidden items-center gap-1 md:flex">
                            <LanguageSwitcher />
                            <ThemeToggle label={nav.toggle_theme_aria} />
                        </div>

                        <button
                            type="button"
                            onClick={openCart}
                            className="nav-action-btn relative flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-transparent text-muted-foreground"
                            aria-label={nav.cart}
                            title={nav.cart}
                        >
                            <ShoppingBag className="h-5 w-5" />
                            {itemsCount > 0 && (
                                <span className="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-none text-primary-foreground shadow rtl:-left-0.5 rtl:right-auto">
                                    {itemsCount > 99 ? '99+' : itemsCount}
                                </span>
                            )}
                        </button>

                        {user && <div className="hidden md:block"><NotificationsMenu /></div>}

                        {!user && (
                            <div className="hidden items-center gap-2 sm:flex">
                                <Link href={route('login')} className="nav-primary-link">{nav.sign_in}</Link>
                                <Link href={route('register')} className="btn-primary btn-sm">{nav.register}</Link>
                            </div>
                        )}

                        {user && <ProfileMenu />}

                        <button
                            type="button"
                            ref={mobileTriggerRef}
                            onClick={() => setMobileOpen(true)}
                            className="nav-action-btn flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-transparent text-muted-foreground lg:hidden"
                            aria-label={nav.menu}
                            aria-expanded={mobileOpen}
                            aria-controls="public-mobile-drawer"
                        >
                            <Menu className="h-5 w-5" />
                        </button>
                    </div>
                </nav>
            </header>

            <MobileDrawer open={mobileOpen} onClose={closeMobileDrawer} categories={categories} />
        </>
    );
}
