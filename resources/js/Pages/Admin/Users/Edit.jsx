import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

const TYPE_OPTIONS = [
    { value: '0', label: 'User' },
    { value: '1', label: 'Admin' },
    { value: '2', label: 'Vendor' },
    { value: '3', label: 'Syndicate' },
    { value: '4', label: 'Employee' },
];

export default function UsersEdit({ userId }) {
    const { common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [form, setForm] = useState({ name: '', phone_number: '', national_id: '', email: '', password: '', type: '0' });
    const [successMessage, setSuccessMessage] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/users/${userId}`, { silent: true }).then((res) => {
            const u = res.data.data;
            setForm({ name: u.name ?? '', phone_number: u.phone_number ?? '', national_id: u.national_id ?? '', email: u.email ?? '', password: '', type: String(u.type ?? 0) });
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [userId]);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        const payload = { ...form, type: parseInt(form.type, 10) };
        if (!payload.email) delete payload.email;
        if (!payload.password) delete payload.password;
        try {
            await submit('put', `/api/admin/users/${userId}`, payload);
            setSuccessMessage('User updated successfully!');
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading ?? 'Loading...'}>
                <Skeleton className="h-96 w-full max-w-2xl" />
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title="Edit user">
            <PageHeader breadcrumb={[{ label: 'Users', href: route('admin.users.index') }, { label: common.edit ?? 'Edit' }]} title="Edit user" copy="Update user account information." />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                    {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="name" label="Full name" required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                            <TextField id="phone_number" label="Phone number" type="tel" required value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                            <TextField id="national_id" label="National ID" required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                            <TextField id="email" label="Email" type="email" placeholder="(optional)" value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                            <TextField id="password" label="New password" type="password" placeholder="Leave blank to keep current" value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                            <SelectField id="type" label="User type" value={form.type} onValueChange={set('type')} options={TYPE_OPTIONS} error={errors.type} />
                        </div>

                        <div className="flex gap-2 border-t border-border pt-5">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.users.index'))}>
                                {common.cancel ?? 'Cancel'}
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {common.save_changes ?? 'Save changes'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
