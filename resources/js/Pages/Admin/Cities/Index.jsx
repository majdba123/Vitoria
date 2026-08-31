import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, Building2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { CsvImportButton } from '@/Components/admin/CsvImportButton';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n } from '@/hooks/use-i18n';

export default function CitiesIndex() {
    const { admin, common } = useI18n();
    const [search, setSearch] = useState('');
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const { status, rows, errorMessage, reload } = useAdminList('/api/admin/cities', { per_page: 100, search: search || undefined });

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/cities/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const columns = [
        {
            key: 'name',
            label: admin.cities,
            render: (row) => (
                <span className="flex items-center gap-3">
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground">
                        <Building2 className="size-4" />
                    </span>
                    {row.name}
                </span>
            ),
        },
        { key: 'vendors_count', label: admin.vendors, align: 'end', render: (row) => row.vendors_count ?? 0 },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.cities.show', row.id)}>{common.view_details}</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.cities.edit', row.id)}>{common.edit}</Link>
                    </Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                        {common.delete}
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.cities}>
            <PageHeader
                title={admin.cities}
                copy={admin.manage_cities_copy}
                actions={
                    <>
                        <CsvImportButton label={admin.cities} templateUrl="/api/admin/cities/import/template" importUrl="/api/admin/cities/import" onImported={reload} />
                        <Button asChild size="sm">
                            <Link href={route('admin.cities.create')}>
                                <Plus className="size-4" />
                                {admin.add_city}
                            </Link>
                        </Button>
                    </>
                }
            />

            <Card className="border-border/80 shadow-none">
                <CardContent className="p-4">
                    <Input placeholder={admin.search_cities_placeholder} value={search} onChange={(e) => setSearch(e.target.value)} className="max-w-sm" />
                </CardContent>
            </Card>

            <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle={admin.no_cities_found} emptyHint={admin.create_first_city} />

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={admin.delete_city_title}
                description={admin.delete_city_warning}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
