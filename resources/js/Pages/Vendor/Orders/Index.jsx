import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { Pagination } from '@/Components/admin/Pagination';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
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
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDate } from '@/lib/date-time';

const STATUS_OPTIONS = ['pending', 'confirmed', 'preparing', 'shipped', 'out_for_delivery', 'completed', 'cancelled'];
const STATUS_TONE = { pending: 'warning', confirmed: 'success', preparing: 'success', shipped: 'brand', out_for_delivery: 'brand', completed: 'brand', cancelled: 'danger' };

export default function VendorOrdersIndex() {
    const { vendor } = useI18n();
    const locale = useLocale();
    const [page, setPage] = useState(1);
    const [product, setProduct] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [categoryId, setCategoryId] = useState('all');
    const [categories, setCategories] = useState([]);

    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/vendor/orders', {
        page,
        product: product || undefined,
        status: statusFilter === 'all' ? undefined : statusFilter,
        category_id: categoryId === 'all' ? undefined : categoryId,
    });

    useEffect(() => {
        window.axios.get('/api/vendor/categories', { silent: true }).then((res) => setCategories(res.data?.data ?? []));
    }, []);

    const columns = [
        {
            key: 'order',
            label: 'Order',
            render: (row) => (
                <div>
                    <p className="font-semibold text-foreground">{row.order_number || `Order #${row.id}`}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground" dir="auto">{formatDate(row.created_at, locale) || '—'}</p>
                </div>
            ),
        },
        { key: 'customer', label: 'Customer', render: (row) => row.user?.name || 'Unknown user' },
        {
            key: 'status',
            label: 'Status',
            render: (row) => <StatusBadge tone={STATUS_TONE[row.status] ?? 'warning'}>{row.status}</StatusBadge>,
        },
        { key: 'total', label: 'Total', align: 'end', render: (row) => <span className="font-semibold text-foreground" dir="auto">{formatCurrency(row.total || row.total_amount, locale)}</span> },
        {
            key: 'actions',
            label: 'Action',
            align: 'end',
            render: (row) => (
                <Link href={route('vendor.orders.show', row.id)} className="text-sm font-semibold text-primary hover:underline">View Details</Link>
            ),
        },
    ];

    return (
        <VendorLayout title={vendor.orders}>
            <PageHeader title={vendor.orders} copy="Filter your store orders by product, status, and category." />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-3">
                    <Input placeholder="Product name" value={product} onChange={(e) => { setProduct(e.target.value); setPage(1); }} />
                    <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {STATUS_OPTIONS.map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={categoryId} onValueChange={(v) => { setCategoryId(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All categories</SelectItem>
                            {categories.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </CardContent>
            </Card>

            <div>
                <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle="No orders found." />
                {status === 'ready' && rows.length > 0 && (
                    <div className="rounded-b-lg border border-t-0 border-border">
                        <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                    </div>
                )}
            </div>
        </VendorLayout>
    );
}
