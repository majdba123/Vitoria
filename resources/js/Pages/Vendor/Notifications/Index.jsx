import { useState } from 'react';
import { ChevronRight } from 'lucide-react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { Pagination } from '@/Components/admin/Pagination';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n } from '@/hooks/use-i18n';

function actionHref(actionType, actionId) {
    if (!actionType || actionId == null) return null;
    if (actionType === 'product') return `/products/${actionId}`;
    if (actionType === 'order') return `/vendor/orders/${actionId}`;
    return null;
}

export default function VendorNotificationsIndex() {
    const { vendor, nav } = useI18n();
    const [page, setPage] = useState(1);
    const [markingAll, setMarkingAll] = useState(false);
    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/notifications', { page, per_page: 15 });

    const markAllRead = () => {
        setMarkingAll(true);
        window.axios.post('/api/notifications/mark-all-read', {}, { silent: true }).then(() => {
            setMarkingAll(false);
            reload();
        }).catch(() => setMarkingAll(false));
    };

    const markOneRead = (id, event) => {
        event.preventDefault();
        event.stopPropagation();
        window.axios.patch(`/api/notifications/${id}/read`, {}, { silent: true }).then(reload);
    };

    return (
        <VendorLayout title={vendor.notifications_title}>
            <PageHeader
                title={vendor.notifications_title}
                copy={vendor.notifications_copy}
                actions={
                    <Button variant="outline" size="sm" onClick={markAllRead} disabled={markingAll}>
                        {nav.mark_all_read}
                    </Button>
                }
            />

            {status === 'loading' && (
                <Card className="border-border/80 shadow-none">
                    <CardContent className="space-y-3 p-5">
                        {[0, 1, 2, 3].map((i) => <Skeleton key={i} className="h-14 w-full" />)}
                    </CardContent>
                </Card>
            )}

            {status === 'error' && (
                <Card className="border-border/80 shadow-none">
                    <CardContent className="py-14 text-center">
                        <p className="text-sm font-medium text-[var(--color-danger-strong)]">{errorMessage ?? nav.failed_show_notifications}</p>
                        <Button variant="outline" size="sm" className="mt-3" onClick={reload}>Retry</Button>
                    </CardContent>
                </Card>
            )}

            {status === 'ready' && rows.length === 0 && (
                <Card className="border-border/80 shadow-none">
                    <CardContent className="py-14 text-center">
                        <p className="text-sm font-medium text-muted-foreground">{nav.no_notifications}</p>
                    </CardContent>
                </Card>
            )}

            {status === 'ready' && rows.length > 0 && (
                <Card className="gap-0 border-border/80 py-0 shadow-none">
                    <ul className="divide-y divide-border">
                        {rows.map((n) => {
                            const isUnread = !n.read_at;
                            const href = actionHref(n.action_type, n.action_id);
                            const time = n.sent_at ? new Date(n.sent_at).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' }) : '';
                            const Wrapper = href ? 'a' : 'div';

                            return (
                                <li key={n.id} className={isUnread ? 'bg-accent/30' : ''}>
                                    <Wrapper href={href ?? undefined} className="flex items-start gap-3 px-4 py-3.5">
                                        <span className={`mt-1.5 size-2 shrink-0 rounded-full ${isUnread ? 'bg-primary' : 'bg-muted-foreground/30'}`} />
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium text-foreground">{n.body}</p>
                                            <p className="mt-1 text-xs text-muted-foreground">{time}{n.sender_name ? ` · ${n.sender_name}` : ''}</p>
                                            {isUnread && (
                                                <button type="button" onClick={(e) => markOneRead(n.id, e)} className="mt-2 text-xs font-medium text-primary hover:underline">
                                                    {nav.mark_notification_read}
                                                </button>
                                            )}
                                        </div>
                                        {href && <ChevronRight className="size-4 shrink-0 text-muted-foreground rtl:rotate-180" />}
                                    </Wrapper>
                                </li>
                            );
                        })}
                    </ul>
                    <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                </Card>
            )}
        </VendorLayout>
    );
}
