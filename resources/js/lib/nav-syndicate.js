import { Bell, LayoutGrid, Layers, Store, Package, Mic, ShoppingBag, TrendingUp, BarChart3 } from 'lucide-react';

export function getSyndicateNavGroups(t) {
    const items = [
        { label: t.dashboard, route: 'syndicate.dashboard', active: 'syndicate.dashboard', icon: LayoutGrid },
        { label: t.categories, route: 'syndicate.categories', active: 'syndicate.categories', icon: Layers },
        { label: t.vendors, route: 'syndicate.vendors', active: 'syndicate.vendors', icon: Store },
        { label: t.products, route: 'syndicate.products', active: 'syndicate.products', icon: Package },
        { label: t.podcasts, route: 'syndicate.podcasts', active: 'syndicate.podcasts', icon: Mic },
        { label: t.orders, route: 'syndicate.orders', active: 'syndicate.orders', icon: ShoppingBag },
        { label: t.sales, route: 'syndicate.sales', active: 'syndicate.sales', icon: TrendingUp },
        { label: t.reports, route: 'syndicate.reports', active: 'syndicate.reports', icon: BarChart3 },
        { label: t.notifications, route: 'syndicate.notifications.index', active: 'syndicate.notifications.*', icon: Bell },
    ];

    return [
        {
            label: t.workspace,
            items: items.map((item) => ({
                key: item.route,
                label: item.label,
                href: route(item.route),
                active: route().current(item.active),
                icon: item.icon,
            })),
        },
    ];
}
