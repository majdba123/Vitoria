import { useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDateTime, formatNumber } from '@/lib/date-time';
import { shipmentsWorkspace } from '@/lib/shipments-workspace';

function Field({ label, children }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs font-semibold text-muted-foreground">{label}</dt>
            <dd className="mt-1 break-words text-sm text-foreground" dir="auto">{children || '—'}</dd>
        </div>
    );
}

export default function ShipmentShow({ workspace, shipmentId }) {
    const { importShipments: t, common } = useI18n();
    const locale = useLocale();
    const { Layout, apiBase, routeName } = shipmentsWorkspace(workspace);
    const [status, setStatus] = useState('loading');
    const [shipment, setShipment] = useState(null);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    useEffect(() => {
        window.axios
            .get(`${apiBase}/${shipmentId}`, { silent: true })
            .then((res) => {
                setShipment(res.data.data);
                setStatus('ready');
            })
            .catch(() => setStatus('error'));
    }, [apiBase, shipmentId]);

    const confirmDelete = () => {
        setIsDeleting(true);
        window.axios
            .delete(`${apiBase}/${shipmentId}`, { silent: true })
            .then(() => router.visit(route(routeName('index'))))
            .catch(() => setIsDeleting(false));
    };

    if (status !== 'ready') {
        return (
            <Layout title={t.details_title}>
                {status === 'loading' ? <Skeleton className="h-96 w-full" /> : <p className="text-sm text-[var(--color-danger-strong)]">{common.generic_error}</p>}
            </Layout>
        );
    }

    const items = shipment.items ?? [];
    const itemsTotal = items.reduce((sum, item) => sum + Number(item.total_price || 0), 0);
    const quantityTotal = items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);

    return (
        <Layout title={t.details_title}>
            <PageHeader
                breadcrumb={[{ label: t.title, href: route(routeName('index')) }, { label: `${t.shipment_number} ${shipment.id}` }]}
                title={shipment.merchant_name}
                copy={`${t.shipment_number} ${shipment.id} · ${formatDateTime(shipment.created_at, locale)}`}
                actions={
                    <>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route(routeName('edit'), shipment.id)}>{common.edit}</Link>
                        </Button>
                        <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setConfirmOpen(true)}>
                            {common.delete}
                        </Button>
                    </>
                }
            />

            <div className="grid gap-5 lg:grid-cols-2">
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="pb-2"><CardTitle className="text-base">{t.section_merchant}</CardTitle></CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Field label={t.merchant_name}>{shipment.merchant_name}</Field>
                            <Field label={t.merchant_register_number}>{shipment.merchant_register_number}</Field>
                            <Field label={t.merchant_address}>{shipment.merchant_address}</Field>
                            <Field label={t.invoice_number}>{shipment.invoice_number}</Field>
                            <Field label={t.port}>{shipment.port?.name}</Field>
                            {shipment.syndicate && <Field label={t.syndicate}>{shipment.syndicate.name}</Field>}
                            <Field label={t.created_by}>{shipment.creator?.name}</Field>
                        </dl>
                    </CardContent>
                </Card>

                <Card className="border-border/80 shadow-none">
                    <CardHeader className="pb-2"><CardTitle className="text-base">{t.section_syndicate}</CardTitle></CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Field label={t.stickers_count}>{formatNumber(shipment.stickers_count, locale)}</Field>
                            <Field label={t.sticker_price}>{formatNumber(shipment.sticker_price, locale)}</Field>
                            <Field label={t.stickers_total}>{formatCurrency(shipment.stickers_total, locale)}</Field>
                            <Field label={t.clearance_number}>{shipment.clearance_number}</Field>
                            <div className="sm:col-span-2">
                                <dt className="text-xs font-semibold text-muted-foreground">{t.release_decision}</dt>
                                <dd className="mt-1 flex flex-col items-start gap-2 text-sm text-foreground">
                                    {shipment.release_decision ? <StatusBadge tone="success">{t.released}</StatusBadge> : <StatusBadge tone="warning">{t.not_released}</StatusBadge>}
                                    {shipment.release_decision && <p className="whitespace-pre-line break-words" dir="auto">{shipment.release_decision}</p>}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="pb-2"><CardTitle className="text-base">{t.section_items}</CardTitle></CardHeader>
                <CardContent className="overflow-x-auto p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t.item_name}</TableHead>
                                <TableHead>{t.item_country}</TableHead>
                                <TableHead className="text-end">{t.item_quantity}</TableHead>
                                <TableHead className="text-end">{t.item_total_price}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell dir="auto">{item.name}</TableCell>
                                    <TableCell dir="auto">{item.country}</TableCell>
                                    <TableCell className="text-end tabular-nums">{formatNumber(item.quantity, locale)}</TableCell>
                                    <TableCell className="text-end tabular-nums" dir="auto">{formatCurrency(item.total_price, locale)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                        <TableFooter>
                            <TableRow>
                                <TableCell colSpan={2} className="font-semibold">{t.items_count}: {formatNumber(items.length, locale)}</TableCell>
                                <TableCell className="text-end font-semibold tabular-nums">{formatNumber(quantityTotal, locale)}</TableCell>
                                <TableCell className="text-end font-semibold tabular-nums" dir="auto">{formatCurrency(itemsTotal, locale)}</TableCell>
                            </TableRow>
                        </TableFooter>
                    </Table>
                </CardContent>
            </Card>

            <DeleteConfirmDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                title={t.delete_title}
                description={t.delete_warning}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </Layout>
    );
}
