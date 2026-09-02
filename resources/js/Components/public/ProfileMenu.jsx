import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronDown, User, LayoutGrid, LogOut } from 'lucide-react';
import { useAuthUser, useI18n } from '@/hooks/use-i18n';
import { useFocusTrap } from '@/hooks/use-focus-trap';

const ROLE_KEY = { 0: 'customer', 1: 'admin', 2: 'business_account', 3: 'syndicate', 4: 'employee' };
const DASHBOARD_ROUTE = { 1: 'admin.dashboard', 2: 'vendor.dashboard', 3: 'syndicate.dashboard', 4: 'employee.dashboard' };

export function ProfileMenu() {
    const { nav, common } = useI18n();
    const user = useAuthUser();
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const onClick = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('click', onClick);
        return () => document.removeEventListener('click', onClick);
    }, [open]);

    useFocusTrap(ref, open, () => setOpen(false));

    if (!user) return null;

    const initial = (user.name || nav.customer || '?').charAt(0).toUpperCase();
    const roleLabel = nav[ROLE_KEY[user.type]] ?? nav.customer;
    const dashboardRoute = DASHBOARD_ROUTE[user.type];

    const handleLogout = async () => {
        setOpen(false);
        await window.Auth.logout(route('login'));
    };

    return (
        <div className="relative" ref={ref}>
            <button type="button" onClick={() => setOpen((v) => !v)} aria-label={`${user.name} — ${roleLabel}`} aria-haspopup="menu" aria-expanded={open} className="flex items-center gap-2.5 rounded-lg border border-transparent px-2 py-1.5 hover:bg-accent/50">
                <div className="flex size-8 items-center justify-center overflow-hidden rounded-full bg-primary">
                    {user.avatar_url ? <img src={user.avatar_url} alt="" className="size-full object-cover" /> : <span className="text-sm font-bold text-primary-foreground">{initial}</span>}
                </div>
                <div className="hidden max-w-40 min-w-0 text-start xl:block">
                    <p className="text-sm font-semibold leading-tight text-foreground">{user.name}</p>
                    <p className="text-[10px] font-medium text-muted-foreground">{roleLabel}</p>
                </div>
                <ChevronDown className={`h-3.5 w-3.5 text-muted-foreground transition-transform duration-200 ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div className="dropdown-panel absolute top-full z-50 mt-2 w-72 ltr:right-0 rtl:left-0">
                    <div className="border-b border-border/80 bg-muted/50 px-5 py-4">
                        <div className="flex items-center gap-3">
                            <div className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary">
                                {user.avatar_url ? <img src={user.avatar_url} alt="" className="size-full object-cover" /> : <span className="text-base font-bold text-primary-foreground">{initial}</span>}
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-bold text-foreground">{user.name}</p>
                                <p className="truncate text-xs text-muted-foreground">{user.email}</p>
                            </div>
                        </div>
                    </div>
                    <div className="py-2">
                        <Link href={route('profile')} onClick={() => setOpen(false)} className="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent/50">
                            <User className="size-4 text-muted-foreground" />
                            {nav.my_profile}
                        </Link>
                        {dashboardRoute && (
                            <a href={route(dashboardRoute)} className="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent/50">
                                <LayoutGrid className="size-4 text-muted-foreground" />
                                {nav.dashboard}
                            </a>
                        )}
                    </div>
                    <div className="border-t border-border/80 py-2">
                        <button type="button" onClick={handleLogout} className="flex w-full items-center gap-3 px-5 py-2.5 text-sm font-medium text-[var(--color-danger-strong)] transition-colors hover:bg-[var(--color-danger-soft)]">
                            <LogOut className="size-4" />
                            {nav.sign_out}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
