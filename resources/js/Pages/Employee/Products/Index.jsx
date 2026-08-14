import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import EmployeeLayout from '@/Layouts/EmployeeLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
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
import { useI18n } from '@/hooks/use-i18n';

const STATUS_TONE = { approved: 'success', rejected: 'danger', pending: 'warning' };

const TITLE_KEY = { approved: 'active_products_tab', pending: 'pending_products', rejected: 'rejected_products' };

export default function EmployeeProductsIndex({ status: statusParam }) {
    const { employee, common, admin } = useI18n();
    const [statusFilter, setStatusFilter] = useState(statusParam ?? 'all');
    const [status, setStatus] = useState('loading');
    const [rows, setRows] = useState([]);
    const [errorMessage, setErrorMessage] = useState(null);

    const load = () => {
        setStatus('loading');
        window.axios.get('/api/employee/products', { params: { per_page: 100, status: statusFilter === 'all' ? undefined : statusFilter }, silent: true }).then((res) => {
            setRows(res.data?.data ?? []);
            setStatus('ready');
        }).catch((error) => {
            setErrorMessage(error.response?.data?.message ?? null);
            setStatus('error');
        });
    };

    useEffect(load, [statusFilter]);

    useEffect(() => {
        const url = new URL(window.location.href);
        if (statusFilter !== 'all') url.searchParams.set('status', statusFilter);
        else url.searchParams.delete('status');
        window.history.replaceState({}, '', url.toString());
    }, [statusFilter]);

    const pageTitle = employee[TITLE_KEY[statusParam]] ?? employee.all_products;

    const columns = [
        {
            key: 'product',
            label: employee.all_products,
            render: (row) => (
                <div className="flex min-w-56 items-center gap-3">
                    <img src={row.first_photo_url || '/images/product-placeholder.svg'} className="size-10 shrink-0 rounded-md border border-border object-cover" alt="" />
                    <div>
                        <p className="font-semibold text-foreground">{row.name}</p>
                        {row.status === 'rejected' && row.rejection_reason && (
                            <p className="mt-0.5 max-w-xs truncate text-xs text-[var(--color-danger-strong)]">{row.rejection_reason}</p>
                        )}
                    </div>
                </div>
            ),
        },
        { key: 'category', label: admin.categories, render: (row) => row.category?.name || '—' },
        { key: 'status', label: employee.status, render: (row) => <StatusBadge tone={STATUS_TONE[row.status] ?? 'warning'}>{row.status}</StatusBadge> },
        { key: 'price', label: common.price ?? 'Price', align: 'end', render: (row) => `${Number.parseFloat(row.price || 0).toLocaleString()} SYP` },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <Link href={route('employee.products.edit', row.id)} className="text-sm font-semibold text-primary hover:underline">
                    {employee.review_product}
                </Link>
            ),
        },
    ];

    return (
        <EmployeeLayout title={pageTitle}>
            <PageHeader
                title={pageTitle}
                copy={statusParam ? employee.all_products_copy : employee.products_copy}
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('employee.dashboard')}>{employee.back_dashboard}</Link>
                    </Button>
                }
            />

            <Card className="gap-0 border-border/80 py-0 shadow-none">
                <CardContent className="flex flex-wrap items-center justify-between gap-3 border-b border-border/80 p-4">
                    <div>
                        <h3 className="text-base font-bold text-foreground">{employee.all_products}</h3>
                        <p className="mt-1 text-sm text-muted-foreground">{employee.all_products_copy}</p>
                    </div>
                    <div className="flex gap-2">
                        <Select value={statusFilter} onValueChange={setStatusFilter}>
                            <SelectTrigger className="w-44"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{employee.all_statuses}</SelectItem>
                                <SelectItem value="pending">{employee.pending}</SelectItem>
                                <SelectItem value="approved">{employee.approved}</SelectItem>
                                <SelectItem value="rejected">{employee.rejected}</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="outline" size="sm" onClick={load}>{common.refresh ?? 'Refresh'}</Button>
                    </div>
                </CardContent>
                <CardContent className="p-4">
                    <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={load} emptyTitle={employee.no_products} />
                </CardContent>
            </Card>
        </EmployeeLayout>
    );
}
