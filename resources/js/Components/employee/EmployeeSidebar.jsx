import { Link, usePage } from '@inertiajs/react';
import { LogOut, ClipboardCheck } from 'lucide-react';
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
import { getEmployeeNavGroups } from '@/lib/nav-employee';
import { useI18n } from '@/hooks/use-i18n';

export function EmployeeSidebar(props) {
    const { employee, nav } = useI18n();
    const { props: pageProps, url } = usePage();
    const groups = getEmployeeNavGroups(employee, nav);
    const side = pageProps.direction === 'rtl' ? 'right' : 'left';

    const currentPath = url.split('?')[0];
    const currentStatus = new URLSearchParams(url.split('?')[1] ?? '').get('status') ?? '';

    const isItemActive = (href) => {
        const [path, query] = href.split('?');
        const status = new URLSearchParams(query ?? '').get('status') ?? '';
        return path === currentPath && status === currentStatus;
    };

    const handleLogout = () => {
        window.VetoraWorkspace?.logout?.(route('login'));
    };

    return (
        <Sidebar side={side} collapsible="offcanvas" role="navigation" aria-label="Employee navigation" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="hover:bg-transparent active:bg-transparent">
                            <Link href={route('employee.dashboard')}>
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary/15 text-sidebar-primary ring-1 ring-inset ring-sidebar-primary/30">
                                    <ClipboardCheck className="size-4" />
                                </span>
                                <div className="grid flex-1 text-start leading-tight">
                                    <span className="font-display text-base font-extrabold text-sidebar-foreground">Vetora</span>
                                    <span className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-sidebar-primary">{employee.workspace_label}</span>
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
                                    <SidebarMenuItem key={item.href}>
                                        <SidebarMenuButton asChild isActive={isItemActive(item.href)} tooltip={item.label}>
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
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        {/* The sidebar always has a dark background regardless of the site's
                            light/dark theme, so the theme-swapping --color-danger-strong token
                            (which resolves to a dark, low-contrast red in light mode) is wrong
                            here — axe-core measured 1.68:1 against the required 4.5:1. Use the
                            fixed dark-background-safe danger tone instead. */}
                        <SidebarMenuButton onClick={handleLogout} className="text-[var(--color-danger-300)] hover:bg-[rgba(185,56,69,0.16)] hover:text-[var(--color-danger-300)]">
                            <LogOut />
                            <span>{nav.sign_out}</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <p className="px-2 pb-1 text-[11px] text-sidebar-foreground/70">{employee.workspace_footer}</p>
            </SidebarFooter>
        </Sidebar>
    );
}
