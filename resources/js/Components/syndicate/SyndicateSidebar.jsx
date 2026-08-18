import { Link, usePage } from '@inertiajs/react';
import { Users } from 'lucide-react';
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

    return (
        <Sidebar side={side} collapsible="offcanvas" role="navigation" aria-label="Syndicate navigation" {...props}>
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
                <p className="px-2 pb-1 text-[11px] text-sidebar-foreground/70">{syndicate.workspace_footer}</p>
            </SidebarFooter>
        </Sidebar>
    );
}
