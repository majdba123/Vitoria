import AdminLayout from '@/Layouts/AdminLayout';
import EmployeeLayout from '@/Layouts/EmployeeLayout';
import SyndicateLayout from '@/Layouts/SyndicateLayout';

const LAYOUTS = { admin: AdminLayout, employee: EmployeeLayout, syndicate: SyndicateLayout };

/**
 * Import shipments are shared by three workspaces; each page receives a
 * `workspace` prop from routes/web.php and resolves its layout, API base and
 * route-name prefix here.
 */
export function shipmentsWorkspace(workspace) {
    const key = LAYOUTS[workspace] ? workspace : 'admin';

    return {
        Layout: LAYOUTS[key],
        apiBase: `/api/${key}/import-shipments`,
        routeName: (name) => `${key}.shipments.${name}`,
    };
}

export function emptyShipmentItem() {
    return { name: '', country: '', quantity: '', total_price: '' };
}
