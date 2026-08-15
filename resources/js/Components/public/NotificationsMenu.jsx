import { useEffect, useRef, useState } from 'react';
import { Bell, ChevronRight } from 'lucide-react';
import { useI18n } from '@/hooks/use-i18n';

function notificationLink(actionType, actionId) {
    if (!actionType || actionId == null) return null;
    const id = String(actionId);
    if (actionType === 'product') return `/products/${id}`;
    if (actionType === 'order') return `/orders/${id}`;
    return null;
}

export function NotificationsMenu() {
    const { nav, common } = useI18n();
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState([]);
    const [status, setStatus] = useState('idle');
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [unread, setUnread] = useState(0);
    const ref = useRef(null);

    const loadBadge = () => {
        window.axios.get('/api/notifications', { params: { per_page: 1 }, silent: true }).then((res) => {
            setUnread(res.data?.unread_count ?? 0);
        }).catch(() => {});
    };

    useEffect(() => { loadBadge(); }, []);

    useEffect(() => {
        if (!open) return;
        setStatus('loading');
        window.axios.get('/api/notifications', { params: { page }, silent: true }).then((res) => {
            setItems(res.data?.data ?? []);
            setMeta(res.data?.meta ?? { current_page: 1, last_page: 1, total: 0 });
            setUnread(res.data?.unread_count ?? 0);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [open, page]);

    useEffect(() => {
        if (!open) return;
        const onClick = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('click', onClick);
        return () => document.removeEventListener('click', onClick);
    }, [open]);

    const markOne = (id) => {
        window.axios.patch(`/api/notifications/${id}/read`, {}, { silent: true }).then(() => {
            setItems((rows) => rows.map((r) => (r.id === id ? { ...r, read_at: new Date().toISOString() } : r)));
            setUnread((u) => Math.max(0, u - 1));
        });
    };

    const markAll = () => {
        window.axios.post('/api/notifications/mark-all-read', {}, { silent: true }).then(() => {
            setItems((rows) => rows.map((r) => ({ ...r, read_at: r.read_at ?? new Date().toISOString() })));
            setUnread(0);
        });
    };

    return (
        <div className="relative" ref={ref}>
            <button type="button" onClick={() => setOpen((v) => !v)} className="nav-action-btn relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-transparent text-muted-foreground" aria-label={nav.notifications_aria} title={nav.notifications_aria}>
                <Bell className="h-5 w-5" />
                {unread > 0 && (
                    <span className="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[var(--color-warning-500)] px-1 text-[10px] font-bold leading-none text-white shadow rtl:-left-0.5 rtl:right-auto">
                        {unread > 99 ? '99+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <div className="dropdown-panel absolute top-full z-50 mt-2 flex max-h-[min(32rem,75vh)] w-[min(420px,95vw)] flex-col ltr:right-0 rtl:left-0">
                    <div className="flex shrink-0 items-center justify-between border-b border-border/80 px-4 py-3">
                        <span className="text-[13px] font-medium uppercase tracking-wider text-muted-foreground">{nav.notifications}</span>
                        <button type="button" onClick={markAll} className="text-[11px] font-medium uppercase tracking-wider text-primary hover:underline">{nav.mark_all_read}</button>
                    </div>
                    <div className="min-h-0 flex-1 overflow-y-auto">
                        {status === 'loading' && <p className="px-4 py-10 text-center text-[13px] text-muted-foreground">{common.loading}</p>}
                        {status === 'error' && <p className="px-4 py-6 text-center text-sm text-[var(--color-danger-strong)]">{common.failed_notifications}</p>}
                        {status === 'ready' && items.length === 0 && <p className="px-4 py-12 text-center text-[13px] text-muted-foreground">{nav.no_notifications}</p>}
                        {status === 'ready' && items.map((n) => {
                            const isUnread = !n.read_at;
                            const href = notificationLink(n.action_type, n.action_id);
                            const time = n.sent_at ? new Date(n.sent_at).toLocaleDateString(undefined, { dateStyle: 'short', timeStyle: 'short' }) : '';
                            const Wrapper = href ? 'a' : 'div';
                            return (
                                <Wrapper key={n.id} href={href ?? undefined} className={`flex border-b border-border/60 last:border-0 ${isUnread ? 'bg-accent/30' : 'hover:bg-accent/20'}`}>
                                    {isUnread && <div className="w-0.5 shrink-0 self-stretch bg-primary" />}
                                    <div className={`min-w-0 flex-1 py-3.5 ${isUnread ? 'ps-3.5' : 'ps-4'} pe-4`}>
                                        <p className="text-[14px] leading-relaxed text-foreground">{n.body}</p>
                                        <p className="mt-1.5 text-[11px] text-muted-foreground">{time}{n.sender_name ? ` · ${n.sender_name}` : ''}</p>
                                        {isUnread && (
                                            <button type="button" onClick={(e) => { e.preventDefault(); markOne(n.id); }} className="mt-2 text-[11px] font-medium text-primary hover:underline">
                                                {nav.mark_notification_read}
                                            </button>
                                        )}
                                    </div>
                                    {href && <ChevronRight className="me-4 mt-3.5 size-4 shrink-0 text-muted-foreground/50 rtl:rotate-180" />}
                                </Wrapper>
                            );
                        })}
                    </div>
                    {meta.last_page > 1 && (
                        <div className="flex shrink-0 items-center justify-between gap-2 border-t border-border/80 bg-muted/40 px-3 py-2">
                            <button type="button" disabled={meta.current_page <= 1} onClick={() => setPage((p) => p - 1)} className="rounded-md px-3 py-1.5 text-xs font-medium text-foreground hover:bg-accent disabled:pointer-events-none disabled:opacity-50">{nav.prev}</button>
                            <span className="text-[11px] text-muted-foreground">{nav.page} {meta.current_page} {nav.of} {meta.last_page}</span>
                            <button type="button" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)} className="rounded-md px-3 py-1.5 text-xs font-medium text-foreground hover:bg-accent disabled:pointer-events-none disabled:opacity-50">{nav.next}</button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
