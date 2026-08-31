import SyndicateLayout from '@/Layouts/SyndicateLayout';
import { NotificationCenter } from '@/Components/workspace/NotificationCenter';
import { useI18n, useLocale } from '@/hooks/use-i18n';

export default function SyndicateNotificationsIndex() {
    const { notificationPreferences } = useI18n();
    const locale = useLocale();

    return <SyndicateLayout title={notificationPreferences.title}><NotificationCenter role="syndicate" locale={locale} /></SyndicateLayout>;
}
