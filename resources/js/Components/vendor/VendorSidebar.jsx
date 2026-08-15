import { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Store, ChevronDown, Layers } from 'lucide-react';
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
import { getVendorNavGroups } from '@/lib/nav-vendor';
import { useI18n } from '@/hooks/use-i18n';

function CategoryDisclosure({ category, groupLabel }) {
    const [open, setOpen] = useState(false);
    const subcategories = category.subcategories ?? [];

    return (
        <div>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="mb-1 flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-start text-sm font-semibold text-sidebar-foreground/75 transition-colors hover:bg-sidebar-accent hover:text-sidebar-foreground"
            >
                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-accent text-sidebar-primary">
                    <Layers className="size-4" />
                </span>
                <span className="flex-1 truncate">{category.name}</span>
                <span className="rounded-full bg-sidebar-primary/15 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.18em] text-sidebar-primary">
                    {parseFloat(category.commission || 0).toFixed(0)}%
                </span>
                <ChevronDown className={`size-4 shrink-0 transition-transform ${open ? 'rotate-180' : ''}`} />
            </button>
            <p className="mb-2 px-3 text-[11px] font-semibold text-sidebar-foreground/70">{groupLabel}</p>
            {open && subcategories.length > 0 && (
                <div className="ms-4 space-y-1 border-s border-sidebar-border ps-3 pb-2">
                    {subcategories.map((sub) => (
                        <div key={sub.id} className="flex items-center gap-2 rounded-md px-2 py-2 text-xs text-sidebar-foreground/55 hover:bg-sidebar-accent hover:text-sidebar-foreground/80">
                            <span className="size-1.5 rounded-full bg-sidebar-primary/70" />
                            <span className="truncate">{sub.name}</span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export function VendorSidebar(props) {
    const { common, vendor } = useI18n();
    const { props: pageProps } = usePage();
    const groups = getVendorNavGroups(vendor, { profile: common.profile });
    const side = pageProps.direction === 'rtl' ? 'right' : 'left';
    const [categories, setCategories] = useState(null);

    useEffect(() => {
        window.axios.get('/api/vendor/allowed-categories', { silent: true }).then((res) => setCategories(res.data?.data ?? []));
    }, []);

    return (
        <Sidebar side={side} collapsible="offcanvas" role="navigation" aria-label="Vendor navigation" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="hover:bg-transparent active:bg-transparent">
                            <Link href={route('vendor.dashboard')}>
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary/15 text-sidebar-primary ring-1 ring-inset ring-sidebar-primary/30">
                                    <Store className="size-4" />
                                </span>
                                <div className="grid flex-1 text-start leading-tight">
                                    <span className="font-display text-base font-extrabold text-sidebar-foreground">Vetora</span>
                                    <span className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-sidebar-primary">{vendor.badge}</span>
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

                <SidebarGroup>
                    <SidebarGroupLabel>{vendor.categories}</SidebarGroupLabel>
                    <SidebarGroupContent className="space-y-1 px-1">
                        {categories === null && <div className="h-10 animate-pulse rounded-md bg-sidebar-accent" />}
                        {/* text-sidebar-foreground/50 measured 3.6:1 against this background
                            (axe-core, needs 4.5:1 for normal-size text) — /80 clears it. */}
                        {categories?.length === 0 && <p className="rounded-md bg-sidebar-accent/50 px-3 py-3 text-xs italic text-sidebar-foreground/80">{vendor.assigned_categories_empty}</p>}
                        {categories?.map((category) => (
                            <CategoryDisclosure key={category.id} category={category} groupLabel={category.type === 'veterinary' ? vendor.group_veterinary : vendor.group_agriculture} />
                        ))}
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <UserMenu roleLabel={vendor.seller} group="vendor" />
            </SidebarFooter>
        </Sidebar>
    );
}
