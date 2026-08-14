import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField, FileField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function SyndicatesCreate() {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [form, setForm] = useState({ name: '', email: '', phone: '', type: 'agriculture', password: '', password_confirmation: '', status: 'active', logo: null });
    const [preview, setPreview] = useState(null);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        try {
            const data = await submit('post', '/api/admin/syndicates', form, { isMultipart: true });
            router.visit(route('admin.syndicates.show', data.data.id));
        } catch {
            // handled by hook
        }
    };

    return (
        <AdminLayout title={admin.add_syndicate_agent}>
            <PageHeader breadcrumb={[{ label: admin.syndicate_agents_heading, href: route('admin.syndicates.index') }, { label: admin.add_syndicate_agent }]} title={admin.add_syndicate_agent} />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="name" label={admin.name_label} required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                            <TextField id="email" label="Email" type="email" required value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                            <TextField id="phone" label="Phone" required value={form.phone} onChange={(e) => set('phone')(e.target.value)} error={errors.phone} />
                            <SelectField id="type" label={admin.type_label} required value={form.type} onValueChange={set('type')} options={[{ value: 'agriculture', label: admin.type_agriculture }, { value: 'veterinary', label: admin.type_veterinary }]} error={errors.type} />
                            <TextField id="password" label="Password" type="password" required autoComplete="new-password" value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                            <TextField id="password_confirmation" label="Confirm password" type="password" required autoComplete="new-password" value={form.password_confirmation} onChange={(e) => set('password_confirmation')(e.target.value)} error={errors.password_confirmation} />
                            <SelectField id="status" label={admin.status_label} required value={form.status} onValueChange={set('status')} options={[{ value: 'active', label: common.active }, { value: 'inactive', label: common.inactive }]} error={errors.status} />
                            <FileField
                                id="logo"
                                label="Image (optional)"
                                hint="Upload one image for this syndicate. Max size: 4MB."
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
                                {common.cancel ?? 'Cancel'}
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {admin.add_syndicate_agent}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
