import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
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
                <div className="site-header-main">
                    <Link href={route('home')} className="flex shrink-0 items-center">
                        <img src="/images/vetora-logo-transparent.png" alt="Vetora" className="h-9 w-auto object-contain sm:h-10" />
                    </Link>

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

                    <div className="flex-1 md:hidden" />

                    <div className="flex shrink-0 items-center gap-1">
                        <LanguageSwitcher />
                        <ThemeToggle label={nav.toggle_theme_aria} />

                        <button
                            type="button"
                            onClick={openCart}
                            className="nav-action-btn relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-transparent text-muted-foreground"
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

                        {user && <NotificationsMenu />}

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
                            className="nav-action-btn flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-transparent text-muted-foreground md:hidden"
                            aria-label={nav.menu}
                        >
                            <Menu className="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div className="site-header-category-bar hidden lg:block">
                    <div className="site-header-category-inner">
                        <CategoryMegaMenu categories={categories} />
                        <Link href={route('products.index')} className="nav-primary-link">{nav.products}</Link>
                        <Link href={route('faq')} className="nav-primary-link">{nav.faq}</Link>
                    </div>
                </div>
            </header>

            <MobileDrawer open={mobileOpen} onClose={closeMobileDrawer} categories={categories} />
        </>
    );
}
