import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Building2, Pencil } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DetailCard } from '@/Components/admin/DetailCard';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

export default function CitiesShow({ cityId }) {
    const { admin, common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [city, setCity] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/cities/${cityId}`, { silent: true }).then((res) => {
            setCity(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [cityId]);

    return (
        <AdminLayout title={city?.name ?? admin.cities}>
            <PageHeader
                breadcrumb={[{ label: admin.cities, href: route('admin.cities.index') }, { label: common.view_details ?? 'Details' }]}
                title={
                    <span className="flex items-center gap-3">
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground">
                            <Building2 className="size-5" />
                        </span>
                        {city?.name ?? '—'}
                    </span>
                }
                actions={
                    city && (
                        <Button asChild size="sm">
                            <Link href={route('admin.cities.edit', city.id)}>
                                <Pencil className="size-4" />
                                {common.edit ?? 'Edit'}
                            </Link>
                        </Button>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load city.</p>}

            <div className="grid gap-4 md:grid-cols-2">
                <DetailCard
                    title="City information"
                    isLoading={status === 'loading'}
                    fields={[
                        { label: admin.cities, value: city?.name },
                        { label: common.created_at ?? 'Created at', value: city ? new Date(city.created_at).toLocaleString() : null },
                        { label: common.updated_at ?? 'Updated at', value: city ? new Date(city.updated_at).toLocaleString() : null },
                    ]}
                />
                <DetailCard
                    title="Usage"
                    isLoading={status === 'loading'}
                    fields={[{ label: admin.vendors_assigned_suffix, value: city?.vendors_count ?? 0 }]}
                />
            </div>
        </AdminLayout>
    );
}
