import { Link, usePage } from '@inertiajs/react';
import { LogOut, Users } from 'lucide-react';
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
import { getSyndicateNavItems } from '@/lib/nav-syndicate';
import { useI18n } from '@/hooks/use-i18n';

export function SyndicateSidebar(props) {
    const { syndicate } = useI18n();
    const { props: pageProps } = usePage();
    const items = getSyndicateNavItems(syndicate);
    const side = pageProps.direction === 'rtl' ? 'right' : 'left';

    const handleLogout = () => {
        window.VetoraWorkspace?.logout?.(route('login'));
    };

    return (
        <Sidebar side={side} collapsible="offcanvas" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="hover:bg-transparent active:bg-transparent">
                            <Link href={route('syndicate.dashboard')}>
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary/15 text-sidebar-primary ring-1 ring-inset ring-sidebar-primary/30">
                                    <Users className="size-4" />
                                </span>
                                <div className="grid flex-1 text-start leading-tight">
                                    <span className="font-display text-base font-extrabold text-sidebar-foreground">Vetora</span>
                                    <span className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-sidebar-primary">{syndicate.workspace_label}</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupLabel>{syndicate.workspace}</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {items.map((item) => (
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
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton onClick={handleLogout} className="text-[var(--color-danger-strong)] hover:bg-[var(--color-danger-soft)] hover:text-[var(--color-danger-strong)]">
                            <LogOut />
                            <span>{syndicate.sign_out}</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <p className="px-2 pb-1 text-[11px] text-sidebar-foreground/40">{syndicate.workspace_footer}</p>
            </SidebarFooter>
        </Sidebar>
    );
}
