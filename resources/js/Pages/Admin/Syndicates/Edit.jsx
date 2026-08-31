import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { TextField, SelectField, FileField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function SyndicatesEdit({ syndicateId }) {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [form, setForm] = useState({ name: '', email: '', phone: '', type: 'agriculture', password: '', password_confirmation: '', status: 'active', logo: null });
    const [preview, setPreview] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/syndicates/${syndicateId}`, { silent: true }).then((res) => {
            const s = res.data.data;
            setForm({ name: s.name ?? '', email: s.email ?? '', phone: s.phone ?? '', type: s.type ?? 'agriculture', password: '', password_confirmation: '', status: s.status ?? 'active', logo: null });
            setPreview(s.logo_url ?? null);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [syndicateId]);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        const payload = { ...form };
        if (!payload.password) {
            delete payload.password;
            delete payload.password_confirmation;
        }
        try {
            await submit('put', `/api/admin/syndicates/${syndicateId}`, payload, { isMultipart: true });
            router.visit(route('admin.syndicates.show', syndicateId));
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading}>
                <Skeleton className="h-96 w-full max-w-2xl" />
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title={admin.edit_syndicate_title}>
            <PageHeader breadcrumb={[{ label: admin.syndicate_agents_heading, href: route('admin.syndicates.index') }, { label: common.edit }]} title={admin.edit_syndicate_title} />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="name" label={admin.name_label} required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                            <TextField id="email" label={admin.email_label} type="email" required value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                            <TextField id="phone" label={admin.phone_label} required value={form.phone} onChange={(e) => set('phone')(e.target.value)} error={errors.phone} />
                            <SelectField id="type" label={admin.type_label} required value={form.type} onValueChange={set('type')} options={[{ value: 'agriculture', label: admin.type_agriculture }, { value: 'veterinary', label: admin.type_veterinary }]} error={errors.type} />
                            <TextField id="password" label={admin.new_password_label} type="password" autoComplete="new-password" placeholder={admin.password_optional_hint} value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                            <TextField id="password_confirmation" label={admin.confirm_new_password_label} type="password" autoComplete="new-password" value={form.password_confirmation} onChange={(e) => set('password_confirmation')(e.target.value)} error={errors.password_confirmation} />
                            <SelectField id="status" label={admin.status_label} required value={form.status} onValueChange={set('status')} options={[{ value: 'active', label: common.active }, { value: 'inactive', label: common.inactive }]} error={errors.status} />
                            <FileField
                                id="logo"
                                label={admin.image_optional_label}
                                preview={preview}
                                onChange={(e) => {
                                    const file = e.target.files?.[0] ?? null;
                                    set('logo')(file);
                                    if (file) setPreview(URL.createObjectURL(file));
                                }}
                                error={errors.logo}
                            />
                        </div>

                        <div className="flex gap-2 border-t border-border pt-5">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.syndicates.index'))}>
                                {common.cancel}
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {common.save_changes}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
