import { useEffect, useState } from 'react';
import { Eye } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
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
import { formatCurrency, formatDate, formatNumber } from '@/lib/date-time';
import { ORDER_STATUS_OPTIONS as STATUS_OPTIONS, ORDER_STATUS_TONE as STATUS_TONE } from '@/lib/order-status';
import { translatedEnum, translatedStatus } from '@/lib/translated-enum';

export default function OrdersIndex() {
    const { orders, common } = useI18n();
    const locale = useLocale();
    const initialParams = new URLSearchParams(window.location.search);
    const [page, setPage] = useState(1);
    const [product, setProduct] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [userId, setUserId] = useState(initialParams.get('user_id') ? String(initialParams.get('user_id')) : 'all');
    const [vendorId, setVendorId] = useState(initialParams.get('vendor_id') ? String(initialParams.get('vendor_id')) : 'all');
    const [categoryId, setCategoryId] = useState('all');
    const [users, setUsers] = useState([]);
    const [vendors, setVendors] = useState([]);
    const [categories, setCategories] = useState([]);

    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/admin/orders', {
        page,
        product: product || undefined,
        status: statusFilter === 'all' ? undefined : statusFilter,
        user_id: userId === 'all' ? undefined : userId,
        vendor_id: vendorId === 'all' ? undefined : vendorId,
        category_id: categoryId === 'all' ? undefined : categoryId,
    });

    useEffect(() => {
        window.axios.get('/api/admin/users', { params: { per_page: 200 }, silent: true }).then((res) => setUsers(res.data?.data ?? []));
        window.axios.get('/api/admin/vendors', { params: { per_page: 200 }, silent: true }).then((res) => setVendors(res.data?.data ?? []));
        window.axios.get('/api/categories', { params: { per_page: 100 }, silent: true }).then((res) => setCategories(res.data?.data ?? []));
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
        {
            key: 'parties',
            label: orders.vendor_customer_label,
            render: (row) => (
                <div>
                    <p>{row.vendor?.store_name || orders.unknown_vendor}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">{row.user?.name || orders.unknown_user}</p>
                </div>
            ),
        },
        { key: 'items', label: orders.items_label, align: 'end', render: (row) => formatNumber(row.items_count ?? (row.items || []).length, locale) },
        { key: 'total', label: orders.total_label, align: 'end', render: (row) => <span className="font-semibold text-foreground" dir="auto">{formatCurrency(row.total_amount, locale)}</span> },
        {
            key: 'status',
            label: orders.status_label,
            render: (row) => (
                <div className="flex flex-wrap items-center gap-1.5">
                    <StatusBadge tone={STATUS_TONE[row.status] ?? 'warning'}>{translatedStatus(row.status, common)}</StatusBadge>
                    <StatusBadge tone="brand">{translatedEnum(row.payment_way || 'cash', common.not_available, orders)}</StatusBadge>
                </div>
            ),
        },
        {
            key: 'actions',
            label: orders.action_label,
            align: 'end',
            render: (row) => (
                <a href={`/admin/orders/${row.id}`} className="inline-flex min-h-10 items-center gap-1.5 rounded-md px-2 text-sm font-semibold text-primary hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><Eye className="size-4" aria-hidden="true" />{orders.view_label}</a>
            ),
        },
    ];

    return (
        <AdminLayout title={orders.orders}>
            <PageHeader title={orders.orders} copy={orders.admin_filter_copy} />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Input placeholder={orders.product_name_placeholder} value={product} onChange={(e) => { setProduct(e.target.value); setPage(1); }} />
                    <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{orders.all_statuses}</SelectItem>
                            {STATUS_OPTIONS.map((s) => <SelectItem key={s} value={s}>{translatedStatus(s, common)}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={userId} onValueChange={(v) => { setUserId(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{orders.all_users}</SelectItem>
                            {users.map((u) => <SelectItem key={u.id} value={String(u.id)}>{u.name || orders.user_number_fallback.replace(':id', String(u.id))}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={vendorId} onValueChange={(v) => { setVendorId(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{orders.all_vendors}</SelectItem>
                            {vendors.map((v) => <SelectItem key={v.id} value={String(v.id)}>{v.store_name || orders.vendor_number_fallback.replace(':id', String(v.id))}</SelectItem>)}
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
        </AdminLayout>
    );
}
