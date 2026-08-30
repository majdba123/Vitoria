import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { BarChart3, CheckCircle2, Package, PackageCheck, Plus, ReceiptText, ShoppingBag } from 'lucide-react';
import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';
import VendorLayout from '@/Layouts/VendorLayout';
import { StatCard } from '@/Components/admin/dashboard/StatCard';
import { ListRow, StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { InsightPanel } from '@/Components/admin/dashboard/InsightPanel';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useI18n } from '@/hooks/use-i18n';

const ORDERS_CHART_CONFIG = { count: { label: 'Confirmed + completed orders', color: 'var(--chart-1)' } };

export default function VendorDashboard() {
    const { vendor, common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [products, setProducts] = useState([]);
    const [stats, setStats] = useState({ totalProducts: 0, activeProducts: 0, orders: 0, storeActive: false });
    const [profile, setProfile] = useState(null);
    const [orderTrend, setOrderTrend] = useState([]);

    const load = () => {
        setStatus('loading');
        Promise.all([
            window.axios.get('/api/vendor/products', { params: { per_page: 5 }, silent: true }),
            window.axios.get('/api/vendor/products', { params: { per_page: 1, is_active: 1 }, silent: true }),
            window.axios.get('/api/vendor/orders', { params: { per_page: 1 }, silent: true }),
            window.axios.get('/api/vendor/profile', { silent: true }),
            window.axios.get('/api/vendor/commission-stats', { silent: true }),
        ]).then(([productsRes, activeRes, ordersRes, profileRes, commissionRes]) => {
            setProducts(productsRes.data?.data ?? []);
            setStats({
                totalProducts: productsRes.data?.meta?.total ?? 0,
                activeProducts: activeRes.data?.meta?.total ?? 0,
                orders: ordersRes.data?.meta?.total ?? 0,
                storeActive: !!profileRes.data?.data?.vendor?.is_active,
            });
            setProfile(profileRes.data?.data ?? null);
            setOrderTrend(commissionRes.data?.data?.recent_orders_last_7_days ?? []);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, []);

    const storeName = profile?.vendor?.store_name || vendor.dashboard_heading;

    return (
        <VendorLayout title={vendor.dashboard}>
            <section className="flex flex-col gap-4 rounded-lg border border-border/80 bg-card px-5 py-5 sm:px-6 sm:py-6 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0">
                    <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-primary">{vendor.workspace}</p>
                    <h2 className="mt-2 text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{storeName}</h2>
                    <p className="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">{vendor.dashboard_copy}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button asChild size="sm">
                        <Link href={route('vendor.products.create')}><Plus className="size-4" aria-hidden="true" />{vendor.create_product}</Link>
                    </Button>
                    <Button asChild size="sm" variant="outline">
                        <Link href={route('vendor.orders.index')}><ShoppingBag className="size-4" aria-hidden="true" />{vendor.orders}</Link>
                    </Button>
                </div>
            </section>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label={vendor.store_status} value={stats.storeActive ? common.active : common.inactive} icon={CheckCircle2} status={status} tone={stats.storeActive ? 'success' : undefined} />
                <StatCard label={vendor.total_products} value={stats.totalProducts} icon={Package} status={status} />
                <StatCard label={vendor.active_products} value={stats.activeProducts} icon={PackageCheck} status={status} tone="success" />
                <StatCard label={vendor.orders} value={stats.orders} icon={ShoppingBag} status={status} />
            </div>

            <div className="grid grid-cols-1 gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                <InsightPanel
                    title={vendor.recent_products_title}
                    copy={vendor.recent_products_copy}
                    action={<Link href={route('vendor.products.index')} className="text-xs font-semibold text-primary hover:underline">{vendor.view_all}</Link>}
                    status={status}
                    isEmpty={products.length === 0}
                    emptyMessage={vendor.assigned_categories_empty}
                    onRetry={load}
                >
                    <div className="space-y-2">
                        {products.map((product) => (
                            <ListRow
                                key={product.id}
                                href={route('vendor.products.show', product.id)}
                                title={product.name}
                                subtitle={product.category?.name || product.status || ''}
                                trailing={<StatusBadge tone={product.is_active ? 'success' : 'warning'}>{product.is_active ? common.active : common.inactive}</StatusBadge>}
                            />
                        ))}
                    </div>
                </InsightPanel>

                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">{vendor.quick_actions_title}</CardTitle>
                        <p className="text-sm text-muted-foreground">{vendor.quick_actions_copy}</p>
                    </CardHeader>
                    <CardContent className="grid gap-3 p-5">
                        {[
                            { href: route('vendor.products.create'), label: vendor.create_product, icon: Plus },
                            { href: route('vendor.products.index'), label: vendor.products, icon: Package },
                            { href: route('vendor.orders.index'), label: vendor.orders, icon: ReceiptText },
                            { href: route('vendor.commission'), label: vendor.commission, icon: BarChart3 },
                        ].map((action) => (
                            <QuickAction key={action.label} {...action} />
                        ))}
                    </CardContent>
                </Card>
            </div>

            <InsightPanel
                title="Orders, last 7 days"
                copy="Real order counts for your store (confirmed + completed), updated daily."
                status={status}
                isEmpty={orderTrend.every((row) => row.count === 0)}
                emptyMessage="No orders in the last 7 days."
                onRetry={load}
            >
                <ChartContainer config={ORDERS_CHART_CONFIG} className="aspect-auto h-48 w-full">
                    <AreaChart data={orderTrend} margin={{ left: 0, right: 8, top: 8 }}>
                        <CartesianGrid vertical={false} strokeDasharray="3 3" />
                        <XAxis dataKey="date" tickLine={false} axisLine={false} tickMargin={8} tickFormatter={(value) => value?.slice?.(5) ?? value} />
                        <ChartTooltip content={<ChartTooltipContent labelKey="date" />} cursor={{ fill: 'var(--color-accent)' }} />
                        <Area dataKey="count" type="monotone" fill="var(--color-count)" fillOpacity={0.2} stroke="var(--color-count)" strokeWidth={2} />
                    </AreaChart>
                </ChartContainer>
                <p className="sr-only">
                    {orderTrend.map((row) => `${row.date}: ${row.count} orders.`).join(' ')}
                </p>
            </InsightPanel>

            <InsightPanel title={vendor.store_information_title} copy={vendor.store_information_copy} status={status} isEmpty={false} onRetry={load}>
                <div className="grid gap-3 sm:grid-cols-2">
                    {[
                        { label: vendor.store_name_label, value: profile?.vendor?.store_name },
                        { label: vendor.owner_name_label, value: profile?.user?.name },
                        { label: vendor.city_label, value: profile?.user?.city?.name },
                        { label: vendor.email_label, value: profile?.user?.email },
                    ].map((item) => (
                        <div key={item.label} className="rounded-md border border-border bg-card px-4 py-3">
                            <p className="text-xs font-extrabold uppercase tracking-[0.18em] text-muted-foreground">{item.label}</p>
                            <p className="mt-2 text-sm font-semibold text-foreground">{item.value || '—'}</p>
                        </div>
                    ))}
                </div>
            </InsightPanel>
        </VendorLayout>
    );
}

function QuickAction({ href, label, icon: Icon }) {
    return <Link href={href} className="flex min-h-11 items-center gap-3 rounded-md border border-border bg-card px-4 py-3 text-sm font-semibold text-foreground transition-colors hover:border-primary/50 hover:bg-accent/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><Icon className="size-4 shrink-0 text-primary" aria-hidden="true" />{label}</Link>;
}
