import EmployeeLayout from '@/Layouts/EmployeeLayout';
import { NotificationCenter } from '@/Components/workspace/NotificationCenter';
import { useI18n, useLocale } from '@/hooks/use-i18n';

export default function EmployeeNotificationsIndex() {
    const { notificationPreferences } = useI18n();
    const locale = useLocale();

    return <EmployeeLayout title={notificationPreferences.title}><NotificationCenter role="employee" locale={locale} /></EmployeeLayout>;
}
