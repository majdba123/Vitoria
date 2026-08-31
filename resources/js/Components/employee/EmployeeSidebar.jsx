import { usePage } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import { RoleSidebar } from '@/Components/shared/RoleSidebar';
import { getEmployeeNavGroups } from '@/lib/nav-employee';
import { useI18n } from '@/hooks/use-i18n';

export function EmployeeSidebar(props) {
    const { employee, nav } = useI18n();
    const { url } = usePage();
    const groups = getEmployeeNavGroups(employee, nav, url);

    return (
        <RoleSidebar
            {...props}
            brandIcon={ClipboardCheck}
            homeHref={route('employee.dashboard')}
            workspaceLabel={employee.workspace_label}
            navigationLabel={employee.navigation_label}
            groups={groups}
            footer={<p className="px-2 pb-1 text-[11px] text-sidebar-foreground/70">{employee.workspace_footer}</p>}
        />
    );
}
