import { LayoutGrid, Package, ShoppingBag, TrendingUp, Bell, User } from 'lucide-react';

/** Mirrors resources/views/components/vendor/sidebar.blade.php's link list. */
export function getVendorNavGroups(t, common) {
    const raw = [
        {
            label: t.group_overview,
            items: [{ label: t.dashboard, route: 'vendor.dashboard', active: 'vendor.dashboard', icon: LayoutGrid }],
        },
        {
            label: t.group_store,
            items: [
                { label: t.products, route: 'vendor.products.index', active: 'vendor.products.*', icon: Package },
                { label: t.orders, route: 'vendor.orders.index', active: 'vendor.orders.*', icon: ShoppingBag },
                { label: t.sales, route: 'vendor.sales', active: 'vendor.sales', icon: TrendingUp },
                { label: t.notifications, route: 'vendor.notifications.index', active: 'vendor.notifications.*', icon: Bell },
            ],
        },
        {
            label: t.group_account,
            items: [{ label: common.profile, route: 'vendor.profile', active: 'vendor.profile', icon: User }],
        },
    ];

    return raw.map((group) => ({
        label: group.label,
        items: group.items.map((item) => ({
            key: item.route,
            label: item.label,
            href: route(item.route),
            active: route().current(item.active),
            icon: item.icon,
        })),
    }));
}
