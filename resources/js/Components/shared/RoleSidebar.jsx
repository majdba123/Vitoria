import { Link, usePage } from '@inertiajs/react';
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

/**
 * Shared chrome for the Admin/Vendor/Syndicate/Employee sidebars: brand
 * header, grouped nav items, optional extra content (e.g. Vendor's category
 * disclosure list), and a footer slot. Each role's Sidebar component owns
 * its icon, workspace label, home route, translated groups (from
 * lib/nav-*.js), and footer content.
 */
export function RoleSidebar({ brandIcon: BrandIcon, homeHref, workspaceLabel, navigationLabel, groups, extraContent, footer, ...props }) {
    const { props: pageProps } = usePage();
    // shadcn's <Sidebar> pins to a physical left/right edge via the `side`
    // prop - it does not follow `dir="rtl"` on its own, so the workspace
    // shell (always right-anchored in Arabic, matching the retired Blade
    // layout's $isRtl ? 'right-0' : 'left-0') has to opt in explicitly.
    const side = pageProps.direction === 'rtl' ? 'right' : 'left';

    return (
        <Sidebar side={side} collapsible="offcanvas" role="navigation" aria-label={navigationLabel} {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="hover:bg-transparent active:bg-transparent">
                            <Link href={homeHref}>
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary/15 text-sidebar-primary ring-1 ring-inset ring-sidebar-primary/30">
                                    <BrandIcon className="size-4" />
                                </span>
                                <div className="grid flex-1 text-start leading-tight">
                                    <span className="font-display text-base font-extrabold text-sidebar-foreground">Vetora</span>
                                    <span className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-sidebar-primary">{workspaceLabel}</span>
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
                                    <SidebarMenuItem key={item.key}>
                                        <SidebarMenuButton asChild isActive={item.active} tooltip={item.label}>
                                            <Link href={item.href}>
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

                {extraContent}
            </SidebarContent>

            <SidebarFooter>{footer}</SidebarFooter>
        </Sidebar>
    );
}
