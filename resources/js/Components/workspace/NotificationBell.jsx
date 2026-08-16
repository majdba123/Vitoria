import { useEffect, useState } from 'react';
import { Bell, CheckCheck, Loader2 } from 'lucide-react';
import { Link } from '@inertiajs/react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

function timeAgo(iso, locale) {
    const diffMs = Date.now() - new Date(iso).getTime();
    const minutes = Math.round(diffMs / 60000);
    const rtf = new Intl.RelativeTimeFormat(locale === 'ar' ? 'ar' : 'en', { numeric: 'auto' });

    if (minutes < 60) return rtf.format(-minutes, 'minute');
    const hours = Math.round(minutes / 60);
    if (hours < 24) return rtf.format(-hours, 'hour');
    const days = Math.round(hours / 24);

    return rtf.format(-days, 'day');
}

/**
 * Notification bell shared by every operator workspace. Mirrors
 * components/workspace/notification-dropdown.blade.php's data contract
 * (/api/notifications) but re-implemented as React state instead of
 * id-keyed DOM manipulation.
 */
export function NotificationBell({ viewAllRoute = 'admin.notifications.index', locale = 'en', group = 'admin' }) {
    const i18n = useI18n();
    const common = i18n.common;
    // Every workspace's lang file (admin.php, vendor.php, ...) declares the
    // same notification-copy keys under its own group, so the bell can be
    // reused across portals by just switching which bucket it reads.
    const admin = { ...i18n.admin, ...(i18n[group] ?? {}) };
    const notificationsLabel = admin.notifications_log ?? admin.notifications ?? 'Notifications';
    const [open, setOpen] = useState(false);
    const [status, setStatus] = useState('idle'); // idle | loading | ready | error
    const [items, setItems] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);

    const fetchBadge = () => {
        window.axios
            .get('/api/notifications', { params: { per_page: 1 }, silent: true })
            .then((res) => setUnreadCount(res.data?.unread_count ?? 0))
            .catch(() => {});
    };

    useEffect(() => {
        fetchBadge();
        const refresh = () => {
            fetchBadge();
            if (open) load();
        };
        window.addEventListener('vetora:notifications-updated', refresh);

        return () => window.removeEventListener('vetora:notifications-updated', refresh);
        // The listener is intentionally registered once; open state is not
        // required for badge accuracy and the dropdown refreshes when opened.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const load = () => {
        setStatus('loading');
        window.axios
            .get('/api/notifications', { params: { per_page: 8 }, silent: true })
            .then((res) => {
                setItems(res.data?.data ?? []);
                setUnreadCount(res.data?.unread_count ?? 0);
                setStatus('ready');
            })
            .catch(() => setStatus('error'));
    };

    const handleOpenChange = (next) => {
        setOpen(next);
        if (next) load();
    };

    const markAllRead = () => {
        window.axios.post('/api/notifications/mark-all-read', {}, { silent: true }).then(() => {
            setItems((current) => current.map((item) => ({ ...item, read_at: item.read_at ?? new Date().toISOString() })));
            setUnreadCount(0);
        });
    };

    const markRead = (id) => {
        window.axios.patch(`/api/notifications/${id}/read`, {}, { silent: true }).then(() => {
            setItems((current) => current.map((item) => (item.id === id ? { ...item, read_at: new Date().toISOString() } : item)));
            setUnreadCount((count) => Math.max(0, count - 1));
        });
    };

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="relative"
                    aria-label={notificationsLabel}
                    title={notificationsLabel}
                >
                    <Bell className="size-4" />
                    {unreadCount > 0 && (
                        <span className="absolute top-1 end-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-warning-500)] px-1 text-[10px] font-bold leading-none text-white">
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-[min(22rem,90vw)] p-0">
                <div className="flex items-center justify-between border-b px-4 py-3">
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        {notificationsLabel}
                    </span>
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider text-primary hover:underline"
                        >
                            <CheckCheck className="size-3.5" />
                            {admin.mark_all_read ?? 'Mark all as read'}
                        </button>
                    </div>
                </div>

                <div className="max-h-96 overflow-y-auto">
                    {status === 'loading' && (
                        <div className="flex items-center justify-center gap-2 px-4 py-10 text-sm text-muted-foreground">
                            <Loader2 className="size-4 animate-spin" />
                            {admin.loading ?? 'Loading...'}
                        </div>
                    )}

                    {status === 'error' && (
                        <div className="px-4 py-8 text-center text-sm text-muted-foreground">
                            <p>{admin.dashboard_load_failed ?? admin.failed_notifications ?? 'Failed to load.'}</p>
                            <button type="button" onClick={load} className="mt-2 font-semibold text-primary underline">
                                {common.refresh ?? 'Retry'}
                            </button>
                        </div>
                    )}

                    {status === 'ready' && items.length === 0 && (
                        <p className="px-4 py-10 text-center text-sm text-muted-foreground">{admin.no_notifications ?? 'No notifications.'}</p>
                    )}

                    {status === 'ready' &&
                        items.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => !item.read_at && markRead(item.id)}
                                className="flex w-full flex-col gap-0.5 border-b px-4 py-3 text-start last:border-b-0 hover:bg-accent"
                            >
                                <span className="flex items-center gap-2">
                                    {!item.read_at && <span className="size-1.5 shrink-0 rounded-full bg-primary" aria-hidden="true" />}
                                    <span className={item.read_at ? 'truncate text-sm text-foreground' : 'truncate text-sm font-semibold text-foreground'}>
                                        {item.title}
                                    </span>
                                </span>
                                {item.body && <span className="truncate text-xs text-muted-foreground">{item.body}</span>}
                                <span className="text-[11px] text-muted-foreground">{timeAgo(item.sent_at, locale)}</span>
                            </button>
                        ))}
                </div>

                <Link
                    href={route(viewAllRoute)}
                    className="block border-t px-4 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-primary hover:bg-accent"
                >
                    {admin.view_all ?? 'View all'}
                </Link>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
