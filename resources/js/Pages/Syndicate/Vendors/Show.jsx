import { syndicateWorkspace } from '@/lib/syndicate-workspace';
import { Vendor360 } from '@/Components/vendor360/Vendor360';
import { useI18n } from '@/hooks/use-i18n';

export default function SyndicateVendorShow({ vendorId, workspace = 'syndicate', sector = 'combined' }) {
    const { Layout, isMinistry } = syndicateWorkspace(workspace, sector);
    const { vendorAnalytics = {} } = useI18n();

    return <Layout title={vendorAnalytics.title}><Vendor360 vendorId={vendorId} mode={isMinistry ? 'ministry' : 'syndicate'} sector={sector} /></Layout>;
}
