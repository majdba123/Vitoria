import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Pencil, Building2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DetailCard } from '@/Components/admin/DetailCard';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

function formatMoney(value) {
    return `${Number(value || 0).toLocaleString()} SYP`;
}

export default function SyndicatesShow({ syndicateId }) {
    const { admin, common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [syndicate, setSyndicate] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/syndicates/${syndicateId}`, { silent: true }).then((res) => {
            setSyndicate(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [syndicateId]);

    const typeLabel = (t) => (t === 'agriculture' ? admin.type_agriculture : t === 'veterinary' ? admin.type_veterinary : t);

    return (
        <AdminLayout title={syndicate?.name ?? admin.syndicate_agents_heading}>
            <PageHeader
                breadcrumb={[{ label: admin.syndicate_agents_heading, href: route('admin.syndicates.index') }]}
                title={
                    <span className="flex items-center gap-3">
                        <span className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-md bg-accent text-accent-foreground">
                            {syndicate?.logo_url ? <img src={syndicate.logo_url} alt="" className="size-full object-cover" /> : <Building2 className="size-5" />}
                        </span>
                        {syndicate?.name ?? '—'}
                        {syndicate && (
                            <>
                                <StatusBadge tone={syndicate.is_active ? 'success' : 'danger'}>{syndicate.is_active ? common.active : common.inactive}</StatusBadge>
                                <StatusBadge tone="brand">{typeLabel(syndicate.type)}</StatusBadge>
                            </>
                        )}
                    </span>
                }
                copy={syndicate ? `${syndicate.email || syndicate.user?.email || ''} · ${syndicate.phone || syndicate.user?.phone_number || ''}` : null}
                actions={
                    syndicate && (
                        <Button asChild size="sm">
                            <Link href={route('admin.syndicates.edit', syndicate.id)}>
                                <Pencil className="size-4" />
                                {common.edit ?? 'Edit'}
                            </Link>
                        </Button>
                    )
                }
            />

            {status === 'error' && <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load syndicate.</p>}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <DetailCard isLoading={status === 'loading'} fields={[{ label: 'Categories', value: syndicate?.categories_count ?? 0 }]} />
                <DetailCard isLoading={status === 'loading'} fields={[{ label: 'Vendors', value: syndicate?.vendors_count ?? 0 }]} />
                <DetailCard isLoading={status === 'loading'} fields={[{ label: 'Products', value: syndicate?.products_count ?? 0 }]} />
                <DetailCard isLoading={status === 'loading'} fields={[{ label: 'Completed orders', value: syndicate?.completed_orders_count ?? 0 }]} />
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <DetailCard isLoading={status === 'loading'} fields={[{ label: 'Total sales', value: syndicate ? formatMoney(syndicate.total_sales) : null }]} />
                <DetailCard isLoading={status === 'loading'} fields={[{ label: 'All orders', value: syndicate?.orders_count ?? 0 }]} />
                <DetailCard isLoading={status === 'loading'} fields={[{ label: 'Created at', value: syndicate ? new Date(syndicate.created_at).toLocaleDateString() : null }]} />
                <DetailCard isLoading={status === 'loading'} fields={[{ label: 'Updated at', value: syndicate ? new Date(syndicate.updated_at).toLocaleDateString() : null }]} />
            </div>

            <DetailCard
                title="Linked user account"
                isLoading={status === 'loading'}
                columns={2}
                fields={[
                    { label: 'Name', value: syndicate?.user?.name },
                    { label: 'Email', value: syndicate?.user?.email || syndicate?.email },
                    { label: 'Phone', value: syndicate?.user?.phone_number || syndicate?.phone },
                ]}
            />
        </AdminLayout>
    );
}
