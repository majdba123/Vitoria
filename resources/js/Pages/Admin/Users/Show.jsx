import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Pencil, Heart } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DetailCard } from '@/Components/admin/DetailCard';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';

const TYPE_LABELS = { 0: 'User', 1: 'Admin', 2: 'Vendor', 3: 'Syndicate', 4: 'Employee' };
const TYPE_TONES = { 0: 'brand', 1: 'warning', 2: 'brand', 3: 'warning', 4: 'success' };

export default function UsersShow({ userId }) {
    const [status, setStatus] = useState('loading');
    const [user, setUser] = useState(null);
    const [favourites, setFavourites] = useState([]);
    const [favStatus, setFavStatus] = useState('loading');

    useEffect(() => {
        window.axios.get(`/api/admin/users/${userId}`, { silent: true }).then((res) => {
            setUser(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));

        window.axios.get(`/api/admin/users/${userId}/favourites`, { silent: true }).then((res) => {
            setFavourites(res.data.data ?? []);
            setFavStatus('ready');
        }).catch(() => setFavStatus('error'));
    }, [userId]);

    return (
        <AdminLayout title={user?.name ?? 'User profile'}>
            <PageHeader
                breadcrumb={[{ label: 'Users', href: route('admin.users.index') }, { label: 'Profile' }]}
                title={
                    <span className="flex items-center gap-4">
                        <Avatar className="size-14 ring-4 ring-background">
                            <AvatarImage src={user?.avatar_url} />
                            <AvatarFallback className="bg-accent text-accent-foreground text-lg">{(user?.name || 'U').charAt(0).toUpperCase()}</AvatarFallback>
                        </Avatar>
                        <span>
                            <span className="block">{user?.name ?? '—'}</span>
                            {user && (
                                <span className="mt-1 flex items-center gap-2 text-sm font-normal text-muted-foreground">
                                    {user.email ?? '—'}
                                    <StatusBadge tone={TYPE_TONES[user.type] ?? 'brand'}>{TYPE_LABELS[user.type] ?? 'User'}</StatusBadge>
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
                                Edit
                            </Link>
                        </Button>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load user profile.</p>}

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="lg:col-span-1">
                    <DetailCard
                        title="Personal information"
                        isLoading={status === 'loading'}
                        fields={[
                            { label: 'Full name', value: user?.name },
                            { label: 'Email', value: user?.email },
                            { label: 'Phone number', value: user?.phone_number },
                            { label: 'National ID', value: user?.national_id },
                            { label: 'Member since', value: user ? new Date(user.created_at).toLocaleDateString() : null },
                        ]}
                    />
                </div>

                <Card className="border-border/80 shadow-none lg:col-span-2">
                    <CardHeader className="flex-row items-center justify-between border-b border-border/80">
                        <CardTitle className="text-base font-bold">Favourites</CardTitle>
                        <span className="text-xs text-muted-foreground">{favStatus === 'ready' ? `${favourites.length} product${favourites.length !== 1 ? 's' : ''}` : 'Loading...'}</span>
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
                                <p className="mt-2 text-sm font-semibold text-muted-foreground">No favourites yet</p>
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
                                                <span className="text-sm font-bold text-foreground">{Number.parseFloat(product.price).toLocaleString()}</span>
                                                <span className="text-[10px] text-muted-foreground">SYP</span>
                                            </div>
                                        </div>
                                    </a>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
