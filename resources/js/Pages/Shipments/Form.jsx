import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2, Plus, Trash2 } from 'lucide-react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { SelectField, TextareaField, TextField } from '@/Components/admin/form/FormField';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';
import { emptyShipmentItem, shipmentsWorkspace } from '@/lib/shipments-workspace';

const NO_SYNDICATE = 'none';

const INITIAL_FORM = {
    merchant_name: '',
    merchant_register_number: '',
    merchant_address: '',
    invoice_number: '',
    port_id: '',
    syndicate_id: NO_SYNDICATE,
    stickers_count: '',
    sticker_price: '200',
    clearance_number: '',
    release_decision: '',
};

function Section({ title, children }) {
    return (
        <Card className="border-border/80 shadow-none">
            <CardHeader className="pb-2">
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-5 sm:grid-cols-2">{children}</CardContent>
        </Card>
    );
}

export default function ShipmentForm({ workspace, shipmentId }) {
    const { importShipments: t, common } = useI18n();
    const locale = useLocale();
    const { Layout, apiBase, routeName } = shipmentsWorkspace(workspace);
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const isEdit = !!shipmentId;

    const [status, setStatus] = useState(isEdit ? 'loading' : 'ready');
    const [form, setForm] = useState(INITIAL_FORM);
    const [items, setItems] = useState([emptyShipmentItem()]);
    const [options, setOptions] = useState({ ports: [], syndicates: [], sticker_prices: [200, 400], can_pick_syndicate: false });

    useEffect(() => {
        window.axios.get(`${apiBase}/options`, { silent: true }).then((res) => setOptions((current) => res.data?.data ?? current));

        if (!isEdit) return;
        window.axios
            .get(`${apiBase}/${shipmentId}`, { silent: true })
            .then((res) => {
                const data = res.data.data;
                setForm({
                    merchant_name: data.merchant_name ?? '',
                    merchant_register_number: data.merchant_register_number ?? '',
                    merchant_address: data.merchant_address ?? '',
                    invoice_number: data.invoice_number ?? '',
                    port_id: data.port_id ? String(data.port_id) : '',
                    syndicate_id: data.syndicate_id ? String(data.syndicate_id) : NO_SYNDICATE,
                    stickers_count: String(data.stickers_count ?? ''),
                    sticker_price: String(data.sticker_price ?? '200'),
                    clearance_number: data.clearance_number ?? '',
                    release_decision: data.release_decision ?? '',
                });
                setItems(
                    (data.items ?? []).map((item) => ({
                        name: item.name,
                        country: item.country,
                        quantity: String(item.quantity),
                        total_price: String(item.total_price),
                    })),
                );
                setStatus('ready');
            })
            .catch(() => setStatus('error'));
    }, [apiBase, shipmentId, isEdit]);

    const setField = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }));
    const setItemField = (index, key) => (event) => {
        const value = event.target.value;
        setItems((current) => current.map((item, i) => (i === index ? { ...item, [key]: value } : item)));
    };

    const stickersTotal = (Number(form.stickers_count) || 0) * (Number(form.sticker_price) || 0);

    const handleSubmit = async (event) => {
        event.preventDefault();
        const payload = {
            ...form,
            port_id: form.port_id ? Number(form.port_id) : null,
            syndicate_id: options.can_pick_syndicate && form.syndicate_id !== NO_SYNDICATE ? Number(form.syndicate_id) : null,
            stickers_count: form.stickers_count === '' ? null : Number(form.stickers_count),
            sticker_price: Number(form.sticker_price),
            clearance_number: form.clearance_number || null,
            release_decision: form.release_decision || null,
            items: items.map((item) => ({
                name: item.name,
                country: item.country,
                quantity: item.quantity === '' ? null : Number(item.quantity),
                total_price: item.total_price === '' ? null : Number(item.total_price),
            })),
        };

        try {
            const data = isEdit ? await submit('put', `${apiBase}/${shipmentId}`, payload) : await submit('post', apiBase, payload);
            router.visit(route(routeName('show'), data.data.id));
        } catch {
            // field errors are handled by useAdminForm
        }
    };

    const title = isEdit ? t.edit_title : t.create_title;

    if (status !== 'ready') {
        return (
            <Layout title={title}>
                {status === 'loading' ? <Skeleton className="h-96 w-full" /> : <p className="text-sm text-[var(--color-danger-strong)]">{common.generic_error}</p>}
            </Layout>
        );
    }

    return (
        <Layout title={title}>
            <PageHeader breadcrumb={[{ label: t.title, href: route(routeName('index')) }, { label: title }]} title={title} />

            {generalError && <p role="alert" className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

            <form onSubmit={handleSubmit} className="flex flex-col gap-5">
                <Section title={t.section_merchant}>
                    <TextField id="merchant_name" label={t.merchant_name} required value={form.merchant_name} onChange={setField('merchant_name')} error={errors.merchant_name} />
                    <TextField id="merchant_register_number" label={t.merchant_register_number} required value={form.merchant_register_number} onChange={setField('merchant_register_number')} error={errors.merchant_register_number} />
                    <TextField id="merchant_address" label={t.merchant_address} required value={form.merchant_address} onChange={setField('merchant_address')} error={errors.merchant_address} />
                    <TextField id="invoice_number" label={t.invoice_number} required value={form.invoice_number} onChange={setField('invoice_number')} error={errors.invoice_number} />
                </Section>

                <Section title={t.section_port}>
                    <SelectField
                        id="port_id"
                        label={t.port}
                        required
                        value={form.port_id}
                        onValueChange={(value) => setForm((current) => ({ ...current, port_id: value }))}
                        placeholder={t.select_port}
                        error={errors.port_id}
                        options={options.ports.map((port) => ({ value: port.id, label: port.name }))}
                    />
                    {options.can_pick_syndicate && (
                        <SelectField
                            id="syndicate_id"
                            label={t.syndicate}
                            value={form.syndicate_id}
                            onValueChange={(value) => setForm((current) => ({ ...current, syndicate_id: value }))}
                            error={errors.syndicate_id}
                            options={[{ value: NO_SYNDICATE, label: t.select_syndicate }, ...options.syndicates.map((syndicate) => ({ value: syndicate.id, label: syndicate.name }))]}
                        />
                    )}
                </Section>

                <Card className="border-border/80 shadow-none">
                    <CardHeader className="flex flex-row items-center justify-between gap-3 pb-2">
                        <CardTitle className="text-base">{t.section_items}</CardTitle>
                        <Button type="button" variant="outline" size="sm" onClick={() => setItems((current) => [...current, emptyShipmentItem()])}>
                            <Plus className="size-4" />
                            {t.add_item}
                        </Button>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        {errors.items && <p className="text-sm font-medium text-[var(--color-danger-strong)]">{errors.items}</p>}
                        {items.map((item, index) => (
                            <fieldset key={index} className="grid gap-4 rounded-lg border border-border p-4 sm:grid-cols-2 lg:grid-cols-[2fr_1.5fr_1fr_1.5fr_auto] lg:items-end">
                                <legend className="sr-only">{`${t.section_items} ${index + 1}`}</legend>
                                <TextField id={`items_${index}_name`} label={t.item_name} required value={item.name} onChange={setItemField(index, 'name')} error={errors[`items.${index}.name`]} />
                                <TextField id={`items_${index}_country`} label={t.item_country} required value={item.country} onChange={setItemField(index, 'country')} error={errors[`items.${index}.country`]} />
                                <TextField id={`items_${index}_quantity`} type="number" min="1" step="1" inputMode="numeric" label={t.item_quantity} required value={item.quantity} onChange={setItemField(index, 'quantity')} error={errors[`items.${index}.quantity`]} />
                                <TextField id={`items_${index}_total_price`} type="number" min="0" step="0.01" inputMode="decimal" label={t.item_total_price} required value={item.total_price} onChange={setItemField(index, 'total_price')} error={errors[`items.${index}.total_price`]} />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t.remove_item}
                                    title={t.remove_item}
                                    disabled={items.length === 1}
                                    className="text-[var(--color-danger-strong)]"
                                    onClick={() => setItems((current) => current.filter((_, i) => i !== index))}
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </fieldset>
                        ))}
                    </CardContent>
                </Card>

                <Section title={t.section_syndicate}>
                    <TextField id="stickers_count" type="number" min="0" step="1" inputMode="numeric" label={t.stickers_count} required value={form.stickers_count} onChange={setField('stickers_count')} error={errors.stickers_count} />
                    <SelectField
                        id="sticker_price"
                        label={t.sticker_price}
                        required
                        value={form.sticker_price}
                        onValueChange={(value) => setForm((current) => ({ ...current, sticker_price: value }))}
                        error={errors.sticker_price}
                        options={options.sticker_prices.map((price) => ({ value: price, label: formatNumber(price, locale) }))}
                    />
                    <div className="rounded-md border border-border bg-muted px-4 py-3 text-sm text-muted-foreground sm:col-span-2">
                        {t.stickers_total}: <span className="font-semibold tabular-nums text-foreground"><bdi>{formatNumber(stickersTotal, locale)}</bdi></span>
                    </div>
                    <TextField id="clearance_number" label={t.clearance_number} value={form.clearance_number} onChange={setField('clearance_number')} error={errors.clearance_number} />
                    <TextareaField id="release_decision" label={t.release_decision} rows={3} value={form.release_decision} onChange={setField('release_decision')} error={errors.release_decision} />
                </Section>

                <div className="flex gap-2">
                    <Button type="button" variant="outline" className="flex-1 sm:flex-none" onClick={() => router.visit(route(routeName('index')))}>
                        {common.cancel}
                    </Button>
                    <Button type="submit" className="flex-1 sm:flex-none sm:min-w-40" disabled={isSubmitting}>
                        {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                        {t.save}
                    </Button>
                </div>
            </form>
        </Layout>
    );
}
