import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
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

const STATUS_OPTIONS = ['pending', 'confirmed', 'preparing', 'shipped', 'out_for_delivery', 'completed', 'cancelled'];
const STATUS_TONE = { pending: 'warning', confirmed: 'success', preparing: 'success', shipped: 'brand', out_for_delivery: 'brand', completed: 'brand', cancelled: 'danger' };

export default function OrdersIndex() {
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
            label: 'Order',
            render: (row) => (
                <div>
                    <p className="font-semibold text-foreground">{row.order_number || `Order #${row.id}`}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">{row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'}</p>
                </div>
            ),
        },
        {
            key: 'parties',
            label: 'Vendor / Customer',
            render: (row) => (
                <div>
                    <p>{row.vendor?.store_name || 'Unknown vendor'}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">{row.user?.name || 'Unknown user'}</p>
                </div>
            ),
        },
        { key: 'items', label: 'Items', align: 'end', render: (row) => row.items_count ?? (row.items || []).length },
        { key: 'total', label: 'Total', align: 'end', render: (row) => <span className="font-semibold text-foreground">{Number.parseFloat(row.total_amount || 0).toLocaleString()} SYP</span> },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <div className="flex flex-wrap items-center gap-1.5">
                    <StatusBadge tone={STATUS_TONE[row.status] ?? 'warning'}>{row.status}</StatusBadge>
                    <StatusBadge tone="brand">{row.payment_way || 'cash'}</StatusBadge>
                </div>
            ),
        },
        {
            key: 'actions',
            label: 'Action',
            align: 'end',
            render: (row) => (
                <a href={`/admin/orders/${row.id}`} className="text-sm font-semibold text-primary hover:underline">View</a>
            ),
        },
    ];

    return (
        <AdminLayout title="Orders">
            <PageHeader title="Orders" copy="Refine by product, status, vendor, user, and category." />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Input placeholder="Product name" value={product} onChange={(e) => { setProduct(e.target.value); setPage(1); }} />
                    <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {STATUS_OPTIONS.map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={userId} onValueChange={(v) => { setUserId(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All users</SelectItem>
                            {users.map((u) => <SelectItem key={u.id} value={String(u.id)}>{u.name || `User #${u.id}`}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={vendorId} onValueChange={(v) => { setVendorId(v); setPage(1); }}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All vendors</SelectItem>
                            {vendors.map((v) => <SelectItem key={v.id} value={String(v.id)}>{v.store_name || `Vendor #${v.id}`}</SelectItem>)}
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
        </AdminLayout>
    );
}
