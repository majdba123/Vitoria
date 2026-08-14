import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Check, Pencil, FileText } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DetailCard } from '@/Components/admin/DetailCard';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorsShow({ vendorId }) {
    const { admin, common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [vendor, setVendor] = useState(null);
    const [flash, setFlash] = useState(null);

    const load = () => {
        window.axios.get(`/api/admin/vendors/${vendorId}`, { silent: true }).then((res) => {
            setVendor(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, [vendorId]);

    const approve = () => {
        window.axios.patch(`/api/admin/vendors/${vendorId}/approve`, {}, { silent: true }).then((res) => {
            setVendor(res.data.data);
            setFlash(res.data.message);
        });
    };

    return (
        <AdminLayout title={vendor?.store_name ?? admin.vendors}>
            <PageHeader
                breadcrumb={[{ label: admin.vendors_breadcrumb, href: route('admin.vendors.index') }, { label: common.view_details ?? 'Details' }]}
                title={
                    <span className="flex items-center gap-4">
                        <div className="flex -space-x-3 rtl:space-x-reverse">
                            <Avatar className="size-14 ring-4 ring-background">
                                <AvatarImage src={vendor?.user?.avatar_url} />
                                <AvatarFallback className="bg-accent text-accent-foreground">{(vendor?.user?.name || 'V').charAt(0).toUpperCase()}</AvatarFallback>
                            </Avatar>
                            {vendor?.logo_url && (
                                <Avatar className="size-14 ring-4 ring-background">
                                    <AvatarImage src={vendor.logo_url} />
                                    <AvatarFallback>{(vendor?.store_name || 'S').charAt(0).toUpperCase()}</AvatarFallback>
                                </Avatar>
                            )}
                        </div>
                        <span>
                            <span className="block">{vendor?.store_name ?? '—'}</span>
                            {vendor && (
                                <span className="mt-1 block text-sm font-normal text-muted-foreground">
                                    Owned by {vendor.user?.name}
                                    {' · '}
                                    <StatusBadge tone={vendor.status === 'pending' ? 'warning' : vendor.is_active ? 'success' : 'danger'}>
                                        {vendor.status === 'pending' ? common.pending : vendor.is_active ? common.active : common.inactive}
                                    </StatusBadge>
                                </span>
                            )}
                        </span>
                    </span>
                }
                actions={
                    vendor && (
                        <>
                            {vendor.status === 'pending' && (
                                <Button size="sm" onClick={approve}>
                                    <Check className="size-4" />
                                    {common.approve ?? 'Approve'}
                                </Button>
                            )}
                            <Button asChild variant="outline" size="sm">
                                <Link href={route('admin.vendors.edit', vendor.id)}>
                                    <Pencil className="size-4" />
                                    {common.edit ?? 'Edit'}
                                </Link>
                            </Button>
                        </>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load vendor.</p>}
            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            <div className="grid gap-4 lg:grid-cols-2">
                <DetailCard
                    title="Personal information"
                    isLoading={status === 'loading'}
                    fields={[
                        { label: 'Full name', value: vendor?.user?.name },
                        { label: 'Phone number', value: vendor?.user?.phone_number },
                        { label: 'Email', value: vendor?.user?.email },
                        { label: 'National ID', value: vendor?.user?.national_id },
                        { label: 'Joined', value: vendor ? new Date(vendor.created_at).toLocaleDateString() : null },
                        { label: 'Registration source', value: vendor ? (vendor.registration_source === 'self' ? admin.self_registration : admin.admin_registration) : null },
                    ]}
                />
                <DetailCard
                    title="Store information"
                    isLoading={status === 'loading'}
                    fields={[
                        { label: 'Store name', value: vendor?.store_name },
                        { label: 'Selected categories', value: vendor?.categories?.length ? vendor.categories.map((c) => c.name).join(', ') : admin.not_assigned },
                        { label: 'Address', value: vendor?.address || 'No address provided.' },
                        { label: 'City', value: vendor?.city?.name || 'No city provided.' },
                        { label: 'Description', value: vendor?.description || 'No description provided.' },
                    ]}
                />
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">Allowed categories</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-2 p-5">
                    {vendor?.categories?.length ? (
                        vendor.categories.map((category) => (
                            <StatusBadge key={category.id} tone="brand">
                                {category.name} ({Number(category.commission || 0).toFixed(2)}%)
                            </StatusBadge>
                        ))
                    ) : (
                        <span className="text-sm text-muted-foreground italic">No categories assigned</span>
                    )}
                </CardContent>
            </Card>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">Commercial registration</CardTitle>
                </CardHeader>
                <CardContent className="p-5">
                    {vendor?.commercial_register_url ? (
                        <Button asChild variant="outline" size="sm">
                            <a href={vendor.commercial_register_url} target="_blank" rel="noopener">
                                <FileText className="size-4" />
                                View / download document
                            </a>
                        </Button>
                    ) : (
                        <p className="text-sm text-muted-foreground">No document uploaded.</p>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
