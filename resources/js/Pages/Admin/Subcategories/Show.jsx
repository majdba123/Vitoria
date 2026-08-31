import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DetailCard } from '@/Components/shared/DetailCard';
import { Button } from '@/Components/ui/button';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatDate } from '@/lib/date-time';
import { translatedEnum } from '@/lib/translated-enum';

export default function SubcategoriesShow({ subcategoryId }) {
    const { admin, common } = useI18n();
    const locale = useLocale();
    const typeLabels = { agriculture: admin.type_agriculture, veterinary: admin.type_veterinary, both: admin.type_both };
    const [status, setStatus] = useState('loading');
    const [subcategory, setSubcategory] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/subcategories/${subcategoryId}`, { silent: true }).then((res) => {
            setSubcategory(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [subcategoryId]);

    return (
        <AdminLayout title={subcategory?.name_ar ?? admin.subcategories_heading}>
            <PageHeader
                breadcrumb={[{ label: admin.subcategories_heading, href: route('admin.subcategories.index') }, { label: common.view_details }]}
                title={subcategory?.name_ar ?? '—'}
                copy={subcategory?.name_en}
                actions={
                    subcategory && (
                        <Button asChild size="sm">
                            <Link href={route('admin.subcategories.edit', subcategory.id)}>
                                <Pencil className="size-4" />
                                {common.edit}
                            </Link>
                        </Button>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">{admin.js_failed_load_subcategory}</p>}

            <DetailCard
                isLoading={status === 'loading'}
                columns={2}
                fields={[
                    { label: admin.parent_category_label, value: subcategory?.category?.name },
                    { label: admin.type_label, value: translatedEnum(subcategory?.category?.type, common.not_available, typeLabels) },
                    { label: admin.products, value: subcategory?.products_count ?? 0 },
                    { label: common.created_label, value: subcategory ? formatDate(subcategory.created_at, locale) : null },
                ]}
            />
        </AdminLayout>
    );
}
