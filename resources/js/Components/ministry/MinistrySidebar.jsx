import { usePage } from '@inertiajs/react';
import { Landmark } from 'lucide-react';
import { RoleSidebar } from '@/Components/shared/RoleSidebar';
import { getMinistryNavGroups } from '@/lib/nav-ministry';
import { useI18n } from '@/hooks/use-i18n';

export function MinistrySidebar(props) {
    const { ministry, syndicate, importShipments } = useI18n();
    const { props: pageProps } = usePage();
    const sector = pageProps.sector ?? 'combined';
    const groups = getMinistryNavGroups(ministry, syndicate, importShipments, sector);

    return (
        <RoleSidebar
            {...props}
            brandIcon={Landmark}
            homeHref={route('ministry.dashboard', sector === 'combined' ? {} : { sector })}
            workspaceLabel={ministry.workspace_label}
            navigationLabel={ministry.navigation_label}
            groups={groups}
            footer={<p className="px-2 pb-1 text-[11px] text-sidebar-foreground/70">{ministry.workspace_footer}</p>}
        />
    );
}
