import { Link } from '@inertiajs/react';
import { Plus, Layers } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { CsvImportButton } from '@/Components/admin/CsvImportButton';
import { Button } from '@/Components/ui/button';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n } from '@/hooks/use-i18n';

export default function CategoriesIndex() {
    const { admin, common } = useI18n();
    const { status, rows, errorMessage, reload } = useAdminList('/api/admin/categories', { per_page: 100 });

    const columns = [
        {
            key: 'name',
            label: admin.category_name,
            render: (row) => (
                <span className="flex items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-accent text-accent-foreground">
                        {row.image_url ? <img src={row.image_url} alt="" className="size-full object-cover" /> : <Layers className="size-4" />}
                    </span>
                    <span className="truncate">{row.name}</span>
                </span>
            ),
        },
        { key: 'type', label: admin.type_label, render: (row) => row.type ?? '—' },
        { key: 'commission', label: admin.category_commission, render: (row) => `${row.commission || 0}%` },
        { key: 'products_count', label: admin.products, align: 'end', render: (row) => row.products_count ?? 0 },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.categories.show', row.id)}>{common.view_details ?? 'View'}</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.categories.edit', row.id)}>{common.edit ?? 'Edit'}</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.categories}>
            <PageHeader
                title={admin.categories}
                copy={admin.manage_categories_copy}
                actions={
                    <>
                        <CsvImportButton
                            label={admin.categories}
                            templateUrl="/api/admin/categories/import/template"
                            importUrl="/api/admin/categories/import"
                            onImported={reload}
                        />
                        <Button asChild size="sm">
                            <Link href={route('admin.categories.create')}>
                                <Plus className="size-4" />
                                {admin.add_category}
                            </Link>
                        </Button>
                    </>
                }
            />

            <DataTable
                columns={columns}
                rows={rows}
                status={status}
                errorMessage={errorMessage}
                onRetry={reload}
                emptyTitle={admin.categories_empty_title}
                emptyHint={admin.categories_empty_hint}
            />
        </AdminLayout>
    );
}
