import { Link, usePage } from '@inertiajs/react';
import { Home } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Separator } from '@/Components/ui/separator';
import { SidebarTrigger } from '@/Components/ui/sidebar';
import { NotificationBell } from '@/Components/workspace/NotificationBell';
import { ThemeToggle } from '@/Components/workspace/ThemeToggle';
import { LanguageSwitcher } from '@/Components/workspace/LanguageSwitcher';
import { useI18n } from '@/hooks/use-i18n';

export function VendorHeader({ title }) {
    const { vendor, nav } = useI18n();
    const { props } = usePage();

    return (
        <header className="dashboard-topbar sticky top-0 z-30 flex h-16 shrink-0 items-center gap-2 border-b bg-background/95 backdrop-blur-sm">
            <div className="dashboard-topbar-inner flex w-full items-center gap-2 px-3 sm:gap-3 sm:px-4 lg:px-6">
                <SidebarTrigger className="-ms-1 size-11" />
                <Separator orientation="vertical" className="h-5" />

                <div className="min-w-0 flex-1">
                    <p className="dashboard-eyebrow text-[11px] font-extrabold uppercase tracking-[0.16em] text-primary">{vendor.badge}</p>
                    <h1 className="truncate text-base font-bold text-foreground sm:text-lg">{title}</h1>
                </div>

                <div className="flex items-center gap-0.5">
                    <Button asChild variant="ghost" size="icon" className="size-11" aria-label={nav.home ?? 'Home'} title={nav.home ?? 'Home'}>
                        <Link href={route('home')}>
                            <Home className="size-4" />
                        </Link>
                    </Button>
                    <LanguageSwitcher />
                    <NotificationBell viewAllRoute="vendor.notifications.index" locale={props.locale} group="vendor" />
                    <ThemeToggle label={vendor.toggle_theme ?? 'Toggle theme'} />
                </div>
            </div>
        </header>
    );
}
