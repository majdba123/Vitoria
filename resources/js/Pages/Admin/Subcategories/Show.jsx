import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DetailCard } from '@/Components/admin/DetailCard';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

export default function SubcategoriesShow({ subcategoryId }) {
    const { admin, common } = useI18n();
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
                breadcrumb={[{ label: admin.subcategories_heading, href: route('admin.subcategories.index') }, { label: common.view_details ?? 'Details' }]}
                title={subcategory?.name_ar ?? '—'}
                copy={subcategory?.name_en}
                actions={
                    subcategory && (
                        <Button asChild size="sm">
                            <Link href={route('admin.subcategories.edit', subcategory.id)}>
                                <Pencil className="size-4" />
                                {common.edit ?? 'Edit'}
                            </Link>
                        </Button>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load subcategory.</p>}

            <DetailCard
                isLoading={status === 'loading'}
                columns={2}
                fields={[
                    { label: admin.parent_category_label, value: subcategory?.category?.name },
                    { label: admin.type_label, value: subcategory?.category?.type },
                    { label: admin.products, value: subcategory?.products_count ?? 0 },
                    { label: common.created_at ?? 'Created', value: subcategory ? new Date(subcategory.created_at).toLocaleDateString() : null },
                ]}
            />
        </AdminLayout>
    );
}
