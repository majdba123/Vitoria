import { usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { NotificationCenter } from '@/Components/workspace/NotificationCenter';
import { useI18n } from '@/hooks/use-i18n';

export default function NotificationsIndex() {
    const { admin } = useI18n();
    const { props } = usePage();

    return <AdminLayout title={admin.notifications_log}><NotificationCenter role="admin" locale={props.locale} sendRoute="admin.notifications.send" /></AdminLayout>;
}
