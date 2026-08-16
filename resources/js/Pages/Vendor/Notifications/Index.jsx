import { usePage } from '@inertiajs/react';
import VendorLayout from '@/Layouts/VendorLayout';
import { NotificationCenter } from '@/Components/workspace/NotificationCenter';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorNotificationsIndex() {
    const { vendor } = useI18n();
    const { props } = usePage();

    return <VendorLayout title={vendor.notifications_title}><NotificationCenter role="vendor" locale={props.locale} /></VendorLayout>;
}
