import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { CsvImportButton } from '@/Components/admin/CsvImportButton';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Card, CardContent } from '@/Components/ui/card';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n } from '@/hooks/use-i18n';

export default function SubcategoriesIndex() {
    const { admin, common } = useI18n();
    const [search, setSearch] = useState('');
    const [type, setType] = useState('all');
    const [categoryId, setCategoryId] = useState('all');
    const [categories, setCategories] = useState([]);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const { status, rows, errorMessage, reload } = useAdminList('/api/admin/subcategories', {
        per_page: 100,
        search: search || undefined,
        type: type === 'all' ? undefined : type,
        category_id: categoryId === 'all' ? undefined : categoryId,
    });

    useEffect(() => {
        window.axios.get('/api/admin/categories', { params: { per_page: 100 }, silent: true }).then((res) => setCategories(res.data?.data ?? []));
    }, []);

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/subcategories/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const columns = [
        {
            key: 'name',
            label: admin.category_name,
            render: (row) => (
                <div>
                    <p>{row.name_ar || row.name_en || '—'}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">{row.name_en}</p>
                </div>
            ),
        },
        { key: 'category', label: admin.parent_category_label, render: (row) => row.category?.name ?? '—' },
        { key: 'type', label: admin.type_label, render: (row) => ({ agriculture: admin.type_agriculture, veterinary: admin.type_veterinary, both: admin.type_both })[row.category?.type] ?? common.not_available },
        { key: 'products_count', label: admin.products, align: 'end', render: (row) => row.products_count ?? 0 },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.subcategories.show', row.id)}>{admin.view}</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.subcategories.edit', row.id)}>{common.edit}</Link>
                    </Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                        {common.delete}
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.subcategories_heading}>
            <PageHeader
                title={admin.subcategories_heading}
                copy={admin.manage_subcategories_copy}
                actions={
                    <>
                        <CsvImportButton
                            label={admin.subcategories}
                            templateUrl="/api/admin/subcategories/import/template"
                            importUrl="/api/admin/subcategories/import"
                            onImported={reload}
                        />
                        <Button asChild size="sm">
                            <Link href={route('admin.subcategories.create')}>
                                <Plus className="size-4" />
                                {admin.add_subcategory}
                            </Link>
                        </Button>
                    </>
                }
            />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-5 lg:grid-cols-4">
                    <div className="lg:col-span-2">
                        <label className="mb-1.5 block text-sm font-medium">{admin.search_label}</label>
                        <Input placeholder={admin.search_by_arabic_english_name} value={search} onChange={(e) => setSearch(e.target.value)} />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{admin.category_type_label}</label>
                        <Select value={type} onValueChange={setType}>
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{admin.all_types_plain}</SelectItem>
                                <SelectItem value="agriculture">{admin.type_agriculture}</SelectItem>
                                <SelectItem value="veterinary">{admin.type_veterinary}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{admin.parent_category_label}</label>
                        <Select value={categoryId} onValueChange={setCategoryId}>
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{admin.all_categories_plain}</SelectItem>
                                {categories.map((category) => (
                                    <SelectItem key={category.id} value={String(category.id)}>
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <DataTable
                columns={columns}
                rows={rows}
                status={status}
                errorMessage={errorMessage}
                onRetry={reload}
                emptyTitle={admin.no_subcategories_found}
                emptyHint={admin.try_another_filter}
            />

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={admin.subcategories_heading}
                description={`${common.confirm_delete} "${deleteTarget?.name_ar ?? deleteTarget?.name_en ?? ''}"?`}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
