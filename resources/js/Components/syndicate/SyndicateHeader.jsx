import { Link, usePage } from '@inertiajs/react';
import { Home } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Separator } from '@/Components/ui/separator';
import { SidebarTrigger } from '@/Components/ui/sidebar';
import { ThemeToggle } from '@/Components/workspace/ThemeToggle';
import { LanguageSwitcher } from '@/Components/workspace/LanguageSwitcher';
import { NotificationBell } from '@/Components/workspace/NotificationBell';
import { LogoutButton } from '@/Components/workspace/LogoutButton';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { useI18n, useAuthUser } from '@/hooks/use-i18n';

const TYPE_LABEL_KEY = { agriculture: 'type_agriculture', veterinary: 'type_veterinary' };

export function SyndicateHeader({ title }) {
    const { syndicate, nav } = useI18n();
    const user = useAuthUser();
    const { props } = usePage();
    const typeKey = TYPE_LABEL_KEY[user?.syndicate?.type] ?? 'type_default';

    return (
        <header className="dashboard-topbar sticky top-0 z-30 flex h-16 shrink-0 items-center gap-2 border-b bg-background/95 backdrop-blur-sm">
            <div className="dashboard-topbar-inner flex w-full items-center gap-2 px-3 sm:gap-3 sm:px-4 lg:px-6">
                <SidebarTrigger className="-ms-1 size-11" />
                <Separator orientation="vertical" className="h-5" />

                <div className="min-w-0 flex-1">
                    <p className="dashboard-eyebrow text-[11px] font-extrabold uppercase tracking-[0.16em] text-primary">{syndicate.workspace_label}</p>
                    <h1 className="truncate text-base font-bold text-foreground sm:text-lg">{title}</h1>
                </div>

                <div className="flex items-center gap-1.5">
                    <span className="hidden lg:inline-flex"><StatusBadge tone="brand">{syndicate[typeKey]}</StatusBadge></span>
                    <Button asChild variant="ghost" size="icon" className="hidden size-11 sm:inline-flex" aria-label={nav.home} title={nav.home}>
                        <Link href={route('home')}>
                            <Home className="size-4" />
                        </Link>
                    </Button>
                    <LanguageSwitcher />
                    <NotificationBell viewAllRoute="syndicate.notifications.index" locale={props.locale} group="syndicate" />
                    <ThemeToggle label={nav.toggle_theme_aria} />
                    <LogoutButton />
                </div>
            </div>
        </header>
    );
}
