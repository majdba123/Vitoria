import { Users } from 'lucide-react';
import { RoleSidebar } from '@/Components/shared/RoleSidebar';
import { getSyndicateNavGroups } from '@/lib/nav-syndicate';
import { useI18n } from '@/hooks/use-i18n';

export function SyndicateSidebar(props) {
    const { syndicate } = useI18n();
    const groups = getSyndicateNavGroups(syndicate);

    return (
        <RoleSidebar
            {...props}
            brandIcon={Users}
            homeHref={route('syndicate.dashboard')}
            workspaceLabel={syndicate.workspace_label}
            navigationLabel={syndicate.navigation_label}
            groups={groups}
            footer={<p className="px-2 pb-1 text-[11px] text-sidebar-foreground/70">{syndicate.workspace_footer}</p>}
        />
    );
}
