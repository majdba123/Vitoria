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
} from 'lucide-react';

/**
 * Mirrors resources/views/components/admin/sidebar.blade.php's group/route/
 * icon list. Keep the two in sync until the Blade admin layout is retired.
 */
export function getAdminNavGroups(t) {
    return [
        {
            label: t.overview,
            items: [{ label: t.dashboard, route: 'admin.dashboard', active: 'admin.dashboard', icon: LayoutGrid }],
        },
        {
            label: t.management,
            items: [
                { label: t.vendors, route: 'admin.vendors.index', active: 'admin.vendors.*', icon: Store },
                { label: t.syndicates, route: 'admin.syndicates.index', active: 'admin.syndicates.*', icon: Handshake },
                { label: t.products, route: 'admin.products.index', active: 'admin.products.*', icon: Package },
                { label: t.employees, route: 'admin.employees.index', active: 'admin.employees.*', icon: UserCog },
                { label: t.coupons, route: 'admin.coupons.index', active: 'admin.coupons.*', icon: Ticket },
                { label: t.orders, route: 'admin.orders.index', active: 'admin.orders.*', icon: ShoppingBag },
                { label: t.users, route: 'admin.users.index', active: 'admin.users.*', icon: Users },
                { label: t.notifications_log, route: 'admin.notifications.index', active: 'admin.notifications.*', icon: Bell },
                { label: t.contact_messages, route: 'admin.contact-messages.index', active: 'admin.contact-messages.*', icon: Mail },
                { label: t.about_us, route: 'admin.about-us.edit', active: 'admin.about-us.*', icon: Info },
                { label: t.pages, route: 'admin.pages.index', active: 'admin.pages.*', icon: FileText },
                { label: t.banners, route: 'admin.banners.index', active: 'admin.banners.*', icon: ImageIcon },
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
}
