import { usePage } from '@inertiajs/react';
import EmployeeLayout from '@/Layouts/EmployeeLayout';
import { NotificationCenter } from '@/Components/workspace/NotificationCenter';

export default function EmployeeNotificationsIndex() {
    const { props } = usePage();
    const title = props.locale === 'ar' ? 'الإشعارات' : 'Notifications';

    return <EmployeeLayout title={title}><NotificationCenter role="employee" locale={props.locale} /></EmployeeLayout>;
}
