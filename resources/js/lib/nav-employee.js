import { Bell, Grid2x2, CircleCheck, Hourglass, CircleX } from 'lucide-react';

/**
 * Employee nav matches by path + `status` query param rather than route-name
 * globbing, since the three product tabs all resolve to the same route with
 * a different `status` value. `currentUrl` is Inertia's `usePage().url`.
 */
export function getEmployeeNavGroups(t, nav, currentUrl) {
    const [currentPath, currentQuery] = currentUrl.split('?');
    const currentStatus = new URLSearchParams(currentQuery ?? '').get('status') ?? '';
    const isActive = (href) => {
        const [path, query] = href.split('?');
        const status = new URLSearchParams(query ?? '').get('status') ?? '';
        return path === currentPath && status === currentStatus;
    };

    const raw = [
        { label: nav.dashboard, items: [{ label: nav.dashboard, href: route('employee.dashboard'), icon: Grid2x2 }] },
        {
            label: t.workspace,
            items: [
                { label: t.active_products_tab, href: route('employee.products.index', { status: 'approved' }), icon: CircleCheck },
                { label: t.pending_products, href: route('employee.products.index', { status: 'pending' }), icon: Hourglass },
                { label: t.rejected_products, href: route('employee.products.index', { status: 'rejected' }), icon: CircleX },
                { label: nav.notifications, href: route('employee.notifications.index'), icon: Bell },
            ],
        },
    ];

    return raw.map((group) => ({
        label: group.label,
        items: group.items.map((item) => ({
            key: item.href,
            label: item.label,
            href: item.href,
            active: isActive(item.href),
            icon: item.icon,
        })),
    }));
}
