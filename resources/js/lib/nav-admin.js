import {
    LayoutGrid,
    Store,
    Handshake,
    Package,
    UserCog,
    Ticket,
    ShoppingBag,
    Users,
    Bell,
    Mail,
    Info,
    FileText,
    Image as ImageIcon,
    Layers,
    Workflow,
    Building2,
    Wallet,
    Container,
    MapPin,
    ShieldCheck,
    ScrollText,
} from 'lucide-react';

/**
 * "Users" (application customers) and "Employees" both resolve to the same
 * `admin.users.index` route (Employees via a server-side redirect), so
 * `route().current()` alone can't tell them apart once landed — it would
 * highlight "Users" for both. Match on the `type` query param instead.
 * "Users" has no dedicated `type=0` link of its own (the page itself
 * defaults an absent `type` to customers), so it must also match a bare
 * `/admin/users` visit with no query param at all.
 */
function isUsersTypeActive(expectedType) {
    if (!route().current('admin.users.index')) return false;
    const search = typeof window === 'undefined' ? '' : window.location.search;
    const type = new URLSearchParams(search).get('type');
    return expectedType === null ? (!type || type === '0') : type === expectedType;
}

/**
 * Admin sidebar groups. Each item names the back-office permission its page
 * requires (the same key App\Support\AdminRoutePermissions enforces on the
 * server); items the administrator cannot open are hidden, and so are groups
 * left empty. Hiding is a convenience only — the server still refuses the page.
 */
export function getAdminNavGroups(t, shipments = {}, can = () => true) {
    const raw = [
        {
            label: t.overview,
            items: [{ label: t.dashboard, route: 'admin.dashboard', active: 'admin.dashboard', icon: LayoutGrid, permission: 'dashboard.view' }],
        },
        {
            label: t.group_financials,
            items: [{ label: t.financials, route: 'admin.financials.index', active: 'admin.financials.*', icon: Wallet, permission: 'financials.view' }],
        },
        {
            label: t.management,
            items: [
                { label: t.vendors, route: 'admin.vendors.index', active: 'admin.vendors.*', icon: Store, permission: 'vendors.view' },
                { label: t.syndicates, route: 'admin.syndicates.index', active: 'admin.syndicates.*', icon: Handshake, permission: 'syndicates.view' },
                { label: t.products, route: 'admin.products.index', active: 'admin.products.*', icon: Package, permission: 'products.view' },
                { label: t.employees, route: 'admin.employees.index', usersType: '4', icon: UserCog, permission: 'users.view' },
                { label: t.coupons, route: 'admin.coupons.index', active: 'admin.coupons.*', icon: Ticket, permission: 'coupons.view' },
                { label: t.orders, route: 'admin.orders.index', active: 'admin.orders.*', icon: ShoppingBag, permission: 'orders.view' },
                { label: t.users, route: 'admin.users.index', usersType: null, icon: Users, permission: 'users.view' },
                { label: t.notifications_log, route: 'admin.notifications.index', active: 'admin.notifications.*', icon: Bell, permission: 'notifications.view' },
                { label: t.contact_messages, route: 'admin.contact-messages.index', active: 'admin.contact-messages.*', icon: Mail, permission: 'contact_messages.view' },
                { label: t.about_us, route: 'admin.about-us.edit', active: 'admin.about-us.*', icon: Info, permission: 'content.view' },
                { label: t.pages, route: 'admin.pages.index', active: 'admin.pages.*', icon: FileText, permission: 'content.view' },
                { label: t.banners, route: 'admin.banners.index', active: 'admin.banners.*', icon: ImageIcon, permission: 'content.view' },
            ],
        },
        {
            label: shipments.title,
            items: [
                { label: shipments.title, route: 'admin.shipments.index', active: 'admin.shipments.*', icon: Container, permission: 'import_shipments.view' },
                { label: shipments.ports_title, route: 'admin.ports.index', active: 'admin.ports.*', icon: MapPin, permission: 'import_shipments.view' },
            ],
        },
        {
            label: t.group_access,
            items: [
                { label: t.rbac_roles, route: 'admin.roles.index', active: 'admin.roles.*', icon: ShieldCheck, permission: 'roles.view' },
                { label: t.rbac_audit_logs, route: 'admin.audit-logs.index', active: 'admin.audit-logs.*', icon: ScrollText, permission: 'audit_logs.view' },
            ],
        },
        {
            label: t.catalog,
            items: [
                { label: t.categories, route: 'admin.categories.index', active: 'admin.categories.*', icon: Layers },
                { label: t.subcategories, route: 'admin.subcategories.index', active: 'admin.subcategories.*', icon: Workflow },
                { label: t.cities, route: 'admin.cities.index', active: 'admin.cities.*', icon: Building2 },
            ],
        },
    ];

    return raw
        .map((group) => ({
            key: group.items[0].route,
            label: group.label,
            items: group.items
                .filter((item) => can(item.permission))
                .map((item) => ({
                    key: item.route,
                    label: item.label,
                    href: route(item.route),
                    active: 'usersType' in item ? isUsersTypeActive(item.usersType) : route().current(item.active),
                    icon: item.icon,
                })),
        }))
        .filter((group) => group.items.length > 0);
}
