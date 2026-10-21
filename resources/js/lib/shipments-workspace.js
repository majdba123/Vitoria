import AdminLayout from '@/Layouts/AdminLayout';
import EmployeeLayout from '@/Layouts/EmployeeLayout';
import SyndicateLayout from '@/Layouts/SyndicateLayout';
import MinistryLayout from '@/Layouts/MinistryLayout';

const LAYOUTS = { admin: AdminLayout, employee: EmployeeLayout, syndicate: SyndicateLayout, ministry: MinistryLayout };

/**
 * Import shipments are shared by four workspaces; each page receives a
 * `workspace` prop from routes/web.php and resolves its layout, API base and
 * route-name prefix here. The Ministry is read-only and filters by `sector`.
 */
export function shipmentsWorkspace(workspace, sector = 'combined') {
    const key = LAYOUTS[workspace] ? workspace : 'admin';
    const sectorParams = key === 'ministry' && sector && sector !== 'combined' ? { sector } : {};

    return {
        Layout: LAYOUTS[key],
        apiBase: `/api/${key}/import-shipments`,
        routeName: (name) => `${key}.shipments.${name}`,
        href: (name, id) => route(`${key}.shipments.${name}`, id === undefined ? sectorParams : { id, ...sectorParams }),
        readOnly: key === 'ministry',
        sectorParams,
    };
}

export function emptyShipmentItem() {
    return { name: '', country: '', quantity: '', total_price: '' };
}
