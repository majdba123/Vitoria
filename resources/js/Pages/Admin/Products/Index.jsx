import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, Package } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { Pagination } from '@/Components/admin/Pagination';
import { CsvImportButton } from '@/Components/admin/CsvImportButton';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
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

export default function ProductsIndex({ discountOnly = false }) {
    const { admin, common } = useI18n();
    const [page, setPage] = useState(1);
    const [vendorId, setVendorId] = useState('all');
    const [productStatus, setProductStatus] = useState('all');
    const [categoryType, setCategoryType] = useState('all');
    const [categoryId, setCategoryId] = useState('all');
    const [activeFilter, setActiveFilter] = useState('all');
    const [discountFilter, setDiscountFilter] = useState(discountOnly ? '1' : 'all');
    const [vendors, setVendors] = useState([]);
    const [categories, setCategories] = useState([]);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [flash, setFlash] = useState(null);

    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/admin/products', {
        page,
        vendor_id: vendorId === 'all' ? undefined : vendorId,
        status: productStatus === 'all' ? undefined : productStatus,
        category_type: categoryType === 'all' ? undefined : categoryType,
        category_id: categoryId === 'all' ? undefined : categoryId,
        is_active: activeFilter === 'all' ? undefined : activeFilter,
        has_discount: discountFilter === 'all' ? undefined : discountFilter,
    });

    useEffect(() => {
        window.axios.get('/api/admin/vendors', { params: { per_page: 100 }, silent: true }).then((res) => setVendors(res.data?.data ?? []));
    }, []);

    useEffect(() => {
        window.axios.get('/api/admin/categories', { params: categoryType === 'all' ? {} : { type: categoryType }, silent: true }).then((res) => setCategories(res.data?.data ?? []));
    }, [categoryType]);

    const showFlash = (message) => {
        setFlash(message);
        setTimeout(() => setFlash(null), 4000);
    };

    const toggleActive = (product) => {
        window.axios.patch(`/api/admin/products/${product.id}/toggle-active`, {}, { silent: true }).then((res) => {
            showFlash(res.data.message);
            reload();
        });
    };

    const changeStatus = (product, value) => {
        window.axios.patch(`/api/admin/products/${product.id}/status`, { status: value }, { silent: true }).then((res) => {
            showFlash(res.data.message ?? 'Status updated.');
            reload();
        }).catch(() => reload());
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/products/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const columns = [
        {
            key: 'name',
            label: admin.name_label,
            render: (row) => (
                <div className="flex items-center gap-3">
                    <span className="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-accent text-accent-foreground">
                        {row.first_photo_url ? <img src={row.first_photo_url} alt="" className="size-full object-contain p-1" /> : <Package className="size-4" />}
                    </span>
                    <div className="min-w-0">
                        <p className="truncate font-semibold text-foreground">{row.name}</p>
                        <p className="truncate text-xs text-muted-foreground">{row.commercial_name || row.category?.name || admin.no_commercial_name}</p>
                    </div>
                </div>
            ),
        },
        {
            key: 'price',
            label: common.price ?? 'Price',
            align: 'end',
            render: (row) =>
                row.has_active_discount ? (
                    <span className="flex flex-col items-end">
                        <span className="flex items-center gap-1.5">
                            <StatusBadge tone="danger">-{parseFloat(row.discount_percentage || 0).toFixed(0)}%</StatusBadge>
                            <span className="font-semibold text-[var(--color-danger-strong)]">{Number(row.discounted_price || row.price || 0).toLocaleString()} SYP</span>
                        </span>
                        <span className="text-xs text-muted-foreground line-through">{Number(row.price || 0).toLocaleString()} SYP</span>
                    </span>
                ) : (
                    <span className="font-semibold text-foreground">{Number(row.price || 0).toLocaleString()} SYP</span>
                ),
        },
        { key: 'quantity', label: admin.qty_label, align: 'end', render: (row) => row.quantity },
        {
            key: 'active',
            label: common.active,
            render: (row) => (
                <button type="button" onClick={() => toggleActive(row)}>
                    <StatusBadge tone={row.is_active ? 'success' : 'danger'}>{row.is_active ? common.active : common.inactive}</StatusBadge>
                </button>
            ),
        },
        {
            key: 'status',
            label: admin.approval_status_label,
            render: (row) => (
                <Select value={row.status || 'pending'} onValueChange={(value) => changeStatus(row, value)}>
                    <SelectTrigger size="sm" className="w-32">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="pending">{admin.status_pending}</SelectItem>
                        <SelectItem value="approved">{admin.status_approved}</SelectItem>
                        <SelectItem value="rejected">{admin.status_rejected}</SelectItem>
                    </SelectContent>
                </Select>
            ),
        },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex flex-wrap items-center justify-end gap-1.5">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.products.show', row.id)}>{admin.show}</Link>
                    </Button>
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.products.reviews', row.id)}>{admin.reviews}</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.products.edit', row.id)}>{common.edit ?? 'Edit'}</Link>
                    </Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                        {admin.remove}
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.manage_products_title}>
            <PageHeader
                title={admin.manage_products_title}
                copy={admin.manage_products_copy}
                actions={
                    <>
                        <CsvImportButton label={admin.products} templateUrl="/api/admin/products/import/template" importUrl="/api/admin/products/import" onImported={reload} />
                        <Button asChild size="sm">
                            <Link href={route('admin.products.create')}>
                                <Plus className="size-4" />
                                {admin.add_product}
                            </Link>
                        </Button>
                    </>
                }
            />

            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-3 xl:grid-cols-6">
                    <FilterSelect label={admin.filter_by_vendor} value={vendorId} onValueChange={(v) => { setVendorId(v); setPage(1); }} allLabel={admin.all_vendors} options={vendors.map((v) => ({ value: String(v.id), label: v.store_name }))} />
                    <FilterSelect label={admin.filter_by_product_status} value={productStatus} onValueChange={(v) => { setProductStatus(v); setPage(1); }} allLabel={admin.all_status} options={[{ value: 'pending', label: admin.status_pending }, { value: 'approved', label: admin.status_approved }, { value: 'rejected', label: admin.status_rejected }]} />
                    <FilterSelect label={admin.filter_by_type} value={categoryType} onValueChange={(v) => { setCategoryType(v); setCategoryId('all'); setPage(1); }} allLabel={admin.all_types} options={[{ value: 'agriculture', label: admin.type_agriculture }, { value: 'veterinary', label: admin.type_veterinary }]} />
                    <FilterSelect label={admin.filter_by_category} value={categoryId} onValueChange={(v) => { setCategoryId(v); setPage(1); }} allLabel={admin.all_categories} options={categories.map((c) => ({ value: String(c.id), label: c.name }))} />
                    <FilterSelect label={admin.filter_by_active} value={activeFilter} onValueChange={(v) => { setActiveFilter(v); setPage(1); }} allLabel={admin.all} options={[{ value: '1', label: common.active }, { value: '0', label: common.inactive }]} />
                    <FilterSelect label={admin.filter_by_discount} value={discountFilter} onValueChange={(v) => { setDiscountFilter(v); setPage(1); }} allLabel={admin.all} options={[{ value: '1', label: admin.with_discount }, { value: '0', label: admin.without_discount }]} />
                </CardContent>
            </Card>

            <div>
                <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle={admin.no_products_yet} emptyHint={admin.add_product_for_vendor_hint} />
                {status === 'ready' && rows.length > 0 && (
                    <div className="rounded-b-lg border border-t-0 border-border">
                        <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                    </div>
                )}
            </div>

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={admin.delete_product_title}
                description={admin.action_cannot_be_undone}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}

function FilterSelect({ label, value, onValueChange, allLabel, options }) {
    return (
        <div>
            <label className="mb-1.5 block text-sm font-medium">{label}</label>
            <Select value={value} onValueChange={onValueChange}>
                <SelectTrigger className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{allLabel}</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
