import { syndicateWorkspace } from '@/lib/syndicate-workspace';
import { NotificationCenter } from '@/Components/workspace/NotificationCenter';
import { useI18n, useLocale } from '@/hooks/use-i18n';

export default function SyndicateNotificationsIndex({ workspace = 'syndicate', sector = 'combined' }) {
    const { Layout } = syndicateWorkspace(workspace, sector);
    const { notificationPreferences } = useI18n();
    const locale = useLocale();

    return <Layout title={notificationPreferences.title}><NotificationCenter role={workspace === 'ministry' ? 'ministry' : 'syndicate'} locale={locale} /></Layout>;
}
