import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { TextField, TextareaField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function NotificationsSend() {
    const { admin } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [form, setForm] = useState({ title: '', body: '' });
    const [users, setUsers] = useState([]);
    const [recipientSearch, setRecipientSearch] = useState('');
    const [usersStatus, setUsersStatus] = useState('loading');
    const [selectedUsers, setSelectedUsers] = useState({});
    const [successMessage, setSuccessMessage] = useState(null);
    const selectedUserIds = Object.keys(selectedUsers).map(Number);

    useEffect(() => {
        let isCurrentRequest = true;
        const search = recipientSearch.trim();
        const timeout = window.setTimeout(() => {
            setUsersStatus('loading');
            window.axios.get('/api/admin/users', { params: { per_page: 100, search: search || undefined }, silent: true }).then((res) => {
                if (!isCurrentRequest) return;
                setUsers(res.data?.data ?? []);
                setUsersStatus('ready');
            }).catch(() => {
                if (isCurrentRequest) setUsersStatus('error');
            });
        }, 250);

        return () => {
            isCurrentRequest = false;
            window.clearTimeout(timeout);
        };
    }, [recipientSearch]);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const toggleUser = (id) => setSelectedUserIds((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        const payload = { title: form.title.trim(), body: form.body.trim(), type: 'private', user_ids: selectedUserIds };

        try {
            await submit('post', '/api/admin/notifications/send', payload);
            setSuccessMessage(admin.notification_sent_success);
            setForm((f) => ({ ...f, title: '', body: '' }));
            setSelectedUsers({});
        } catch {
            // handled by hook
        }
    };

    return (
        <AdminLayout title={admin.send_notification}>
            <PageHeader
                breadcrumb={[{ label: admin.dashboard, href: route('admin.home') }, { label: admin.notifications_log, href: route('admin.notifications.index') }, { label: admin.send_notification }]}
                title={admin.send_notification}
            />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                    {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <TextField id="title" label={admin.notification_title} required maxLength={255} placeholder={admin.notification_title_placeholder} value={form.title} onChange={(e) => set('title')(e.target.value)} error={errors.title} />
                        <TextareaField id="body" label={admin.notification_message} required maxLength={10000} rows={4} placeholder={admin.notification_message_placeholder} value={form.body} onChange={(e) => set('body')(e.target.value)} error={errors.body} />
                        <div className="flex flex-col gap-3">
                            <TextField
                                id="recipient-search"
                                type="search"
                                label={admin.notification_recipient_search}
                                placeholder={admin.notification_recipient_search_placeholder}
                                value={recipientSearch}
                                onChange={(event) => setRecipientSearch(event.target.value)}
                            />
                            <label htmlFor="notification-recipients" className="text-sm font-medium">
                                {admin.notification_recipients} *
                            </label>
                            <select
                                id="notification-recipients"
                                multiple
                                required
                                size={8}
                                disabled={usersStatus !== 'ready'}
                                className="w-full rounded-md border border-input bg-background p-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-wait disabled:opacity-60"
                                value={users.filter((user) => selectedUsers[user.id]).map((user) => String(user.id))}
                                onChange={(event) => {
                                    const selectedVisibleIds = new Set(Array.from(event.target.selectedOptions).map((option) => Number(option.value)));
                                    setSelectedUsers((current) => {
                                        const next = { ...current };
                                        users.forEach((user) => {
                                            if (selectedVisibleIds.has(user.id)) {
                                                next[user.id] = user;
                                            } else {
                                                delete next[user.id];
                                            }
                                        });

                                        return next;
                                    });
                                }}
                            >
                                {usersStatus === 'loading' && <option value="">{admin.loading}</option>}
                                {usersStatus === 'error' && <option value="">{admin.users_load_failed}</option>}
                                {usersStatus === 'ready' && users.length === 0 && <option value="">{admin.no_users_found}</option>}
                                {usersStatus === 'ready' && users.map((u) => (
                                    <option key={u.id} value={u.id}>{u.name} ({u.phone_number || u.email || '—'})</option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-muted-foreground">{admin.multiselect_hint}</p>
                            {selectedUserIds.length > 0 && (
                                <div aria-live="polite" className="flex flex-col gap-2">
                                    <p className="text-sm font-medium">{admin.selected_recipients} ({selectedUserIds.length})</p>
                                    <ul className="flex flex-wrap gap-2">
                                        {Object.values(selectedUsers).map((user) => (
                                            <li key={user.id} className="flex max-w-full items-center gap-2 rounded-full border border-border bg-muted/50 py-1 ps-3 pe-1 text-sm">
                                                <span className="truncate">{user.name}</span>
                                                <button
                                                    type="button"
                                                    aria-label={`${admin.remove_recipient}: ${user.name}`}
                                                    onClick={() => setSelectedUsers((current) => {
                                                        const next = { ...current };
                                                        delete next[user.id];

                                                        return next;
                                                    })}
                                                    className="rounded-full px-2 py-0.5 text-muted-foreground transition-colors hover:bg-background hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                >
                                                    ×
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            {errors.user_ids && <p className="mt-1.5 text-xs font-medium text-[var(--color-danger-strong)]">{errors.user_ids}</p>}
                        </div>

                        <div className="flex justify-end gap-2 border-t border-border pt-5">
                            <Button type="submit" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {admin.send_notification}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
