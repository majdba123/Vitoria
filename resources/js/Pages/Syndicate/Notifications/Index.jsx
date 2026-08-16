import { usePage } from '@inertiajs/react';
import SyndicateLayout from '@/Layouts/SyndicateLayout';
import { NotificationCenter } from '@/Components/workspace/NotificationCenter';

export default function SyndicateNotificationsIndex() {
    const { props } = usePage();
    const title = props.locale === 'ar' ? 'الإشعارات' : 'Notifications';

    return <SyndicateLayout title={title}><NotificationCenter role="syndicate" locale={props.locale} /></SyndicateLayout>;
}
