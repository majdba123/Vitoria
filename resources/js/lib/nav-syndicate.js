import { Bell, LayoutGrid, Layers, Store, Package, Mic, ShoppingBag, TrendingUp, BarChart3, Wallet } from 'lucide-react';

export function getSyndicateNavGroups(t) {
    const link = (item) => ({
        key: item.route,
        label: item.label,
        href: route(item.route),
        active: route().current(item.active),
        icon: item.icon,
    });

    const items = [
        { label: t.dashboard, route: 'syndicate.dashboard', active: 'syndicate.dashboard', icon: LayoutGrid },
        { label: t.categories, route: 'syndicate.categories', active: 'syndicate.categories', icon: Layers },
        { label: t.vendors, route: 'syndicate.vendors', active: 'syndicate.vendors', icon: Store },
        { label: t.products, route: 'syndicate.products', active: 'syndicate.products', icon: Package },
        { label: t.podcasts, route: 'syndicate.podcasts', active: 'syndicate.podcasts', icon: Mic },
        { label: t.orders, route: 'syndicate.orders', active: 'syndicate.orders', icon: ShoppingBag },
    ].map(link);

    const financials = [
        { label: t.sales, route: 'syndicate.sales', active: 'syndicate.sales', icon: TrendingUp },
        { label: t.reports, route: 'syndicate.reports', active: 'syndicate.reports', icon: BarChart3 },
    ].map(link);

    const notifications = link({ label: t.notifications, route: 'syndicate.notifications.index', active: 'syndicate.notifications.*', icon: Bell });

    return [
        {
            label: t.workspace,
            items: [
                ...items,
                { key: 'financials', label: t.group_financials, icon: Wallet, items: financials },
                notifications,
            ],
        },
    ];
}
