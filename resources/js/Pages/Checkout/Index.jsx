import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight, Loader2 } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';

const ADDRESS_LABELS = ['home', 'work', 'farm', 'clinic', 'pharmacy', 'other'];

function money(amount, currency, locale) {
    return `${formatNumber(amount, locale, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ${currency || ''}`.trim();
}

export default function CheckoutIndex() {
    const { checkout, nav, addresses: addressesI18n } = useI18n();
    const locale = useLocale();

    const [status, setStatus] = useState('loading');
    const [summary, setSummary] = useState(null);
    const [selectedAddressId, setSelectedAddressId] = useState(null);
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(null);
    const [couponCode, setCouponCode] = useState('');
    const [errorMessage, setErrorMessage] = useState(null);
    const [showAddressForm, setShowAddressForm] = useState(false);
    const [addressForm, setAddressForm] = useState({ label: 'home', recipient_name: '', phone: '', governorate: '', city: '', district: '', street: '', notes: '' });
    const [addressErrors, setAddressErrors] = useState({});
    const [isPlacing, setIsPlacing] = useState(false);
    const [success, setSuccess] = useState(null);

    const loadSummary = () => {
        window.axios.get('/api/checkout/summary', { silent: true }).then((res) => {
            const data = res.data?.data;
            setSummary(data);
            const addrs = data?.addresses ?? [];
            if (addrs.length && !addrs.some((a) => a.id === selectedAddressId)) {
                setSelectedAddressId((addrs.find((a) => a.is_default) || addrs[0]).id);
            }
            const methods = data?.payment_methods ?? [];
            if (!methods.includes(selectedPaymentMethod)) setSelectedPaymentMethod(methods[0] ?? null);
            if (data?.coupon?.code) setCouponCode(data.coupon.code);
            (data?.notices ?? []).forEach((notice) => window.AppToast?.show(notice, 'warning'));
            setStatus('ready');
        }).catch((err) => {
            setStatus('error');
            setErrorMessage(err.response?.data?.message || null);
        });
    };

    useEffect(loadSummary, []);

    const applyCoupon = () => {
        const code = couponCode.trim();
        if (!code) return;
        window.axios.post('/api/cart/coupon', { coupon_code: code }, { silent: true }).then(() => {
            setErrorMessage(null);
            loadSummary();
        }).catch((err) => {
            setErrorMessage(err.response?.data?.message || null);
            loadSummary();
        });
    };

    const submitAddress = (event) => {
        event.preventDefault();
        setAddressErrors({});
        window.axios.post('/api/addresses', addressForm, { silent: true }).then((res) => {
            setSelectedAddressId(res.data?.data?.id ?? selectedAddressId);
            setAddressForm({ label: 'home', recipient_name: '', phone: '', governorate: '', city: '', district: '', street: '', notes: '' });
            setShowAddressForm(false);
            setErrorMessage(null);
            loadSummary();
        }).catch((err) => {
            const fieldErrors = {};
            Object.entries(err.response?.data?.errors ?? {}).forEach(([field, messages]) => { fieldErrors[field] = messages[0]; });
            setAddressErrors(fieldErrors);
            setErrorMessage(Object.values(fieldErrors)[0] || err.response?.data?.message || null);
        });
    };

    const placeOrder = () => {
        setErrorMessage(null);
        if (!selectedAddressId) {
            setErrorMessage(checkout.address_required);
            return;
        }
        setIsPlacing(true);
        window.axios.post('/api/checkout', { address_id: selectedAddressId, payment_method: selectedPaymentMethod }, { silent: true }).then((res) => {
            setSuccess({ message: res.data?.message, orders: res.data?.data?.orders ?? [] });
            window.refreshCart?.();
            setIsPlacing(false);
        }).catch((err) => {
            setErrorMessage(err.response?.data?.message || null);
            setIsPlacing(false);
            loadSummary();
        });
    };

    const currency = summary?.totals?.currency;
    const items = summary?.cart?.items ?? [];
    const vendorCount = new Set(items.map((i) => i.vendor_id)).size;
    const totals = summary?.totals ?? {};

    return (
        <PublicLayout title={checkout.title} noindex>
            <div className="bg-transparent">
                <div className="catalog-page-band">
                    <div className="page-shell py-3">
                        <nav className="page-breadcrumb">
                            <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <span className="font-medium text-foreground">{checkout.title}</span>
                        </nav>
                    </div>
                </div>

                <div className="page-shell py-6">
                    <h1 className="mb-6 text-2xl font-bold text-foreground">{checkout.title}</h1>

                    {status === 'loading' && (
                        <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                            <Skeleton className="h-96 w-full" />
                            <Skeleton className="h-64 w-full" />
                        </div>
                    )}

                    {status === 'error' && !success && (
                        <div className="rounded-lg border border-border bg-card p-10 text-center">
                            <p className="text-sm font-medium text-[var(--color-danger-strong)]">{errorMessage}</p>
                        </div>
                    )}

                    {success && (
                        <div className="rounded-lg border border-[var(--color-success-200)] bg-[var(--color-success-soft)] p-8 text-center">
                            <h2 className="text-lg font-bold text-[var(--color-success-strong)]">{checkout.order_placed}</h2>
                            <p className="mt-2 text-sm text-[var(--color-success-strong)]">{success.message}</p>
                            <div className="mx-auto mt-4 max-w-sm space-y-1 text-sm text-[var(--color-success-strong)]">
                                {success.orders.map((order) => <p key={order.order_number} className="font-mono text-xs">{order.order_number}</p>)}
                            </div>
                            <Link href={route('profile')} className="btn-primary mt-6 inline-flex">{checkout.view_orders}</Link>
                        </div>
                    )}

                    {status === 'ready' && !success && items.length === 0 && (
                        <div className="rounded-lg border border-border bg-card p-10 text-center">
                            <p className="text-base font-semibold text-foreground">{checkout.empty_cart}</p>
                            <Link href={route('products.index')} className="mt-4 inline-flex rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                                {checkout.back_to_shopping}
                            </Link>
                        </div>
                    )}

                    {status === 'ready' && !success && items.length > 0 && (
                        <div className="grid gap-6 lg:grid-cols-[1fr_360px] lg:items-start">
                            <div className="space-y-6">
                                <section className="surface-card p-5">
                                    <div className="mb-4 flex items-center justify-between gap-3">
                                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{checkout.delivery_address}</h2>
                                        <button type="button" onClick={() => setShowAddressForm((v) => !v)} className="text-xs font-semibold text-primary hover:underline">{checkout.new_address}</button>
                                    </div>

                                    <div className="space-y-2">
                                        {(summary?.addresses ?? []).map((address) => {
                                            const checked = address.id === selectedAddressId;
                                            const line = [address.street, address.district, address.city, address.governorate].filter(Boolean).join('، ');
                                            return (
                                                <label key={address.id} className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors ${checked ? 'border-primary bg-accent/40' : 'border-border hover:border-primary/40'}`}>
                                                    <input type="radio" name="checkout-address" checked={checked} onChange={() => setSelectedAddressId(address.id)} className="mt-1 size-4 shrink-0 accent-primary" />
                                                    <span className="min-w-0 flex-1">
                                                        <span className="flex flex-wrap items-center gap-2">
                                                            <span className="text-sm font-semibold text-foreground">{address.recipient_name}</span>
                                                            {address.is_default && <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground">{addressesI18n.default}</span>}
                                                        </span>
                                                        <span className="mt-0.5 block text-xs text-muted-foreground">{line}</span>
                                                        <span className="mt-0.5 block text-xs text-muted-foreground" dir="ltr">{address.phone}</span>
                                                    </span>
                                                </label>
                                            );
                                        })}
                                        {(summary?.addresses ?? []).length === 0 && <p className="text-sm text-muted-foreground">{checkout.no_addresses}</p>}
                                    </div>

                                    {showAddressForm && (
                                        <form onSubmit={submitAddress} className="mt-4 space-y-3 border-t border-border pt-4">
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label htmlFor="addr-label" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.field.label}</label>
                                                    <select id="addr-label" className="form-select w-full" value={addressForm.label} onChange={(e) => setAddressForm((f) => ({ ...f, label: e.target.value }))}>
                                                        {ADDRESS_LABELS.map((l) => <option key={l} value={l}>{addressesI18n.label?.[l] ?? l}</option>)}
                                                    </select>
                                                </div>
                                                <div>
                                                    <label htmlFor="addr-recipient" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.field.recipient_name}</label>
                                                    <input id="addr-recipient" type="text" required className="form-input" value={addressForm.recipient_name} onChange={(e) => setAddressForm((f) => ({ ...f, recipient_name: e.target.value }))} />
                                                    {addressErrors.recipient_name && <p className="form-error">{addressErrors.recipient_name}</p>}
                                                </div>
                                                <div>
                                                    <label htmlFor="addr-phone" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.field.phone}</label>
                                                    <input id="addr-phone" type="tel" required dir="ltr" className="form-input" value={addressForm.phone} onChange={(e) => setAddressForm((f) => ({ ...f, phone: e.target.value }))} />
                                                    {addressErrors.phone && <p className="form-error">{addressErrors.phone}</p>}
                                                </div>
                                                <div>
                                                    <label htmlFor="addr-governorate" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.field.governorate}</label>
                                                    <input id="addr-governorate" type="text" required className="form-input" value={addressForm.governorate} onChange={(e) => setAddressForm((f) => ({ ...f, governorate: e.target.value }))} />
                                                    {addressErrors.governorate && <p className="form-error">{addressErrors.governorate}</p>}
                                                </div>
                                                <div>
                                                    <label htmlFor="addr-city" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.field.city}</label>
                                                    <input id="addr-city" type="text" required className="form-input" value={addressForm.city} onChange={(e) => setAddressForm((f) => ({ ...f, city: e.target.value }))} />
                                                    {addressErrors.city && <p className="form-error">{addressErrors.city}</p>}
                                                </div>
                                                <div>
                                                    <label htmlFor="addr-district" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.field.district}</label>
                                                    <input id="addr-district" type="text" className="form-input" value={addressForm.district} onChange={(e) => setAddressForm((f) => ({ ...f, district: e.target.value }))} />
                                                </div>
                                                <div className="sm:col-span-2">
                                                    <label htmlFor="addr-street" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.field.street}</label>
                                                    <input id="addr-street" type="text" className="form-input" value={addressForm.street} onChange={(e) => setAddressForm((f) => ({ ...f, street: e.target.value }))} />
                                                </div>
                                                <div className="sm:col-span-2">
                                                    <label htmlFor="addr-notes" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.field.notes}</label>
                                                    <textarea id="addr-notes" rows={2} className="form-textarea" value={addressForm.notes} onChange={(e) => setAddressForm((f) => ({ ...f, notes: e.target.value }))} />
                                                </div>
                                            </div>
                                            <div className="flex gap-2">
                                                <button type="submit" className="btn-primary">{checkout.save_address}</button>
                                                <button type="button" onClick={() => setShowAddressForm(false)} className="btn-secondary">{checkout.cancel}</button>
                                            </div>
                                        </form>
                                    )}
                                </section>

                                <section className="surface-card p-5">
                                    <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">{checkout.order_review}</h2>
                                    {vendorCount > 1 && (
                                        <p className="mb-4 rounded-lg border border-[var(--color-info-200)] bg-[var(--color-info-soft)] px-3 py-2 text-xs font-medium text-[var(--color-info-strong)]">{checkout.multi_vendor_notice}</p>
                                    )}
                                    <div className="divide-y divide-border">
                                        {items.map((item) => (
                                            <div key={item.product_id} className="flex items-start justify-between gap-4 py-3">
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium text-foreground">{item.name}</p>
                                                    {item.vendor_name && <p className="truncate text-xs text-muted-foreground">{checkout.sold_by} {item.vendor_name}</p>}
                                                    <p className="mt-0.5 text-xs text-muted-foreground">{checkout.quantity_short} {item.quantity} × {money(item.unit_price, currency, locale)}</p>
                                                </div>
                                                <p className="shrink-0 text-sm font-semibold tabular-nums text-foreground">{money(item.line_total, currency, locale)}</p>
                                            </div>
                                        ))}
                                    </div>
                                </section>

                                <section className="surface-card p-5">
                                    <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">{checkout.payment_method}</h2>
                                    <div className="space-y-2">
                                        {(summary?.payment_methods ?? []).map((method) => {
                                            const checked = method === selectedPaymentMethod;
                                            return (
                                                <label key={method} className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 ${checked ? 'border-primary bg-accent/40' : 'border-border'}`}>
                                                    <input type="radio" name="checkout-payment" checked={checked} onChange={() => setSelectedPaymentMethod(method)} className="mt-1 size-4 shrink-0 accent-primary" />
                                                    <span>
                                                        <span className="block text-sm font-semibold text-foreground">{checkout.cash_on_delivery}</span>
                                                        <span className="mt-0.5 block text-xs text-muted-foreground">{checkout.cash_on_delivery_hint}</span>
                                                    </span>
                                                </label>
                                            );
                                        })}
                                        {(summary?.payment_methods ?? []).length === 1 && <p className="pt-1 text-xs text-muted-foreground">{checkout.only_method_available}</p>}
                                    </div>
                                </section>
                            </div>

                            <aside className="surface-card p-5 lg:sticky lg:top-24">
                                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">{checkout.total}</h2>

                                <div className="mb-4">
                                    <label htmlFor="checkout-coupon" className="mb-1 block text-xs font-semibold text-muted-foreground">{checkout.discount_code}</label>
                                    <div className="flex gap-2">
                                        <input id="checkout-coupon" type="text" placeholder={checkout.coupon_placeholder} className="form-input min-w-0 flex-1" value={couponCode} onChange={(e) => setCouponCode(e.target.value)} />
                                        <button type="button" onClick={applyCoupon} className="btn-secondary shrink-0">{checkout.apply}</button>
                                    </div>
                                </div>

                                <dl className="space-y-2 border-t border-border pt-4 text-sm">
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">{checkout.subtotal}</dt>
                                        <dd className="font-medium tabular-nums text-foreground">{money(totals.subtotal, currency, locale)}</dd>
                                    </div>
                                    {Number(totals.discount_total) > 0 && (
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">{checkout.discount}</dt>
                                            <dd className="font-medium tabular-nums text-[var(--color-success-strong)]">− {money(totals.discount_total, currency, locale)}</dd>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">{checkout.shipping}</dt>
                                        <dd className="font-medium tabular-nums text-foreground">{Number(totals.shipping_total) > 0 ? money(totals.shipping_total, currency, locale) : checkout.shipping_free}</dd>
                                    </div>
                                    {Number(totals.tax_total) > 0 && (
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">{checkout.tax}</dt>
                                            <dd className="font-medium tabular-nums text-foreground">{money(totals.tax_total, currency, locale)}</dd>
                                        </div>
                                    )}
                                    <div className="flex justify-between border-t border-border pt-3">
                                        <dt className="font-semibold text-foreground">{checkout.total}</dt>
                                        <dd className="text-lg font-bold tabular-nums text-foreground">{money(totals.grand_total, currency, locale)}</dd>
                                    </div>
                                </dl>

                                {errorMessage && (
                                    <div className="mt-4 rounded-lg border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-3 py-2 text-xs font-semibold text-[var(--color-danger-strong)]" role="alert">{errorMessage}</div>
                                )}

                                <button type="button" onClick={placeOrder} disabled={isPlacing} className="btn-primary mt-5 flex w-full items-center justify-center gap-2">
                                    {isPlacing && <Loader2 className="size-4 animate-spin" />}
                                    {isPlacing ? checkout.placing_order : checkout.place_order}
                                </button>
                            </aside>
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
