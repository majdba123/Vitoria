import { Bell, Grid2x2, CircleCheck, Hourglass, CircleX } from 'lucide-react';

export function getEmployeeNavGroups(t, nav) {
    return [
        { label: nav.dashboard, items: [{ label: nav.dashboard, href: route('employee.dashboard'), icon: Grid2x2 }] },
        {
            label: t.workspace,
            items: [
                { label: t.active_products_tab, href: route('employee.products.index', { status: 'approved' }), icon: CircleCheck },
                { label: t.pending_products, href: route('employee.products.index', { status: 'pending' }), icon: Hourglass },
                { label: t.rejected_products, href: route('employee.products.index', { status: 'rejected' }), icon: CircleX },
                { label: nav.notifications ?? 'Notifications', href: route('employee.notifications.index'), icon: Bell },
            ],
        },
    ];
}
