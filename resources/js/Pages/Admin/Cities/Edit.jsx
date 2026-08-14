import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function CitiesEdit({ cityId }) {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [name, setName] = useState('');
    const [vendorsCount, setVendorsCount] = useState(0);
    const [successMessage, setSuccessMessage] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/cities/${cityId}`, { silent: true }).then((res) => {
            setName(res.data.data.name ?? '');
            setVendorsCount(res.data.data.vendors_count ?? 0);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [cityId]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        try {
            const data = await submit('put', `/api/admin/cities/${cityId}`, { name });
            setVendorsCount(data.data?.vendors_count ?? vendorsCount);
            setSuccessMessage(common.saved ?? 'City updated successfully.');
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading ?? 'Loading...'}>
                <Skeleton className="h-56 w-full max-w-xl" />
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title="Edit city">
            <PageHeader breadcrumb={[{ label: admin.cities, href: route('admin.cities.index') }, { label: common.edit ?? 'Edit' }]} title="Edit city" />

            <Card className="max-w-xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                    {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <TextField id="name" label="City name" required value={name} onChange={(e) => setName(e.target.value)} error={errors.name} />

                        <div className="rounded-md border border-border bg-muted px-4 py-3 text-sm text-muted-foreground">
                            {admin.vendors_assigned_suffix}: <span className="font-semibold text-foreground">{vendorsCount}</span>
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.cities.index'))}>
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
