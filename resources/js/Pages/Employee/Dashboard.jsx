import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { RefreshCw, Package, PackageCheck, Hourglass, CircleCheck, CircleX, PackageX } from 'lucide-react';
import EmployeeLayout from '@/Layouts/EmployeeLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { StatCard } from '@/Components/admin/dashboard/StatCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

async function fetchAllProducts() {
    const products = [];
    let page = 1;
    let lastPage = 1;
    do {
        const res = await window.axios.get('/api/employee/products', { params: { per_page: 50, page }, silent: true });
        products.push(...(res.data?.data ?? []));
        lastPage = res.data?.meta?.last_page ?? 1;
        page++;
    } while (page <= lastPage);
    return products;
}

export default function EmployeeDashboard() {
    const { employee, common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [stats, setStats] = useState({ total: 0, pending: 0, approved: 0, rejected: 0, active: 0, inactive: 0 });
    const [categoryRows, setCategoryRows] = useState([]);
    const [typeRows, setTypeRows] = useState([]);

    const load = () => {
        setStatus('loading');
        Promise.all([
            window.axios.get('/api/employee/products', { params: { per_page: 1 }, silent: true }),
            window.axios.get('/api/employee/products', { params: { per_page: 1, status: 'pending' }, silent: true }),
            window.axios.get('/api/employee/products', { params: { per_page: 1, status: 'approved' }, silent: true }),
            window.axios.get('/api/employee/products', { params: { per_page: 1, status: 'rejected' }, silent: true }),
            window.axios.get('/api/employee/products', { params: { per_page: 1, is_active: 1 }, silent: true }),
            window.axios.get('/api/employee/products', { params: { per_page: 1, is_active: 0 }, silent: true }),
            fetchAllProducts(),
        ]).then(([total, pending, approved, rejected, active, inactive, allProducts]) => {
            setStats({
                total: total.data?.meta?.total ?? 0,
                pending: pending.data?.meta?.total ?? 0,
                approved: approved.data?.meta?.total ?? 0,
                rejected: rejected.data?.meta?.total ?? 0,
                active: active.data?.meta?.total ?? 0,
                inactive: inactive.data?.meta?.total ?? 0,
            });

            const categoryCounts = new Map();
            const typeCounts = new Map();
            allProducts.forEach((p) => {
                const categoryName = p?.category?.name || common.not_found || 'Not found';
                categoryCounts.set(categoryName, (categoryCounts.get(categoryName) || 0) + 1);
                const typeName = p?.category?.type_label || p?.category?.type || common.not_found || 'Not found';
                typeCounts.set(typeName, (typeCounts.get(typeName) || 0) + 1);
            });
            setCategoryRows([...categoryCounts.entries()].sort((a, b) => b[1] - a[1]).slice(0, 6));
            setTypeRows([...typeCounts.entries()].sort((a, b) => b[1] - a[1]));
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, []);

    const categoryMax = Math.max(...categoryRows.map(([, v]) => v), 1);
    const typeTotal = typeRows.reduce((sum, [, v]) => sum + v, 0);
    const typeColors = ['var(--color-primary)', 'var(--color-info-500)', 'var(--color-warning-500)', 'var(--color-danger-500)'];

    return (
        <EmployeeLayout title={employee.dashboard_overview}>
            <PageHeader
                title={employee.dashboard_title}
                copy={employee.dashboard_copy}
                actions={
                    <>
                        <Button asChild size="sm">
                            <Link href={route('employee.products.index')}>{employee.view_products}</Link>
                        </Button>
                        <Button variant="outline" size="sm" onClick={load} disabled={status === 'loading'}>
                            <RefreshCw className="size-4" />
                            {employee.refresh}
                        </Button>
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
                <StatCard label={employee.total_products} value={stats.total} icon={Package} status={status} onRetry={load} />
                <StatCard label={employee.active_products_label} value={stats.active} icon={PackageCheck} status={status} onRetry={load} />
                <StatCard label={employee.approved_products} value={stats.approved} icon={CircleCheck} status={status} onRetry={load} tone="success" />
                <StatCard label={employee.pending_products} value={stats.pending} icon={Hourglass} status={status} onRetry={load} />
                <StatCard label={employee.rejected_products} value={stats.rejected} icon={CircleX} status={status} onRetry={load} tone="danger" />
                <StatCard label={employee.inactive_products_label} value={stats.inactive} icon={PackageX} status={status} onRetry={load} />
            </div>

            <div className="grid grid-cols-1 gap-4 xl:grid-cols-4">
                {[
                    { href: route('employee.products.index'), label: employee.all_products, tone: 'border-s-primary' },
                    { href: route('employee.products.index', { status: 'approved' }), label: employee.active_products_tab, tone: 'border-s-[var(--color-success-500)]' },
                    { href: route('employee.products.index', { status: 'pending' }), label: employee.pending_products, tone: 'border-s-[var(--color-warning-500)]' },
                    { href: route('employee.products.index', { status: 'rejected' }), label: employee.rejected_products, tone: 'border-s-[var(--color-danger-500)]' },
                ].map((item) => (
                    <Link key={item.label} href={item.href} className={`flex items-center justify-between gap-4 rounded-md border border-border border-s-2 ${item.tone} bg-card px-4 py-4 transition-colors hover:bg-accent/40`}>
                        <div>
                            <p className="text-xs font-extrabold uppercase tracking-[0.18em] text-muted-foreground">{employee.status_summary}</p>
                            <h3 className="mt-2 text-base font-bold text-foreground">{item.label}</h3>
                        </div>
                        <span className="rounded-full bg-accent px-2.5 py-1 text-[11px] font-bold text-accent-foreground">{employee.open_tab}</span>
                    </Link>
                ))}
            </div>

            <div className="grid grid-cols-1 gap-6 xl:grid-cols-[1.4fr_1fr]">
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="flex-row items-center justify-between border-b border-border/80">
                        <div>
                            <CardTitle className="text-base font-bold">{employee.products_by_category}</CardTitle>
                            <p className="mt-1 text-xs text-muted-foreground">{employee.products_by_category_copy}</p>
                        </div>
                        <span className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{employee.live_mix}</span>
                    </CardHeader>
                    <CardContent className="space-y-3 p-5">
                        {status === 'ready' && categoryRows.length === 0 && <p className="py-8 text-center text-sm text-muted-foreground">{employee.no_products}</p>}
                        {categoryRows.map(([label, value]) => (
                            <div key={label} className="rounded-md border border-border p-4">
                                <div className="mb-2 flex items-center justify-between gap-3">
                                    <p className="truncate text-sm font-semibold text-foreground">{label}</p>
                                    <span className="text-sm font-bold text-primary">{value}</span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                    <div className="h-full rounded-full bg-primary" style={{ width: `${Math.max((value / categoryMax) * 100, 8)}%` }} />
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card className="border-border/80 shadow-none">
                    <CardHeader className="flex-row items-center justify-between border-b border-border/80">
                        <div>
                            <CardTitle className="text-base font-bold">{employee.products_by_type}</CardTitle>
                            <p className="mt-1 text-xs text-muted-foreground">{employee.products_by_type_copy}</p>
                        </div>
                        <span className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{employee.type_view}</span>
                    </CardHeader>
                    <CardContent className="space-y-3 p-5">
                        {status === 'ready' && typeRows.length === 0 && <p className="py-8 text-center text-sm text-muted-foreground">{employee.no_products}</p>}
                        {typeRows.map(([label, value], index) => {
                            const pct = typeTotal > 0 ? Math.round((value / typeTotal) * 100) : 0;
                            const color = typeColors[index % typeColors.length];
                            return (
                                <div key={label} className="rounded-md border border-border p-4">
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold text-foreground">{label}</p>
                                            <p className="mt-1 text-xs text-muted-foreground">{pct}% of all products</p>
                                        </div>
                                        <span className="inline-flex h-11 min-w-11 items-center justify-center rounded-md bg-muted px-3 text-sm font-bold text-foreground">{value}</span>
                                    </div>
                                    <div className="mt-3 h-2 overflow-hidden rounded-full bg-muted">
                                        <div className="h-full rounded-full" style={{ width: `${Math.max(pct, 8)}%`, background: color }} />
                                    </div>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>
            </div>
        </EmployeeLayout>
    );
}
