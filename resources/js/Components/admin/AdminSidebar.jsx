import { Warehouse } from 'lucide-react';
import { RoleSidebar } from '@/Components/shared/RoleSidebar';
import { UserMenu } from '@/Components/workspace/UserMenu';
import { getAdminNavGroups } from '@/lib/nav-admin';
import { useI18n } from '@/hooks/use-i18n';

export function AdminSidebar(props) {
    const { admin } = useI18n();
    const groups = getAdminNavGroups(admin);

    return (
        <RoleSidebar
            {...props}
            brandIcon={Warehouse}
            homeHref={route('admin.dashboard')}
            workspaceLabel={admin.badge}
            navigationLabel={admin.navigation_label}
            groups={groups}
            footer={<UserMenu roleLabel={admin.badge} />}
        />
    );
}
