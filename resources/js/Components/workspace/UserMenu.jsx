import { ChevronsUpDown, Home } from 'lucide-react';
import { Link } from '@inertiajs/react';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/Components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/Components/ui/sidebar';
import { useAuthUser, useI18n } from '@/hooks/use-i18n';

export function UserMenu({ roleLabel }) {
    const { isMobile } = useSidebar();
    const user = useAuthUser();
    const { nav } = useI18n();

    if (!user) return null;

    const initials = (user.name || '?').trim().charAt(0).toUpperCase();

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton size="lg" className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                            <Avatar className="size-8 rounded-md">
                                <AvatarImage src={user.avatar_url} alt={user.name} />
                                <AvatarFallback className="rounded-md bg-primary text-primary-foreground">{initials}</AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-start text-sm leading-tight">
                                <span className="truncate font-semibold">{user.name}</span>
                                <span className="truncate text-xs text-sidebar-foreground/60">{roleLabel}</span>
                            </div>
                            <ChevronsUpDown className="ms-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56"
                        side={isMobile ? 'bottom' : 'right'}
                        align="end"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="font-normal">
                            <div className="flex flex-col text-start text-sm leading-tight">
                                <span className="truncate font-semibold">{user.name}</span>
                                <span className="truncate text-xs text-muted-foreground">{user.email}</span>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={route('home')}>
                                <Home />
                                {nav.home ?? 'Home'}
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
