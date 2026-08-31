import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Building2, Pencil } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DetailCard } from '@/Components/shared/DetailCard';
import { Button } from '@/Components/ui/button';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatDateTime } from '@/lib/date-time';

export default function CitiesShow({ cityId }) {
    const { admin, common } = useI18n();
    const locale = useLocale();
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
                breadcrumb={[{ label: admin.cities, href: route('admin.cities.index') }, { label: common.view_details }]}
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
                                {common.edit}
                            </Link>
                        </Button>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">{admin.js_failed_load_city}</p>}

            <div className="grid gap-4 md:grid-cols-2">
                <DetailCard
                    title={admin.city_information_title}
                    isLoading={status === 'loading'}
                    fields={[
                        { label: admin.cities, value: city?.name },
                        { label: admin.category_created_label, value: city ? formatDateTime(city.created_at, locale) : null },
                        { label: admin.category_updated_label, value: city ? formatDateTime(city.updated_at, locale) : null },
                    ]}
                />
                <DetailCard
                    title={admin.usage_title}
                    isLoading={status === 'loading'}
                    fields={[{ label: admin.vendors_assigned_suffix, value: city?.vendors_count ?? 0 }]}
                />
            </div>
        </AdminLayout>
    );
}
