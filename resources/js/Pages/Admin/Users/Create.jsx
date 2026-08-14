import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';

export default function UsersCreate() {
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

    return (
        <AdminLayout title="Add user">
            <PageHeader breadcrumb={[{ label: 'Users', href: route('admin.users.index') }, { label: 'Create' }]} title="Create new user" copy="Create a new user account with credentials." />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="name" label="Full name" required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                            <TextField id="phone_number" label="Phone number" type="tel" required placeholder="09XXXXXXXX" value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                            <TextField id="national_id" label="National ID" required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                            <TextField id="email" label="Email" type="email" placeholder="(optional)" value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                            <TextField id="password" label="Password" type="password" required placeholder="Min 6 characters" value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                            <SelectField
                                id="type"
                                label="User type"
                                required
                                value={form.type}
                                onValueChange={set('type')}
                                options={[{ value: '0', label: 'User' }, { value: '1', label: 'Admin' }, { value: '4', label: 'Employee' }]}
                                error={errors.type}
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">
                            To create a vendor account, use <Link href={route('admin.vendors.create')} className="text-primary underline">Add Vendor</Link> instead — it creates the store profile this form doesn't.
                        </p>

                        <div className="flex gap-2 border-t border-border pt-5">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.users.index'))}>
                                Cancel
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                Create user
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
