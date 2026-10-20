import { Warehouse } from 'lucide-react';
import { RoleSidebar } from '@/Components/shared/RoleSidebar';
import { UserMenu } from '@/Components/workspace/UserMenu';
import { getAdminNavGroups } from '@/lib/nav-admin';
import { useI18n } from '@/hooks/use-i18n';
import { useAdminPermissions } from '@/hooks/use-admin-permissions';

export function AdminSidebar(props) {
    const { admin, importShipments } = useI18n();
    const { can } = useAdminPermissions();
    const groups = getAdminNavGroups(admin, importShipments, can);

    return (
        <RoleSidebar
            {...props}
            brandIcon={Warehouse}
            homeHref={route('admin.home')}
            workspaceLabel={admin.badge}
            navigationLabel={admin.navigation_label}
            groups={groups}
            footer={<UserMenu roleLabel={admin.badge} />}
        />
    );
}
