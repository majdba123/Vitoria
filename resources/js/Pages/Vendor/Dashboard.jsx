import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { CheckCircle2, Package, PackageCheck, ShoppingBag } from 'lucide-react';
import VendorLayout from '@/Layouts/VendorLayout';
import { StatCard } from '@/Components/admin/dashboard/StatCard';
import { ListRow, StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { InsightPanel } from '@/Components/admin/dashboard/InsightPanel';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorDashboard() {
    const { vendor, common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [products, setProducts] = useState([]);
    const [stats, setStats] = useState({ totalProducts: 0, activeProducts: 0, orders: 0, storeActive: false });
    const [profile, setProfile] = useState(null);

    const load = () => {
        setStatus('loading');
        Promise.all([
            window.axios.get('/api/vendor/products', { params: { per_page: 5 }, silent: true }),
            window.axios.get('/api/vendor/products', { params: { per_page: 1, is_active: 1 }, silent: true }),
            window.axios.get('/api/vendor/orders', { params: { per_page: 1 }, silent: true }),
            window.axios.get('/api/vendor/profile', { silent: true }),
        ]).then(([productsRes, activeRes, ordersRes, profileRes]) => {
            setProducts(productsRes.data?.data ?? []);
            setStats({
                totalProducts: productsRes.data?.meta?.total ?? 0,
                activeProducts: activeRes.data?.meta?.total ?? 0,
                orders: ordersRes.data?.meta?.total ?? 0,
                storeActive: !!profileRes.data?.data?.vendor?.is_active,
            });
            setProfile(profileRes.data?.data ?? null);
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
                        <Link href={route('vendor.products.create')}>{vendor.create_product}</Link>
                    </Button>
                    <Button asChild size="sm" variant="outline">
                        <Link href={route('vendor.orders.index')}>{vendor.orders}</Link>
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
                            { href: route('vendor.products.create'), label: vendor.create_product },
                            { href: route('vendor.products.index'), label: vendor.products },
                            { href: route('vendor.orders.index'), label: vendor.orders },
                            { href: route('vendor.commission'), label: vendor.commission },
                        ].map((action) => (
                            <Link key={action.label} href={action.href} className="rounded-md border border-border bg-card px-4 py-3 text-sm font-semibold text-foreground transition-colors hover:border-primary/50 hover:bg-accent/40">
                                {action.label}
                            </Link>
                        ))}
                    </CardContent>
                </Card>
            </div>

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
