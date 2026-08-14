import { Link, usePage } from '@inertiajs/react';
import { Warehouse } from 'lucide-react';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/Components/ui/sidebar';
import { UserMenu } from '@/Components/workspace/UserMenu';
import { getAdminNavGroups } from '@/lib/nav-admin';
import { useI18n } from '@/hooks/use-i18n';

export function AdminSidebar(props) {
    const { admin } = useI18n();
    const { props: pageProps } = usePage();
    const groups = getAdminNavGroups(admin);
    // shadcn's <Sidebar> pins to a physical left/right edge via the `side`
    // prop - it does not follow `dir="rtl"` on its own, so the workspace
    // shell (always right-anchored in Arabic, matching the retired Blade
    // layout's $isRtl ? 'right-0' : 'left-0') has to opt in explicitly.
    const side = pageProps.direction === 'rtl' ? 'right' : 'left';

    return (
        <Sidebar side={side} collapsible="offcanvas" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="hover:bg-transparent active:bg-transparent">
                            <Link href={route('admin.dashboard')}>
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary/15 text-sidebar-primary ring-1 ring-inset ring-sidebar-primary/30">
                                    <Warehouse className="size-4" />
                                </span>
                                <div className="grid flex-1 text-start leading-tight">
                                    <span className="font-display text-base font-extrabold text-sidebar-foreground">Vetora</span>
                                    <span className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-sidebar-primary">{admin.badge}</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {groups.map((group) => (
                    <SidebarGroup key={group.label}>
                        <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {group.items.map((item) => (
                                    <SidebarMenuItem key={item.route}>
                                        <SidebarMenuButton asChild isActive={route().current(item.active)} tooltip={item.label}>
                                            <Link href={route(item.route)}>
                                                <item.icon />
                                                <span>{item.label}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <UserMenu roleLabel={admin.badge} />
            </SidebarFooter>
        </Sidebar>
    );
}
