import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
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
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/Components/ui/sidebar';

/** A nav item with nested `items` renders as a collapsible parent (e.g. the
 * syndicate sidebar's "Financials" group) instead of a direct link. */
function CollapsibleNavItem({ item }) {
    const [open, setOpen] = useState(() => item.items.some((child) => child.active));

    return (
        <SidebarMenuItem>
            <SidebarMenuButton onClick={() => setOpen((v) => !v)} aria-expanded={open} className="h-auto min-h-8 items-start py-2">
                <item.icon />
                <span className="!overflow-visible !whitespace-normal !text-clip min-w-0 flex-1 break-words leading-snug">{item.label}</span>
                <ChevronDown className={`size-4 shrink-0 transition-transform ${open ? 'rotate-180' : ''}`} />
            </SidebarMenuButton>
            {open && (
                <SidebarMenuSub>
                    {item.items.map((child) => (
                        <SidebarMenuSubItem key={child.key}>
                            <SidebarMenuSubButton asChild isActive={child.active}>
                                <Link href={child.href}>
                                    <child.icon />
                                    <span className="truncate">{child.label}</span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    ))}
                </SidebarMenuSub>
            )}
        </SidebarMenuItem>
    );
}

/**
 * Shared chrome for the Admin/Vendor/Syndicate/Employee sidebars: brand
 * header, grouped nav items, optional extra content (e.g. Vendor's category
 * disclosure list), and a footer slot. Each role's Sidebar component owns
 * its icon, workspace label, home route, translated groups (from
 * lib/nav-*.js), and footer content. A group item with nested `items`
 * renders as a collapsible sub-menu instead of a direct link.
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
                                    <span className="!overflow-visible !whitespace-normal !text-clip break-words text-[10px] font-extrabold uppercase leading-snug tracking-[0.12em] text-sidebar-primary">{workspaceLabel}</span>
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
                                {group.items.map((item) =>
                                    item.items ? (
                                        <CollapsibleNavItem key={item.key} item={item} />
                                    ) : (
                                        <SidebarMenuItem key={item.key}>
                                            <SidebarMenuButton asChild isActive={item.active} tooltip={item.label} className="h-auto min-h-8 items-start py-2">
                                                <Link href={item.href}>
                                                    <item.icon />
                                                    <span className="!overflow-visible !whitespace-normal !text-clip break-words leading-snug">{item.label}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    )
                                )}
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
