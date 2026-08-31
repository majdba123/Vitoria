import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Layers, Pencil } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DetailCard } from '@/Components/shared/DetailCard';
import { Button } from '@/Components/ui/button';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatDate, formatPercent } from '@/lib/date-time';
import { translatedEnum } from '@/lib/translated-enum';

export default function CategoriesShow({ categoryId }) {
    const { admin, common } = useI18n();
    const locale = useLocale();
    const typeLabels = { agriculture: admin.type_agriculture, veterinary: admin.type_veterinary, both: admin.type_both };
    const [status, setStatus] = useState('loading');
    const [category, setCategory] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/categories/${categoryId}`, { silent: true }).then((res) => {
            setCategory(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [categoryId]);

    return (
        <AdminLayout title={category?.name ?? admin.categories}>
            <PageHeader
                breadcrumb={[{ label: admin.categories, href: route('admin.categories.index') }, { label: common.view_details }]}
                title={
                    <span className="flex items-center gap-3">
                        <span className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-md bg-accent text-accent-foreground">
                            {category?.image_url ? <img src={category.image_url} alt="" className="size-full object-cover" /> : <Layers className="size-5" />}
                        </span>
                        {category?.name ?? '—'}
                    </span>
                }
                actions={
                    category && (
                        <Button asChild size="sm">
                            <Link href={route('admin.categories.edit', category.id)}>
                                <Pencil className="size-4" />
                                {common.edit}
                            </Link>
                        </Button>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">{admin.js_failed_load_category}</p>}

            <DetailCard
                isLoading={status === 'loading'}
                columns={2}
                fields={[
                    { label: admin.type_label, value: translatedEnum(category?.type, common.not_available, typeLabels) },
                    { label: admin.category_commission, value: category ? formatPercent(category.commission || 0, locale) : null },
                    { label: admin.products, value: category?.products_count ?? 0 },
                    { label: common.created_label, value: category ? formatDate(category.created_at, locale) : null },
                ]}
            />
        </AdminLayout>
    );
}
