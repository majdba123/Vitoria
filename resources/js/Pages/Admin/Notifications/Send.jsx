import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
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
    const [usersStatus, setUsersStatus] = useState('idle');
    const [selectedUserIds, setSelectedUserIds] = useState([]);
    const [successMessage, setSuccessMessage] = useState(null);

    useEffect(() => {
        if (usersStatus === 'idle') {
            setUsersStatus('loading');
            window.axios.get('/api/admin/users', { params: { per_page: 500 }, silent: true }).then((res) => {
                setUsers(res.data?.data ?? []);
                setUsersStatus('ready');
            }).catch(() => setUsersStatus('error'));
        }
    }, [usersStatus]);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const toggleUser = (id) => setSelectedUserIds((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        const payload = { title: form.title.trim(), body: form.body.trim(), type: 'private', user_ids: selectedUserIds };

        try {
            await submit('post', '/api/admin/notifications/send', payload);
            setSuccessMessage(admin.notification_sent_success ?? 'Notification sent successfully.');
            setForm((f) => ({ ...f, title: '', body: '' }));
            setSelectedUserIds([]);
        } catch {
            // handled by hook
        }
    };

    return (
        <AdminLayout title={admin.send_notification}>
            <PageHeader
                breadcrumb={[{ label: admin.dashboard, href: route('admin.dashboard') }, { label: admin.notifications_log, href: route('admin.notifications.index') }, { label: admin.send_notification }]}
                title={admin.send_notification}
                copy={admin.send_notification_copy}
            />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                    {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <TextField id="title" label={admin.notification_title} required maxLength={255} placeholder={admin.notification_title_placeholder} value={form.title} onChange={(e) => set('title')(e.target.value)} error={errors.title} />
                        <TextareaField id="body" label={admin.notification_message} required maxLength={10000} rows={4} placeholder={admin.notification_message_placeholder} value={form.body} onChange={(e) => set('body')(e.target.value)} error={errors.body} />
                        <div>
                            <label className="mb-1.5 block text-sm font-medium">{admin.notification_recipients} *</label>
                            <p className="mb-2 text-xs text-muted-foreground">{admin.notification_recipients_copy}</p>
                            <select
                                multiple
                                required
                                size={8}
                                className="w-full rounded-md border border-input bg-transparent p-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                value={selectedUserIds.map(String)}
                                onChange={(e) => setSelectedUserIds(Array.from(e.target.selectedOptions).map((o) => parseInt(o.value, 10)))}
                            >
                                {usersStatus === 'loading' && <option value="">{admin.loading}</option>}
                                {usersStatus === 'error' && <option value="">{admin.users_load_failed}</option>}
                                {usersStatus === 'ready' && users.length === 0 && <option value="">{admin.no_users_found}</option>}
                                {usersStatus === 'ready' && users.map((u) => (
                                    <option key={u.id} value={u.id}>{u.name} ({u.phone_number || u.email || '—'})</option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-muted-foreground">{admin.multiselect_hint}</p>
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
