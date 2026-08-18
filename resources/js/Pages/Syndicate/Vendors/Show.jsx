import SyndicateLayout from '@/Layouts/SyndicateLayout';
import { Vendor360 } from '@/Components/vendor360/Vendor360';
import { useI18n } from '@/hooks/use-i18n';

export default function SyndicateVendorShow({ vendorId }) {
    const { vendorAnalytics = {} } = useI18n();

    return <SyndicateLayout title={vendorAnalytics.title}><Vendor360 vendorId={vendorId} mode="syndicate" /></SyndicateLayout>;
}
