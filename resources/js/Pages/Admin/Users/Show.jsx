import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Pencil, Heart, ShoppingBag } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DetailCard } from '@/Components/shared/DetailCard';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDate } from '@/lib/date-time';
import { translatedStatus } from '@/lib/translated-enum';

const TYPE_TONES = { 0: 'brand', 1: 'warning', 2: 'brand', 3: 'warning', 4: 'success' };

export default function UsersShow({ userId }) {
    const { admin, common, orders: ordersLang } = useI18n();
    const locale = useLocale();
    const [status, setStatus] = useState('loading');
    const [user, setUser] = useState(null);
    const [favourites, setFavourites] = useState([]);
    const [favStatus, setFavStatus] = useState('loading');
    const [recentOrders, setRecentOrders] = useState([]);
    const [ordersStatus, setOrdersStatus] = useState('loading');
    const isCustomer = user?.type === 0;

    useEffect(() => {
        window.axios.get(`/api/admin/users/${userId}`, { silent: true }).then((res) => {
            setUser(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));

        window.axios.get(`/api/admin/users/${userId}/favourites`, { silent: true }).then((res) => {
            setFavourites(res.data.data ?? []);
            setFavStatus('ready');
        }).catch(() => setFavStatus('error'));

        window.axios.get('/api/admin/orders', { params: { user_id: userId, per_page: 5 }, silent: true }).then((res) => {
            setRecentOrders(res.data?.data ?? []);
            setOrdersStatus('ready');
        }).catch(() => setOrdersStatus('error'));
    }, [userId]);

    return (
        <AdminLayout title={user?.name ?? admin.user_profile_title}>
            <PageHeader
                breadcrumb={[{ label: admin.users, href: route('admin.users.index') }, { label: common.profile }]}
                title={
                    <span className="flex items-center gap-4">
                        <Avatar className="size-14 ring-4 ring-background">
                            <AvatarImage src={user?.avatar_url} alt={user?.name ?? ''} />
                            <AvatarFallback className="bg-accent text-accent-foreground text-lg">{(user?.name || common.user_fallback || 'U').charAt(0).toUpperCase()}</AvatarFallback>
                        </Avatar>
                        <span>
                            <span className="block">{user?.name ?? '—'}</span>
                            {user && (
                                <span className="mt-1 flex items-center gap-2 text-sm font-normal text-muted-foreground">
                                    {user.email ?? '—'}
                                    <StatusBadge tone={TYPE_TONES[user.type] ?? 'brand'}>{admin.user_type_labels?.[user.type] ?? common.user_fallback}</StatusBadge>
                                </span>
                            )}
                        </span>
                    </span>
                }
                actions={
                    user && (
                        <Button asChild size="sm">
                            <Link href={route('admin.users.edit', user.id)}>
                                <Pencil className="size-4" />
                                {common.edit}
                            </Link>
                        </Button>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">{admin.failed_load_user_profile}</p>}

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="lg:col-span-1">
                    <DetailCard
                        title={admin.personal_information}
                        isLoading={status === 'loading'}
                        fields={[
                            { label: admin.full_name_label, value: user?.name },
                            { label: admin.email_label, value: user?.email },
                            { label: admin.phone_number_label, value: user?.phone_number },
                            { label: admin.th_national_id, value: user?.national_id },
                            ...(isCustomer
                                ? [
                                      { label: admin.th_city, value: user?.city?.name || common.not_specified },
                                      {
                                          label: admin.th_product_interest,
                                          value:
                                              user?.preferred_product_type === 'agriculture'
                                                  ? admin.type_agriculture
                                                  : user?.preferred_product_type === 'veterinary'
                                                    ? admin.type_veterinary
                                                    : common.not_specified,
                                      },
                                  ]
                                : []),
                            { label: admin.member_since_label, value: user ? formatDate(user.created_at, locale) : null },
                        ]}
                    />
                </div>

                <Card className="border-border/80 shadow-none lg:col-span-2">
                    <CardHeader className="flex-row items-center justify-between border-b border-border/80">
                        <CardTitle className="text-base font-bold">{admin.favourites_title}</CardTitle>
                        <span className="text-xs text-muted-foreground">{favStatus === 'ready' ? (admin.products_count_label ?? '').replace(':count', String(favourites.length)) : admin.loading}</span>
                    </CardHeader>
                    <CardContent className="p-5">
                        {favStatus === 'loading' && (
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                {[0, 1, 2].map((i) => <Skeleton key={i} className="h-48 w-full rounded-lg" />)}
                            </div>
                        )}
                        {favStatus === 'ready' && favourites.length === 0 && (
                            <div className="py-10 text-center">
                                <Heart className="mx-auto size-10 text-muted-foreground/40" />
                                <p className="mt-2 text-sm font-semibold text-muted-foreground">{admin.no_favourites_yet}</p>
                            </div>
                        )}
                        {favStatus === 'ready' && favourites.length > 0 && (
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                {favourites.map((product) => (
                                    <a key={product.id} href={`/products/${product.id}`} className="group overflow-hidden rounded-lg border border-border bg-card transition-colors hover:border-primary/50">
                                        <div className="flex aspect-square items-center justify-center overflow-hidden bg-muted">
                                            {product.first_photo_url ? <img src={product.first_photo_url} alt="" className="size-full object-contain p-3" loading="lazy" /> : <Heart className="size-8 text-muted-foreground/30" />}
                                        </div>
                                        <div className="p-3">
                                            {product.vendor && <p className="mb-0.5 truncate text-[10px] text-muted-foreground">{product.vendor.store_name}</p>}
                                            <h4 className="line-clamp-2 text-xs font-bold text-foreground group-hover:text-primary">{product.name}</h4>
                                            <div className="mt-1.5 flex items-baseline gap-1">
                                                <span className="text-sm font-bold text-foreground">{formatCurrency(product.price, locale)}</span>
                                            </div>
                                        </div>
                                    </a>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {isCustomer && (
                <div className="grid gap-5 lg:grid-cols-3">
                    <Card className="border-border/80 shadow-none lg:col-span-1">
                        <CardHeader className="border-b border-border/80">
                            <CardTitle className="text-base font-bold">{admin.order_summary_title}</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4 p-5">
                            <div>
                                <p className="text-xs text-muted-foreground">{admin.th_orders_count}</p>
                                <p className="mt-1 text-lg font-bold text-foreground">{user?.orders_count ?? 0}</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">{admin.th_total_purchases}</p>
                                <p className="mt-1 text-lg font-bold text-foreground">{formatCurrency(user?.total_purchases ?? 0, locale)}</p>
                            </div>
                            <div className="col-span-2">
                                <p className="text-xs text-muted-foreground">{admin.th_last_order}</p>
                                <p className="mt-1 text-sm font-semibold text-foreground">{user?.last_order_at ? formatDate(user.last_order_at, locale) : common.not_specified}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/80 shadow-none lg:col-span-2">
                        <CardHeader className="border-b border-border/80">
                            <CardTitle className="text-base font-bold">{admin.recent_orders_title}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2 p-5">
                            {ordersStatus === 'loading' && (
                                <div className="flex flex-col gap-2">
                                    {[0, 1, 2].map((i) => <Skeleton key={i} className="h-14 w-full rounded-md" />)}
                                </div>
                            )}
                            {ordersStatus === 'ready' && recentOrders.length === 0 && (
                                <div className="py-10 text-center">
                                    <ShoppingBag className="mx-auto size-10 text-muted-foreground/40" />
                                    <p className="mt-2 text-sm font-semibold text-muted-foreground">{admin.no_orders_yet}</p>
                                </div>
                            )}
                            {recentOrders.slice(0, 5).map((order) => (
                                <Link
                                    key={order.id}
                                    href={route('admin.orders.show', order.id)}
                                    className="flex items-center justify-between gap-3 rounded-md border border-border px-3 py-2 transition-colors hover:bg-muted"
                                >
                                    <span className="min-w-0">
                                        <span className="block truncate font-semibold text-foreground">{order.order_number || ordersLang.order_number_fallback.replace(':id', String(order.id))}</span>
                                        <span className="block text-xs text-muted-foreground" dir="auto">{formatDate(order.created_at, locale)}</span>
                                    </span>
                                    <span className="flex shrink-0 items-center gap-2">
                                        <StatusBadge tone={order.status === 'completed' ? 'success' : order.status === 'cancelled' ? 'danger' : 'warning'}>{translatedStatus(order.status, common)}</StatusBadge>
                                        <span className="tabular-nums font-medium text-foreground">{formatCurrency(order.grand_total ?? 0, locale)}</span>
                                    </span>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            )}
        </AdminLayout>
    );
}
