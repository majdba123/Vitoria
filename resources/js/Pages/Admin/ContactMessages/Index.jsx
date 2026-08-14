import { useState } from 'react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { Pagination } from '@/Components/admin/Pagination';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Card } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { TextareaField } from '@/Components/admin/form/FormField';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n } from '@/hooks/use-i18n';

export default function ContactMessagesIndex() {
    const { admin, common } = useI18n();
    const [page, setPage] = useState(1);
    const [statusFilter, setStatusFilter] = useState(() => new URLSearchParams(window.location.search).get('status') || 'all');
    const [replyTarget, setReplyTarget] = useState(null);
    const [replyText, setReplyText] = useState('');
    const [replyError, setReplyError] = useState(null);
    const [isSending, setIsSending] = useState(false);

    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/admin/contact-messages', {
        page,
        per_page: 15,
        status: statusFilter === 'all' ? undefined : statusFilter,
    });

    const openReply = (message) => {
        setReplyTarget(message);
        setReplyText('');
        setReplyError(null);
    };

    const sendReply = () => {
        if (!replyText.trim()) {
            setReplyError(admin.js_please_enter_reply ?? 'Please enter a reply.');
            return;
        }
        setIsSending(true);
        window.axios.patch(`/api/admin/contact-messages/${replyTarget.id}/reply`, { admin_reply: replyText.trim() }, { silent: true }).then(() => {
            setIsSending(false);
            setReplyTarget(null);
            reload();
        }).catch((error) => {
            setIsSending(false);
            setReplyError(error.response?.data?.errors?.admin_reply?.[0] ?? error.response?.data?.message ?? admin.js_failed_send_reply ?? 'Failed to send reply.');
        });
    };

    return (
        <AdminLayout title={admin.contact_messages}>
            <PageHeader
                title={admin.contact_messages}
                copy={admin.contact_messages_copy}
                actions={
                    <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); setPage(1); }}>
                        <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{admin.all_statuses}</SelectItem>
                            <SelectItem value="pending">{common.pending}</SelectItem>
                            <SelectItem value="replied">{admin.status_replied}</SelectItem>
                        </SelectContent>
                    </Select>
                }
            />

            {status === 'loading' && <p className="py-14 text-center text-sm text-muted-foreground">{common.loading}</p>}
            {status === 'error' && <p className="py-14 text-center text-sm font-medium text-[var(--color-danger-strong)]">{errorMessage ?? 'Failed to load.'}</p>}
            {status === 'ready' && rows.length === 0 && <p className="py-14 text-center text-sm font-medium text-muted-foreground">{admin.no_contact_messages_yet}</p>}

            {status === 'ready' && rows.length > 0 && (
                <Card className="gap-0 border-border/80 py-0 shadow-none">
                    <ul className="divide-y divide-border">
                        {rows.map((m) => (
                            <li key={m.id} className="px-4 py-3.5 hover:bg-accent/30">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium text-foreground">{m.name || m.user?.name || '—'} &lt;{m.email || m.user?.email || '—'}&gt;</p>
                                        <p className="mt-1 text-xs text-muted-foreground">{m.created_at ? new Date(m.created_at).toLocaleString() : '—'}</p>
                                        <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">{m.message}</p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        {m.status === 'replied' ? (
                                            <StatusBadge tone="success">{admin.status_replied}</StatusBadge>
                                        ) : (
                                            <>
                                                <StatusBadge tone="warning">{common.pending}</StatusBadge>
                                                <Button variant="outline" size="sm" onClick={() => openReply(m)}>{admin.reply}</Button>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                    <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                </Card>
            )}

            <Dialog open={!!replyTarget} onOpenChange={(open) => !open && setReplyTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{admin.reply_to_message}</DialogTitle>
                    </DialogHeader>
                    {replyTarget && (
                        <div className="rounded-md border border-border bg-muted/40 p-3 text-sm text-muted-foreground">
                            <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">{admin.from_label} {replyTarget.email || replyTarget.user?.email}</p>
                            <p className="mt-1.5">{replyTarget.message}</p>
                        </div>
                    )}
                    <TextareaField id="admin_reply" label={admin.your_reply} rows={4} required maxLength={5000} placeholder={admin.type_your_reply} value={replyText} onChange={(e) => setReplyText(e.target.value)} error={replyError} />
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setReplyTarget(null)}>{common.cancel ?? 'Cancel'}</Button>
                        <Button type="button" onClick={sendReply} disabled={isSending}>
                            {isSending && <Loader2 className="size-4 animate-spin" />}
                            {admin.send_reply}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
