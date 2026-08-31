import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { Pagination } from '@/Components/shared/Pagination';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
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
import { ORDER_STATUS_OPTIONS as STATUS_OPTIONS, ORDER_STATUS_TONE as STATUS_TONE } from '@/lib/order-status';
import { translatedStatus } from '@/lib/translated-enum';

export default function VendorOrdersIndex() {
    const { vendor, orders, common } = useI18n();
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
            label: orders.order,
            render: (row) => (
                <div>
                    <p className="font-semibold text-foreground">{row.order_number || orders.order_number_fallback.replace(':id', String(row.id))}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground" dir="auto">{formatDate(row.created_at, locale) || '—'}</p>
                </div>
            ),
        },
        { key: 'customer', label: orders.customer, render: (row) => row.user?.name || orders.unknown_user },
        {
            key: 'status',
            label: orders.status_label,
            render: (row) => <StatusBadge tone={STATUS_TONE[row.status] ?? 'warning'}>{translatedStatus(row.status, common)}</StatusBadge>,
        },
        { key: 'total', label: orders.total_label, align: 'end', render: (row) => <span className="font-semibold text-foreground" dir="auto">{formatCurrency(row.total || row.total_amount, locale)}</span> },
        {
            key: 'actions',
            label: orders.action_label,
            align: 'end',
            render: (row) => (
                <Link href={route('vendor.orders.show', row.id)} className="inline-flex min-h-10 items-center gap-1.5 rounded-md px-2 text-sm font-semibold text-primary hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><Eye className="size-4" aria-hidden="true" />{common.view_details}</Link>
            ),
        },
    ];

    return (
        <VendorLayout title={vendor.orders}>
            <PageHeader title={vendor.orders} copy={orders.vendor_filter_copy} />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-3">
                    <Input placeholder={orders.product_name_placeholder} value={product} onChange={(e) => { setProduct(e.target.value); setPage(1); }} />
                    <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{orders.all_statuses}</SelectItem>
                            {STATUS_OPTIONS.map((s) => <SelectItem key={s} value={s}>{translatedStatus(s, common)}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={categoryId} onValueChange={(v) => { setCategoryId(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{orders.all_categories}</SelectItem>
                            {categories.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </CardContent>
            </Card>

            <div>
                <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle={orders.no_orders_found} />
                {status === 'ready' && rows.length > 0 && (
                    <div className="rounded-b-lg border border-t-0 border-border">
                        <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                    </div>
                )}
            </div>
        </VendorLayout>
    );
}
