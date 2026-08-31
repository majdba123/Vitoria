import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { TextField, SelectField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function UsersCreate() {
    const { admin, common } = useI18n();
    const initialType = new URLSearchParams(window.location.search).get('type') === '4' ? '4' : '0';
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [form, setForm] = useState({ name: '', phone_number: '', national_id: '', email: '', password: '', type: initialType });

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        const payload = { ...form, type: parseInt(form.type, 10) };
        if (!payload.email) delete payload.email;
        try {
            await submit('post', '/api/admin/users', payload);
            router.visit(route('admin.users.index'));
        } catch {
            // handled by hook
        }
    };

    const [vendorHintBefore, vendorHintAfter] = admin.vendor_hint_copy.split(':link');

    return (
        <AdminLayout title={admin.add_user}>
            <PageHeader breadcrumb={[{ label: admin.users, href: route('admin.users.index') }, { label: admin.create }]} title={admin.create_new_user_title} copy={admin.create_new_user_copy} />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="name" label={admin.full_name_label} required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                            <TextField id="phone_number" label={admin.phone_number_label} type="tel" required placeholder="09XXXXXXXX" value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                            <TextField id="national_id" label={admin.th_national_id} required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                            <TextField id="email" label={admin.email_label} type="email" placeholder={admin.email_optional_placeholder} value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                            <TextField id="password" label={admin.password_label} type="password" required placeholder={admin.min_characters_placeholder} value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                            <SelectField
                                id="type"
                                label={admin.user_type_label}
                                required
                                value={form.type}
                                onValueChange={set('type')}
                                options={[{ value: '0', label: admin.user_type_labels?.[0] }, { value: '1', label: admin.user_type_labels?.[1] }, { value: '4', label: admin.user_type_labels?.[4] }]}
                                error={errors.type}
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {vendorHintBefore}<Link href={route('admin.vendors.create')} className="text-primary underline">{admin.add_vendor}</Link>{vendorHintAfter}
                        </p>

                        <div className="flex gap-2 border-t border-border pt-5">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.users.index'))}>
                                {common.cancel}
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {admin.create_user_btn}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
