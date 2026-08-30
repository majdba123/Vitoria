import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight, Camera, Heart, ImageOff, Loader2, Check } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Skeleton } from '@/Components/ui/skeleton';
import { useFavourites } from '@/hooks/use-favourites';
import { useAuthUser, useI18n } from '@/hooks/use-i18n';

const ROLE_LABEL = { 0: 'Customer', 1: 'Admin', 2: 'Business account' };
const ORDER_STATUS_CLASS = {
    pending: 'bg-[var(--color-warning-soft)] text-[var(--color-warning-strong)]',
    confirmed: 'bg-[var(--color-success-soft)] text-[var(--color-success-strong)]',
    preparing: 'bg-[var(--color-success-soft)] text-[var(--color-success-strong)]',
    shipped: 'bg-[var(--color-info-soft)] text-[var(--color-info-strong)]',
    out_for_delivery: 'bg-[var(--color-info-soft)] text-[var(--color-info-strong)]',
    completed: 'bg-[var(--color-info-soft)] text-[var(--color-info-strong)]',
    cancelled: 'bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]',
};

function FavouritesPanel() {
    const { profile } = useI18n();
    const { toggle } = useFavourites();
    const [status, setStatus] = useState('loading');
    const [products, setProducts] = useState([]);

    useEffect(() => {
        window.axios.get('/api/favourites', { silent: true }).then((res) => {
            setProducts(res.data?.data ?? []);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, []);

    const remove = (productId) => {
        toggle(productId);
        setProducts((rows) => rows.filter((p) => p.id !== productId));
    };

    return (
        <div className="surface-card p-6">
            <div className="mb-5 flex items-center gap-3">
                <div className="flex size-10 items-center justify-center rounded-md bg-[var(--color-danger-soft)]">
                    <Heart className="size-5 text-[var(--color-danger-strong)]" fill="currentColor" />
                </div>
                <div>
                    <h3 className="text-base font-bold text-foreground">{profile.my_favourites}</h3>
                    <p className="text-xs text-muted-foreground">
                        {status === 'loading' ? 'Loading...' : `${products.length} product${products.length !== 1 ? 's' : ''}`}
                    </p>
                </div>
            </div>

            {status === 'loading' && (
                <div className="responsive-product-grid">
                    {Array.from({ length: 3 }).map((_, i) => <Skeleton key={i} className="h-52 rounded-lg" />)}
                </div>
            )}

            {status === 'ready' && products.length === 0 && (
                <div className="py-12 text-center">
                    <Heart className="mx-auto size-12 text-muted-foreground/20" />
                    <p className="mt-3 text-sm font-bold text-muted-foreground">{profile.no_favourites}</p>
                    <p className="mt-1 text-xs text-muted-foreground">{profile.add_favourites_hint}</p>
                    <Link href={route('products.index')} className="btn-primary mt-4 inline-flex">{profile.browse_products}</Link>
                </div>
            )}

            {status === 'ready' && products.length > 0 && (
                <div className="responsive-product-grid">
                    {products.map((p) => (
                        <div key={p.id} className="group relative overflow-hidden rounded-lg border border-border bg-card transition-all hover:shadow-md">
                            <button onClick={() => remove(p.id)} aria-label={profile.remove_favourite_aria} className="absolute end-2 top-2 z-10 flex size-10 items-center justify-center border border-border bg-card text-[var(--color-danger-strong)] transition-colors hover:bg-[var(--color-danger-soft)]">
                                <Heart className="size-4" fill="currentColor" />
                            </button>
                            <Link href={route('products.show', p.id)}>
                                <div className="aspect-square overflow-hidden bg-muted">
                                    {p.first_photo_url ? (
                                        <img src={p.first_photo_url} className="size-full object-contain p-3 transition-transform duration-300 group-hover:scale-105" loading="lazy" alt="" />
                                    ) : (
                                        <div className="flex size-full items-center justify-center"><ImageOff className="size-10 text-muted-foreground/20" /></div>
                                    )}
                                </div>
                                <div className="p-3">
                                    <h4 className="line-clamp-2 text-xs font-bold text-foreground group-hover:text-primary">{p.name}</h4>
                                    <div className="mt-1.5 flex items-baseline gap-1">
                                        <span className="text-sm font-bold text-foreground">{parseFloat(p.price).toLocaleString()}</span>
                                        <span className="text-[10px] text-muted-foreground">SYP</span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

function OrderCard({ order }) {
    const { profile } = useI18n();
    const date = order.created_at ? new Date(order.created_at).toLocaleDateString() : '—';
    const statusClass = ORDER_STATUS_CLASS[String(order.status || '').toLowerCase()] ?? ORDER_STATUS_CLASS.pending;

    return (
        <article className="overflow-hidden border-b border-border bg-card">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-muted/40 px-4 py-3">
                <div>
                    <span className="badge badge-brand">{order.order_number || `${profile.order_prefix}${order.id}`}</span>
                    <p className="mt-1 text-xs text-muted-foreground">{date}</p>
                </div>
                <div className="flex items-center gap-2">
                    <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold ${statusClass}`}>{String(order.status || 'pending').toLowerCase()}</span>
                    <span className="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground">{order.payment_way || 'cash'}</span>
                </div>
            </div>

            <div className="p-4">
                <div className="space-y-2">
                    {(order.items || []).map((item, index) => {
                        const original = parseFloat(item.original_unit_price || 0).toLocaleString();
                        const unit = parseFloat(item.unit_price || 0).toLocaleString();
                        const total = parseFloat(item.line_total || 0).toLocaleString();
                        return (
                            <div key={item.id ?? index} className="rounded-md border border-border p-2.5">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-xs font-bold text-foreground">{item.product_name}</p>
                                        <p className="text-[11px] text-muted-foreground">{profile.quantity_short} {item.quantity} · {unit} SYP {profile.each}</p>
                                        {item.has_discount && (
                                            <>
                                                <p className="text-[11px] text-muted-foreground line-through">{profile.original_price} {original} SYP</p>
                                                <p className="text-[11px] text-[var(--color-success-strong)]">{profile.discount} {parseFloat(item.applied_discount_percentage || 0)}% · {profile.saved} {parseFloat(item.discount_amount || 0).toLocaleString()} SYP</p>
                                            </>
                                        )}
                                    </div>
                                    <p className="shrink-0 text-xs font-bold text-foreground">{total} SYP</p>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        { label: profile.order_id, value: order.id ?? '—' },
                        { label: profile.items, value: order.items_count ?? (order.items || []).length },
                        { label: profile.subtotal, value: `${parseFloat(order.subtotal_amount || 0).toLocaleString()} SYP` },
                        { label: profile.coupon, value: order.coupon ? order.coupon.code : '—' },
                    ].map((item) => (
                        <div key={item.label} className="storefront-spec-card">
                            <p className="text-muted-foreground">{item.label}</p>
                            <p className="mt-0.5 font-semibold text-foreground">{item.value}</p>
                        </div>
                    ))}
                </div>

                <div className="mt-3 flex items-center justify-between border-t border-border pt-3 text-sm">
                    <p className="font-bold text-foreground">{profile.total}: {parseFloat(order.total_amount || 0).toLocaleString()} SYP</p>
                    <Link href={route('orders.show', order.id)} className="btn-secondary btn-xs inline-flex items-center gap-1">
                        {profile.view_details}
                        <ChevronRight className="size-3.5 rtl:-scale-x-100" />
                    </Link>
                </div>
            </div>
        </article>
    );
}

function OrderHistoryPanel() {
    const { profile, pagination } = useI18n();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [searchDraft, setSearchDraft] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [status, setStatus] = useState('loading');
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState(null);

    useEffect(() => {
        const timer = setTimeout(() => { setSearch(searchDraft); setPage(1); }, 300);
        return () => clearTimeout(timer);
    }, [searchDraft]);

    useEffect(() => {
        setStatus('loading');
        window.axios.get('/api/orders', { params: { page, status: statusFilter || undefined, search: search || undefined }, silent: true }).then((res) => {
            setRows(res.data?.data ?? []);
            setMeta(res.data?.meta ?? null);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [page, statusFilter, search]);

    const resetFilters = () => {
        setSearchDraft('');
        setSearch('');
        setStatusFilter('');
        setPage(1);
    };

    return (
        <div className="surface-card p-6">
            <div className="mb-5 flex items-center justify-between">
                <div>
                    <h3 className="text-base font-bold text-foreground">{profile.order_history}</h3>
                    <p className="text-xs text-muted-foreground">{status === 'loading' ? 'Loading...' : `${meta?.total ?? rows.length} order${(meta?.total ?? rows.length) !== 1 ? 's' : ''}`}</p>
                </div>
                <p className="rounded-full bg-muted px-3 py-1 text-[11px] font-semibold text-muted-foreground">{profile.payment_cash}</p>
            </div>

            <div className="filter-grid mb-4">
                <input type="search" placeholder={profile.search_order} className="form-input text-xs" value={searchDraft} onChange={(e) => setSearchDraft(e.target.value)} />
                <select className="form-select text-xs" value={statusFilter} onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}>
                    <option value="">{profile.all_statuses}</option>
                    <option value="pending">{profile.status_pending}</option>
                    <option value="confirmed">{profile.status_confirmed}</option>
                    <option value="completed">{profile.status_completed}</option>
                    <option value="cancelled">{profile.status_cancelled}</option>
                </select>
                <button type="button" onClick={resetFilters} className="btn-secondary btn-sm w-full sm:w-auto">{profile.reset_filters}</button>
            </div>

            {status === 'loading' && (
                <div className="space-y-3">
                    {Array.from({ length: 3 }).map((_, i) => <Skeleton key={i} className="h-20 rounded-lg" />)}
                </div>
            )}

            {status === 'ready' && rows.length === 0 && (
                <div className="py-10 text-center text-sm font-bold text-muted-foreground">{profile.no_orders}</div>
            )}

            {status === 'ready' && rows.length > 0 && (
                <>
                    <div className="space-y-3">
                        {rows.map((order) => <OrderCard key={order.id} order={order} />)}
                    </div>
                    {meta && meta.last_page > 1 && (
                        <div className="mt-5 flex items-center justify-between border-t border-border pt-4">
                            <p className="text-xs text-muted-foreground">Page {meta.current_page} of {meta.last_page}</p>
                            <div className="flex gap-2">
                                <button type="button" disabled={meta.current_page <= 1} onClick={() => setPage((p) => p - 1)} className="btn-secondary btn-xs">{pagination.previous}</button>
                                <button type="button" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)} className="btn-secondary btn-xs">{pagination.next}</button>
                            </div>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

function ContactHistoryPanel() {
    const { profile } = useI18n();
    const [page, setPage] = useState(1);
    const [status, setStatus] = useState('loading');
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState(null);

    useEffect(() => {
        setStatus('loading');
        window.axios.get('/api/contact-messages', { params: { page, per_page: 10 }, silent: true }).then((res) => {
            setRows(res.data?.data ?? []);
            setMeta(res.data?.meta ?? null);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [page]);

    return (
        <div className="surface-card p-6">
            <div className="mb-5 flex items-center gap-3">
                <div className="flex size-10 items-center justify-center rounded-md bg-accent">
                    <Check className="size-5 text-primary" />
                </div>
                <div>
                    <h3 className="text-base font-bold text-foreground">{profile.contact_history}</h3>
                    <p className="text-xs text-muted-foreground">{status === 'loading' ? 'Loading...' : `${meta?.total ?? rows.length} message${(meta?.total ?? rows.length) !== 1 ? 's' : ''}`}</p>
                </div>
            </div>

            {status === 'loading' && (
                <div className="space-y-3">
                    {Array.from({ length: 2 }).map((_, i) => <Skeleton key={i} className="h-24 rounded-lg" />)}
                </div>
            )}

            {status === 'ready' && rows.length === 0 && (
                <div className="py-10 text-center">
                    <p className="text-sm font-bold text-muted-foreground">{profile.no_contact_messages}</p>
                    <p className="mt-1 text-xs text-muted-foreground">{profile.contact_hint}</p>
                    <a href={`${route('home')}#contact`} className="btn-primary mt-4 inline-flex">{profile.contact_us}</a>
                </div>
            )}

            {status === 'ready' && rows.length > 0 && (
                <>
                    <div className="space-y-3">
                        {rows.map((m) => (
                            <article key={m.id} className="rounded-lg border border-border bg-muted/30 p-4">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold ${m.status === 'replied' ? 'bg-[var(--color-success-soft)] text-[var(--color-success-strong)]' : 'bg-[var(--color-warning-soft)] text-[var(--color-warning-strong)]'}`}>{m.status}</span>
                                    <span className="text-xs text-muted-foreground">{m.created_at ? new Date(m.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—'}</span>
                                </div>
                                <p className="mt-2 text-sm text-muted-foreground">{m.message}</p>
                                {m.admin_reply && (
                                    <div className="mt-3 rounded-md border border-border bg-card p-3">
                                        <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Admin reply{m.replied_at ? ` · ${new Date(m.replied_at).toLocaleDateString()}` : ''}</p>
                                        <p className="mt-1.5 text-sm text-foreground">{m.admin_reply}</p>
                                    </div>
                                )}
                            </article>
                        ))}
                    </div>
                    {meta && meta.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between border-t border-border pt-4">
                            <p className="text-xs text-muted-foreground">Page {meta.current_page} of {meta.last_page}</p>
                            <div className="flex gap-2">
                                <button type="button" disabled={meta.current_page <= 1} onClick={() => setPage((p) => p - 1)} className="btn-secondary btn-xs">Prev</button>
                                <button type="button" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)} className="btn-secondary btn-xs">Next</button>
                            </div>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

function ProfileForm({ user, onUpdated }) {
    const { profile } = useI18n();
    const [form, setForm] = useState({ name: user.name || '', email: user.email || '', phone_number: user.phone_number || '', timezone: user.timezone || '', preferred_product_type: user.preferred_product_type || '' });
    const [timezones, setTimezones] = useState([]);
    const [avatarFile, setAvatarFile] = useState(null);
    const [avatarRemoved, setAvatarRemoved] = useState(false);
    const [avatarPreview, setAvatarPreview] = useState(user.avatar_url || null);
    const [errors, setErrors] = useState({});
    const [successMessage, setSuccessMessage] = useState(null);
    const [generalError, setGeneralError] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        window.axios.get('/api/startup/preferences', { silent: true }).then((res) => {
            const data = res.data?.data ?? {};
            setTimezones(data.timezones ?? []);
            const browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone || data.default_timezone || 'Asia/Damascus';
            setForm((f) => ({ ...f, timezone: user.timezone || data.timezone || browserTz }));
        }).catch(() => setTimezones([{ value: 'Asia/Damascus', label: 'Asia/Damascus' }]));
    }, []);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const onAvatarChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            alert(profile.avatar_max_size);
            e.target.value = '';
            return;
        }
        setAvatarFile(file);
        setAvatarRemoved(false);
        setAvatarPreview(URL.createObjectURL(file));
    };

    const removeAvatar = () => {
        setAvatarFile(null);
        setAvatarRemoved(true);
        setAvatarPreview(null);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});
        setGeneralError(null);
        setSuccessMessage(null);
        setIsSubmitting(true);

        const fd = new FormData();
        fd.append('name', form.name.trim());
        fd.append('email', form.email.trim());
        fd.append('phone_number', form.phone_number.trim());
        fd.append('timezone', form.timezone);
        fd.append('preferred_product_type', form.preferred_product_type);
        if (avatarFile) fd.append('avatar', avatarFile);
        else if (avatarRemoved) fd.append('avatar', '');

        try {
            const res = await window.axios.post('/api/profile', fd, { headers: { 'Content-Type': 'multipart/form-data' }, silent: true });
            const updatedUser = res.data.data;
            window.Auth.setUser(updatedUser);
            setAvatarFile(null);
            setAvatarRemoved(false);
            setAvatarPreview(updatedUser.avatar_url || null);
            setSuccessMessage(profile.updated_success);
            onUpdated?.(updatedUser);
            if (typeof window.updateNavbar === 'function') window.updateNavbar();
            setTimeout(() => setSuccessMessage(null), 4000);
        } catch (err) {
            if (err.response?.status === 422) {
                const fieldErrors = {};
                Object.entries(err.response.data?.errors ?? {}).forEach(([field, messages]) => { fieldErrors[field] = messages[0]; });
                setErrors(fieldErrors);
            } else {
                setGeneralError(err.response?.data?.message || profile.something_wrong);
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div>
            <div className="surface-card mb-6 p-6">
                <label className="mb-4 block text-sm font-bold text-foreground">{profile.profile_photo}</label>
                <div className="flex items-center gap-6">
                    <div className="relative">
                        <div className="flex size-24 items-center justify-center overflow-hidden rounded-full bg-primary shadow-[0_0_0_4px_var(--background)]">
                            {avatarPreview ? <img src={avatarPreview} className="size-full object-cover" alt="" /> : <span className="text-3xl font-bold text-primary-foreground">{(form.name || '?').charAt(0).toUpperCase()}</span>}
                        </div>
                        <label htmlFor="avatar-input" className="absolute bottom-0 flex size-9 cursor-pointer items-center justify-center rounded-full bg-primary text-primary-foreground transition-colors hover:bg-primary/90" style={{ insetInlineEnd: 0 }}>
                            <Camera className="size-4" />
                        </label>
                        <input type="file" id="avatar-input" className="hidden" accept="image/*" onChange={onAvatarChange} />
                    </div>
                    <div>
                        <p className="text-sm font-medium text-foreground">{profile.upload_avatar}</p>
                        <p className="mt-0.5 text-xs text-muted-foreground">{profile.avatar_hint}</p>
                        {avatarPreview && (
                            <button type="button" onClick={removeAvatar} className="mt-2 text-xs font-bold text-[var(--color-danger-strong)] hover:underline">{profile.remove_photo}</button>
                        )}
                    </div>
                </div>
            </div>

            <div className="surface-card mb-6 p-6">
                <h3 className="mb-5 text-base font-bold text-foreground">{profile.personal_info}</h3>
                <form onSubmit={handleSubmit} className="space-y-5">
                    <div>
                        <label htmlFor="p-name" className="mb-1.5 block text-sm font-medium text-foreground">{profile.full_name}</label>
                        <input type="text" id="p-name" className="form-input" placeholder={profile.placeholder_full_name} value={form.name} onChange={(e) => set('name')(e.target.value)} />
                        {errors.name && <p className="mt-1 text-xs text-[var(--color-danger-strong)]">{errors.name}</p>}
                    </div>
                    <div>
                        <label htmlFor="p-email" className="mb-1.5 block text-sm font-medium text-foreground">{profile.email_address}</label>
                        <input type="email" id="p-email" className="form-input" placeholder="you@example.com" value={form.email} onChange={(e) => set('email')(e.target.value)} />
                        {errors.email && <p className="mt-1 text-xs text-[var(--color-danger-strong)]">{errors.email}</p>}
                    </div>
                    <div>
                        <label htmlFor="p-phone" className="mb-1.5 block text-sm font-medium text-foreground">{profile.phone_number}</label>
                        <input type="text" id="p-phone" className="form-input" placeholder="09XXXXXXXX" value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} />
                        {errors.phone_number && <p className="mt-1 text-xs text-[var(--color-danger-strong)]">{errors.phone_number}</p>}
                    </div>
                    <div>
                        <label htmlFor="p-timezone" className="mb-1.5 block text-sm font-medium text-foreground">{profile.timezone}</label>
                        <select id="p-timezone" className="form-select" value={form.timezone} onChange={(e) => set('timezone')(e.target.value)}>
                            {timezones.map((tz) => <option key={tz.value} value={tz.value}>{tz.label}</option>)}
                        </select>
                        {errors.timezone && <p className="mt-1 text-xs text-[var(--color-danger-strong)]">{errors.timezone}</p>}
                    </div>
                    <div>
                        <label htmlFor="p-type" className="mb-1.5 block text-sm font-medium text-foreground">{profile.preferred_product_type}</label>
                        <select id="p-type" className="form-select" value={form.preferred_product_type} onChange={(e) => set('preferred_product_type')(e.target.value)}>
                            <option value="">{profile.select_type}</option>
                            <option value="agriculture">{profile.type_agriculture}</option>
                            <option value="veterinary">{profile.type_veterinary}</option>
                        </select>
                        {errors.preferred_product_type && <p className="mt-1 text-xs text-[var(--color-danger-strong)]">{errors.preferred_product_type}</p>}
                    </div>

                    <div className="info-grid rounded-lg border border-border bg-muted/40 p-4">
                        <div>
                            <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">{profile.account_type}</p>
                            <p className="mt-0.5 text-sm font-bold text-foreground">{ROLE_LABEL[user.type] ?? 'Customer'}</p>
                        </div>
                        <div>
                            <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">{profile.member_since}</p>
                            <p className="mt-0.5 text-sm font-bold text-foreground">{user.created_at ? new Date(user.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }) : '—'}</p>
                        </div>
                    </div>

                    {successMessage && (
                        <div className="flex items-center gap-3 rounded-lg bg-[var(--color-success-soft)] px-4 py-3 text-sm font-medium text-[var(--color-success-strong)]">
                            <Check className="size-5 shrink-0" />
                            <span>{successMessage}</span>
                        </div>
                    )}
                    {generalError && (
                        <div className="flex items-center gap-3 rounded-lg bg-[var(--color-danger-soft)] px-4 py-3 text-sm font-medium text-[var(--color-danger-strong)]">
                            <span>{generalError}</span>
                        </div>
                    )}

                    <button type="submit" disabled={isSubmitting} className="btn-primary flex w-full items-center justify-center gap-2">
                        {isSubmitting ? <Loader2 className="size-4 animate-spin" /> : <Check className="size-4" />}
                        {profile.save_changes}
                    </button>
                </form>
            </div>
        </div>
    );
}

export default function ProfileIndex() {
    const { profile, nav, authPage } = useI18n();
    const user = useAuthUser();

    return (
        <PublicLayout title={profile.my_profile} noindex>
            <div className="bg-transparent">
                <div className="catalog-page-band">
                    <div className="page-shell py-3">
                        <nav className="page-breadcrumb">
                            <Link href={route('home')} className="hover:text-primary">{profile.home}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <span className="font-medium text-foreground">{profile.my_profile}</span>
                        </nav>
                    </div>
                </div>

                <div className="page-shell">
                    {!user ? (
                        <div className="empty-state py-10 sm:py-12">
                            <p className="mt-4 text-base font-bold text-foreground">{profile.sign_in_to_view}</p>
                            <Link href={route('login')} className="btn-primary mt-4 inline-flex">{authPage.sign_in ?? nav.sign_in}</Link>
                        </div>
                    ) : (
                        <div>
                            <div className="mb-8">
                                <h1 className="section-title text-2xl">{profile.my_profile}</h1>
                                <p className="mt-1 text-sm text-muted-foreground">{profile.update_info}</p>
                            </div>

                            <div className="split-dashboard-grid">
                                <ProfileForm user={user} />
                                <div className="split-dashboard-main space-y-6">
                                    <FavouritesPanel />
                                    <OrderHistoryPanel />
                                    <ContactHistoryPanel />
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
