import AdminLayout from '@/Layouts/AdminLayout';
import { Vendor360 } from '@/Components/vendor360/Vendor360';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorsShow({ vendorId }) {
    const { vendorAnalytics = {} } = useI18n();

    return <AdminLayout title={vendorAnalytics.title}><Vendor360 vendorId={vendorId} mode="admin" /></AdminLayout>;
}
