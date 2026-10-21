import { Bell, LayoutGrid, Layers, Store, Package, ShoppingBag, BarChart3, Container } from 'lucide-react';

/**
 * Ministry navigation. Every link keeps the active `?sector=` so moving
 * between sections never silently resets the agriculture/veterinary filter.
 */
export function getMinistryNavGroups(t, syndicate, shipments = {}, sector = 'combined') {
    const sectorParam = sector && sector !== 'combined' ? { sector } : {};
    const link = (item) => ({
        key: item.route,
        label: item.label,
        href: route(item.route, sectorParam),
        active: route().current(item.active),
        icon: item.icon,
    });

    return [
        {
            label: t.workspace,
            items: [
                { label: syndicate.dashboard, route: 'ministry.dashboard', active: 'ministry.dashboard', icon: LayoutGrid },
                { label: syndicate.categories, route: 'ministry.categories', active: 'ministry.categories', icon: Layers },
                { label: syndicate.vendors, route: 'ministry.vendors', active: 'ministry.vendors*', icon: Store },
                { label: syndicate.products, route: 'ministry.products', active: 'ministry.products*', icon: Package },
                { label: syndicate.orders, route: 'ministry.orders', active: 'ministry.orders*', icon: ShoppingBag },
                { label: shipments.title, route: 'ministry.shipments.index', active: 'ministry.shipments.*', icon: Container },
                { label: syndicate.reports, route: 'ministry.reports', active: 'ministry.reports', icon: BarChart3 },
                { label: t.notifications, route: 'ministry.notifications.index', active: 'ministry.notifications.*', icon: Bell },
            ].map(link),
        },
    ];
}
