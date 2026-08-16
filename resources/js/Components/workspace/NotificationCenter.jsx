import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Bell, ChevronRight, Info, Loader2, Plus, ShieldCheck } from 'lucide-react';
import { PageHeader } from '@/Components/admin/PageHeader';
import { Pagination } from '@/Components/admin/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Switch } from '@/Components/ui/switch';
import { useAdminList } from '@/hooks/use-admin-list';

const COPY = {
    en: {
        title: 'Notifications', copy: 'Your ongoing activity, decisions, and account updates in one place.', markAll: 'Mark all as read', markOne: 'Mark as read', empty: 'You are all caught up.', failed: 'Notifications could not be loaded.', retry: 'Retry', preferences: 'Notification preferences', preferencesCopy: 'Control what Vetora remembers about how you want to be contacted.', critical: 'Required for account safety and active transactions', optional: 'You can change this preference at any time.', loadingPreferences: 'Loading preferences…', preferencesFailed: 'Preferences could not be loaded.', why: 'Why you received this', publicReason: 'Shared with everyone on Vetora.', privateReason: 'Related directly to your account or work.', sentBy: 'Sent by', send: 'Send notification', categories: { order_updates: 'Order updates', account_security: 'Account security', vendor_compliance: 'Vendor compliance', marketing: 'Product news and offers' },
    },
    ar: {
        title: 'الإشعارات', copy: 'نشاطك وقراراتك وتحديثات حسابك المستمرة في مكان واحد.', markAll: 'تحديد الكل كمقروء', markOne: 'تحديد كمقروء', empty: 'لا توجد إشعارات جديدة.', failed: 'تعذر تحميل الإشعارات.', retry: 'إعادة المحاولة', preferences: 'تفضيلات الإشعارات', preferencesCopy: 'تحكّم في ما تتذكره Vetora عن الطريقة التي تفضّل أن يصلك بها المحتوى.', critical: 'مطلوب لأمان الحساب والمعاملات النشطة', optional: 'يمكنك تغيير هذا التفضيل في أي وقت.', loadingPreferences: 'جارٍ تحميل التفضيلات…', preferencesFailed: 'تعذر تحميل التفضيلات.', why: 'سبب وصوله', publicReason: 'تمت مشاركته مع جميع مستخدمي Vetora.', privateReason: 'يرتبط مباشرةً بحسابك أو عملك.', sentBy: 'أرسله', send: 'إرسال إشعار', categories: { order_updates: 'تحديثات الطلبات', account_security: 'أمان الحساب', vendor_compliance: 'امتثال البائع', marketing: 'أخبار المنتجات والعروض' },
    },
};

function actionHref(role, actionType, actionId) {
    if (!actionType || actionId == null) return null;
    if (actionType === 'product') return role === 'admin' ? `/admin/products/${actionId}` : `/products/${actionId}`;
    if (actionType === 'order') return role === 'admin' ? `/admin/orders/${actionId}` : role === 'vendor' ? `/vendor/orders/${actionId}` : `/orders/${actionId}`;
    return null;
}

export function NotificationCenter({ role, locale = 'en', sendRoute = null }) {
    const text = COPY[locale === 'ar' ? 'ar' : 'en'];
    const [page, setPage] = useState(1);
    const [markingAll, setMarkingAll] = useState(false);
    const [preferences, setPreferences] = useState([]);
    const [preferenceStatus, setPreferenceStatus] = useState('loading');
    const [savingCategory, setSavingCategory] = useState(null);
    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/notifications', { page, per_page: 15 });

    const loadPreferences = () => {
        setPreferenceStatus('loading');
        window.axios.get('/api/notification-preferences', { silent: true }).then((response) => {
            setPreferences(response.data?.data ?? []);
            setPreferenceStatus('ready');
        }).catch(() => setPreferenceStatus('error'));
    };

    useEffect(loadPreferences, []);
    useEffect(() => {
        const refresh = () => reload();
        window.addEventListener('vetora:notifications-updated', refresh);
        return () => window.removeEventListener('vetora:notifications-updated', refresh);
    }, [reload]);

    const markAllRead = () => {
        setMarkingAll(true);
        window.axios.post('/api/notifications/mark-all-read', {}, { silent: true }).then(reload).finally(() => setMarkingAll(false));
    };

    const markOneRead = (id, event) => {
        event.preventDefault();
        event.stopPropagation();
        window.axios.patch(`/api/notifications/${id}/read`, {}, { silent: true }).then(reload);
    };

    const updatePreference = (preference, enabled) => {
        if (!preference.editable) return;
        setSavingCategory(preference.category);
        window.axios.patch('/api/notification-preferences', { category: preference.category, enabled }, { silent: true })
            .then((response) => setPreferences(response.data?.data ?? []))
            .finally(() => setSavingCategory(null));
    };

    return (
        <>
            <PageHeader title={text.title} copy={text.copy} actions={<><Button variant="outline" size="sm" onClick={markAllRead} disabled={markingAll || status !== 'ready'}>{markingAll && <Loader2 className="size-4 animate-spin" />}{text.markAll}</Button>{sendRoute && <Button asChild size="sm"><Link href={route(sendRoute)}><Plus className="size-4" />{text.send}</Link></Button>}</>} />
            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(19rem,0.8fr)] xl:items-start">
                <Card className="gap-0 border-border/80 py-0 shadow-none">
                    {status === 'loading' && <CardContent className="space-y-3 p-5">{[0, 1, 2, 3].map((item) => <Skeleton key={item} className="h-20 w-full" />)}</CardContent>}
                    {status === 'error' && <CardContent className="py-14 text-center"><p className="text-sm font-medium text-[var(--color-danger-strong)]">{errorMessage ?? text.failed}</p><Button variant="outline" size="sm" className="mt-3" onClick={reload}>{text.retry}</Button></CardContent>}
                    {status === 'ready' && rows.length === 0 && <CardContent className="flex flex-col items-center py-16 text-center"><Bell className="size-8 text-muted-foreground" /><p className="mt-3 text-sm font-medium text-muted-foreground">{text.empty}</p></CardContent>}
                    {status === 'ready' && rows.length > 0 && <><ul className="divide-y divide-border">{rows.map((notification) => {
                        const isUnread = !notification.read_at;
                        const href = actionHref(role, notification.action_type, notification.action_id);
                        const Wrapper = href ? Link : 'div';
                        const time = notification.sent_at ? new Date(notification.sent_at).toLocaleString(locale, { dateStyle: 'medium', timeStyle: 'short' }) : '';
                        return <li key={notification.id} className={isUnread ? 'bg-accent/30' : ''}><Wrapper href={href ?? undefined} className="flex min-h-24 items-start gap-3 px-4 py-4 sm:px-5"><span className={`mt-2 size-2 shrink-0 rounded-full ${isUnread ? 'bg-primary' : 'bg-muted-foreground/30'}`} aria-hidden="true" /><div className="min-w-0 flex-1"><p className="text-sm font-semibold text-foreground">{notification.title}</p><p className="mt-1 max-w-[70ch] text-sm leading-6 text-muted-foreground">{notification.body}</p><div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"><span>{time}</span>{notification.sender_name && <span>{text.sentBy}: {notification.sender_name}</span>}<span className="inline-flex items-center gap-1"><Info className="size-3" />{text.why}: {notification.type === 'public' ? text.publicReason : text.privateReason}</span></div>{isUnread && <button type="button" onClick={(event) => markOneRead(notification.id, event)} className="mt-2 min-h-11 text-xs font-semibold text-primary">{text.markOne}</button>}</div>{href && <ChevronRight className="mt-1 size-4 shrink-0 text-muted-foreground rtl:rotate-180" />}</Wrapper></li>;
                    })}</ul><Pagination meta={meta} onPrev={() => setPage((current) => current - 1)} onNext={() => setPage((current) => current + 1)} /></>}
                </Card>
                <Card className="border-border/80 shadow-none"><CardHeader><CardTitle className="text-base">{text.preferences}</CardTitle><p className="text-sm leading-6 text-muted-foreground">{text.preferencesCopy}</p></CardHeader><CardContent className="space-y-1">{preferenceStatus === 'loading' && <p className="py-6 text-sm text-muted-foreground">{text.loadingPreferences}</p>}{preferenceStatus === 'error' && <div className="py-6"><p className="text-sm text-[var(--color-danger-strong)]">{text.preferencesFailed}</p><Button variant="outline" size="sm" className="mt-3" onClick={loadPreferences}>{text.retry}</Button></div>}{preferenceStatus === 'ready' && preferences.map((preference) => <div key={preference.category} className="flex items-start justify-between gap-4 border-b border-border py-4 last:border-b-0"><div className="min-w-0"><p className="text-sm font-semibold text-foreground">{text.categories[preference.category] ?? preference.category}</p><p className="mt-1 text-xs leading-5 text-muted-foreground">{preference.editable ? text.optional : text.critical}</p></div><div className="flex min-h-11 items-center gap-2">{!preference.editable && <ShieldCheck className="size-4 text-primary" aria-hidden="true" />}<Switch checked={preference.enabled} disabled={!preference.editable || savingCategory === preference.category} onCheckedChange={(enabled) => updatePreference(preference, enabled)} aria-label={text.categories[preference.category] ?? preference.category} /></div></div>)}</CardContent></Card>
            </div>
        </>
    );
}
