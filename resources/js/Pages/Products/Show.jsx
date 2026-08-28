import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight, Heart, ShoppingBag, Ban, X, Star, AlertTriangle } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { useCart } from '@/hooks/use-cart';
import { useFavourites } from '@/hooks/use-favourites';
import { useAuthUser, useI18n, useLocale } from '@/hooks/use-i18n';
import { canPurchase } from '@/lib/purchase';

function StarRow({ rating, size = 'h-5 w-5' }) {
    const resolved = Math.min(5, Math.max(0, Math.round(rating)));
    return (
        <div className="flex items-center gap-0.5 text-amber-400">
            {Array.from({ length: 5 }).map((_, i) => (
                <Star key={i} className={size} fill={i < resolved ? 'currentColor' : 'none'} stroke="currentColor" />
            ))}
        </div>
    );
}

function formatDateOnly(value) {
    if (!value) return '—';
    const date = new Date(typeof value === 'string' ? value.replace(' ', 'T') : value);
    if (Number.isNaN(date.getTime())) return String(value).slice(0, 10);
    return date.toLocaleDateString();
}

/**
 * Agricultural/veterinary detail fields are a loose mix of strings, numbers,
 * flat arrays, and arrays of small objects (e.g. application_rates: [{value, unit}]).
 * This renders any of those shapes as one readable line instead of "[object Object]".
 */
function formatSpecValue(value) {
    if (value === null || value === undefined || value === '') return null;
    if (Array.isArray(value)) {
        if (value.length === 0) return null;
        return value.map((entry) => (
            typeof entry === 'object' && entry !== null
                ? Object.values(entry).filter(Boolean).join(' ')
                : String(entry)
        )).filter(Boolean).join(', ');
    }
    if (typeof value === 'object') {
        const parts = Object.entries(value).filter(([, v]) => v !== null && v !== undefined && v !== '');
        return parts.length ? parts.map(([k, v]) => `${k}: ${v}`).join(', ') : null;
    }
    return String(value);
}

const AGRICULTURAL_SPEC_FIELDS = ['agricultural_product_type', 'target_crops', 'application_methods', 'application_rates', 'max_applications', 'application_interval_days', 'pre_harvest_intervals', 'storage_conditions', 'warnings'];
const VETERINARY_SPEC_FIELDS = ['target_species', 'indications', 'dosage_instructions', 'treatment_duration', 'contraindications', 'withdrawal_meat_days', 'withdrawal_milk_days', 'withdrawal_eggs_days', 'storage_conditions', 'warnings'];

function buildSpecRows(product, productSpecs) {
    const detail = product.agricultural_detail ? { source: product.agricultural_detail, fields: AGRICULTURAL_SPEC_FIELDS }
        : product.veterinary_detail ? { source: product.veterinary_detail, fields: VETERINARY_SPEC_FIELDS }
        : null;
    if (!detail) return [];

    return detail.fields
        .map((key) => ({ key, label: productSpecs[key] || key, value: formatSpecValue(detail.source[key]) }))
        .filter((row) => row.value !== null);
}

function ReviewForm({ productId, onSubmitted }) {
    const { products } = useI18n();
    const [rating, setRating] = useState(0);
    const [body, setBody] = useState('');
    const [error, setError] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        setError(null);
        if (rating < 1 || rating > 5) {
            alert(products.rating_select_hint);
            return;
        }
        setIsSubmitting(true);
        window.axios.post(`/api/products/${productId}/reviews`, { rating, body: body.trim() || null }, { silent: true }).then(() => {
            setRating(0);
            setBody('');
            setIsSubmitting(false);
            onSubmitted();
        }).catch((err) => {
            setIsSubmitting(false);
            let message = err.response?.data?.message || products.review_submit_failed;
            if (err.response?.status === 401) message = products.review_login_required;
            if (err.response?.status === 422) message = products.review_validation_error;
            setError(message);
        });
    };

    return (
        <div className="mb-8 border-y border-border bg-muted/40 p-5">
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label className="mb-2 block text-sm font-bold text-foreground">{products.review_rating_label}</label>
                    <div className="flex gap-1 text-2xl">
                        {[1, 2, 3, 4, 5].map((value) => (
                            <button
                                key={value}
                                type="button"
                                onClick={() => setRating(value)}
                                aria-label={(products.review_star_label ?? '').replace(':count', String(value))}
                                className="rounded p-0.5 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2"
                            >
                                <Star className={`h-8 w-8 ${value <= rating ? 'text-amber-400' : 'text-muted-foreground/30'}`} fill={value <= rating ? 'currentColor' : 'none'} />
                            </button>
                        ))}
                    </div>
                </div>
                <div>
                    <label htmlFor="review-body" className="mb-2 block text-sm font-bold text-foreground">{products.review_comment_label}</label>
                    <textarea id="review-body" rows={4} className="form-textarea" placeholder={products.review_placeholder} value={body} onChange={(e) => setBody(e.target.value)} />
                </div>
                {error && <p className="form-error">{error}</p>}
                <button type="submit" disabled={isSubmitting} className="btn-primary">{products.review_submit}</button>
            </form>
        </div>
    );
}

/**
 * Rendered as a child of PublicLayout (not a sibling that wraps it), so
 * useCart/useFavourites resolve against the CartProvider/FavouritesProvider
 * PublicLayout mounts — calling those hooks from the page component itself
 * would run before that provider exists in the tree.
 */
function ProductDetail({ product, productId }) {
    const { nav, products, common, productSpecs, purchase } = useI18n();
    const locale = useLocale();
    const user = useAuthUser();
    const { addToCart } = useCart();
    const buyerAllowed = canPurchase(user);
    const { ids: favIds, toggle: toggleFav } = useFavourites();

    const [activePhoto, setActivePhoto] = useState(() => {
        const photos = product.photos ?? [];
        const primary = photos.find((ph) => ph.is_primary) ?? photos[0];
        return primary ? (primary.url || `/storage/${primary.path}`) : (product.first_photo_url || product.fallback_photo_url || null);
    });
    const [lightbox, setLightbox] = useState(null);

    const [reviewsStatus, setReviewsStatus] = useState('loading');
    const [reviews, setReviews] = useState([]);
    const [reviewsMeta, setReviewsMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [reviewsError, setReviewsError] = useState(null);

    const loadReviews = (page) => {
        setReviewsStatus('loading');
        window.axios.get(`/api/products/${productId}/reviews`, { params: { page, per_page: 5 }, silent: true }).then((res) => {
            setReviews(res.data?.data ?? []);
            setReviewsMeta(res.data?.meta ?? { current_page: 1, last_page: 1, total: 0 });
            setReviewsStatus('ready');
        }).catch((err) => {
            setReviewsError(err.response?.status === 404 ? products.reviews_not_available : products.reviews_load_failed);
            setReviewsStatus('error');
        });
    };

    useEffect(() => { loadReviews(1); }, [productId]);

    const deleteReview = (reviewId) => {
        if (!window.confirm(products.delete_review_confirm)) return;
        window.axios.delete(`/api/products/${productId}/reviews/${reviewId}`, { silent: true }).then(() => {
            loadReviews(reviewsMeta.current_page);
        }).catch((err) => {
            alert(err.response?.data?.message || products.review_delete_failed);
        });
    };

    const photos = product.photos ?? [];
    const inStock = Number(product.quantity || 0) > 0;
    const isFav = favIds.has(product.id);
    const hasDiscount = !!product.has_active_discount;
    const effectivePrice = parseFloat(hasDiscount ? product.discounted_price : product.price || 0);
    const reviewCount = parseInt(product.review_count, 10) || 0;
    const averageRating = parseFloat(product.average_rating) || 0;
    const minOrderQuantity = Math.max(1, Number(product.minimum_order_quantity) || 1);
    const [orderQuantity, setOrderQuantity] = useState(minOrderQuantity);

    const discountStatusLabel = { active: products.discount_status_active, pending: products.discount_status_pending, expired: products.discount_status_expired }[product.discount_status] ?? common.not_specified;
    const specRows = buildSpecRows(product, productSpecs);

    return (
        <div className="bg-transparent">
            <div className="catalog-page-band">
                <div className="page-shell py-3">
                    <nav className="page-breadcrumb">
                        <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                        <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                        <Link href={route('products.index')} className="hover:text-primary">{nav.products}</Link>
                        <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                        <span className="page-breadcrumb-current">{product.name}</span>
                    </nav>
                </div>
            </div>

            <div className="page-shell">
                <div className="product-decision-layout">
                    <div className="space-y-4">
                        <div className="storefront-gallery-main">
                            {activePhoto ? (
                                <button type="button" className="block h-full w-full cursor-zoom-in focus:outline-none" aria-label={products.view_larger} onClick={() => setLightbox(activePhoto)}>
                                    <img src={activePhoto} alt={product.name} loading="eager" />
                                </button>
                            ) : (
                                <div className="absolute inset-0 flex items-center justify-center text-center text-sm font-medium text-muted-foreground">
                                    {products.no_primary_photo}
                                </div>
                            )}
                        </div>

                        <div className="border-t border-border pt-5">
                            <div className="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-bold text-foreground">{products.gallery_title}</h2>
                                </div>
                                <span className="text-xs font-semibold text-muted-foreground">{photos.length} {photos.length === 1 ? products.photo_single : products.photos}</span>
                            </div>
                            {photos.length > 0 ? (
                                <div className="storefront-chip-scroll flex gap-3 overflow-x-auto pb-1">
                                    {photos.map((photo) => {
                                        const url = photo.url || `/storage/${photo.path}`;
                                        return (
                                            <button key={photo.id} type="button" onClick={() => setActivePhoto(url)} className={`storefront-thumb-button ${url === activePhoto ? 'is-active' : ''}`} aria-label={product.name}>
                                                <img src={url} alt={`${product.name} thumbnail`} />
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="py-4 text-xs text-muted-foreground">{products.no_additional_photos}</p>
                            )}
                        </div>
                    </div>

                    <div className="product-decision-summary">
                        <div className="border-b border-border pb-7">
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                <div className="min-w-0 flex-1">
                                    <h1 className="text-3xl font-bold leading-tight tracking-tight text-foreground sm:text-4xl">{product.name}</h1>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => toggleFav(product.id)}
                                    aria-pressed={isFav}
                                    aria-label={nav.cart}
                                    className={`flex h-11 w-11 shrink-0 items-center justify-center border transition-colors ${isFav ? 'border-primary text-primary' : 'border-border text-muted-foreground hover:border-primary hover:text-primary'}`}
                                >
                                    <Heart className="h-6 w-6" fill={isFav ? 'currentColor' : 'none'} />
                                </button>
                            </div>

                            <div className="mt-5 flex flex-wrap items-center gap-2 border-b border-border pb-5">
                                <StarRow rating={averageRating} />
                                <span className="text-sm text-muted-foreground">
                                    {reviewCount === 0 ? products.no_ratings_yet : `${reviewCount} ${reviewCount === 1 ? products.review_single : products.review_plural}`}
                                </span>
                            </div>

                            <div className="mt-5 flex flex-wrap items-end gap-3 border-b border-border pb-5">
                                <span className={`text-4xl font-bold tabular-nums ${hasDiscount ? 'text-[var(--color-danger-600)]' : 'text-foreground'}`}>{effectivePrice.toLocaleString()}</span>
                                <span className="pb-1 text-sm font-semibold text-muted-foreground">SYP</span>
                                {hasDiscount && <span className="pb-1 text-sm tabular-nums text-muted-foreground line-through">{parseFloat(product.price || 0).toLocaleString()} SYP</span>}
                            </div>

                            <div className="storefront-spec-grid mt-5">
                                <div className="storefront-spec-card">
                                    <p className="text-xs font-medium text-muted-foreground">{products.fields.category}</p>
                                    <p className="mt-1 text-sm font-semibold text-foreground">{product.category?.name || common.not_specified}</p>
                                </div>
                                <div className="storefront-spec-card">
                                    <p className="text-xs font-medium text-muted-foreground">{products.fields.subcategory}</p>
                                    <p className="mt-1 text-sm font-semibold text-foreground">
                                        {(locale === 'ar'
                                            ? (product.subcategory?.name_ar || product.subcategory?.name_en)
                                            : (product.subcategory?.name_en || product.subcategory?.name_ar)
                                        ) || products.no_subcategory}
                                    </p>
                                </div>
                                <div className="storefront-spec-card">
                                    <p className="text-xs font-medium text-muted-foreground">{products.fields.quantity}</p>
                                    <p className="mt-1 text-sm font-semibold tabular-nums text-foreground">{product.quantity || 0} {products.units}</p>
                                </div>
                                {minOrderQuantity > 1 && (
                                    <div className="storefront-spec-card">
                                        <p className="text-xs font-medium text-muted-foreground">{products.fields.minimum_order_quantity}</p>
                                        <p className="mt-1 text-sm font-semibold tabular-nums text-foreground">{minOrderQuantity} {products.units}</p>
                                    </div>
                                )}
                                <div className="storefront-spec-card">
                                    <p className="text-xs font-medium text-muted-foreground">{products.fields.vendor}</p>
                                    <p className="mt-1 truncate text-sm font-semibold text-foreground">
                                        {product.vendor?.id ? (
                                            <Link href={route('vendors.show', product.vendor.id)} className="hover:text-primary hover:underline">
                                                {product.vendor.store_name || product.vendor.name}
                                            </Link>
                                        ) : (product.vendor?.store_name || product.vendor?.name || products.no_vendor)}
                                    </p>
                                </div>
                            </div>

                            <div className="mt-5 border-y border-border py-4">
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">{products.discount_card_title}</p>
                                        <p className="mt-2 text-sm font-semibold text-foreground">{discountStatusLabel}</p>
                                    </div>
                                    {inStock ? (
                                        <span className="badge badge-success gap-1.5"><span className="h-1.5 w-1.5 rounded-full bg-[var(--color-success-500)]" />{products.in_stock}</span>
                                    ) : (
                                        <span className="badge badge-danger gap-1.5"><span className="h-1.5 w-1.5 rounded-full bg-[var(--color-danger-500)]" />{products.sold_out}</span>
                                    )}
                                </div>
                                <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                    <div className="border-s-2 border-border ps-3">
                                        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">{products.fields.discount_value}</p>
                                        <p className="mt-1 text-sm font-semibold tabular-nums text-[var(--color-danger-600)]">{product.discount_percentage ? `${parseFloat(product.discount_percentage).toFixed(2)}%` : products.discount_value_empty}</p>
                                    </div>
                                    <div className="border-s-2 border-border ps-3">
                                        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">{products.fields.discount_starts}</p>
                                        <p className="mt-1 text-sm font-semibold tabular-nums text-foreground">{formatDateOnly(product.discount_starts_at)}</p>
                                    </div>
                                    <div className="border-s-2 border-border ps-3">
                                        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">{products.fields.discount_ends}</p>
                                        <p className="mt-1 text-sm font-semibold tabular-nums text-foreground">{formatDateOnly(product.discount_ends_at)}</p>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-5 flex flex-col gap-3 sm:flex-row">
                                {inStock && minOrderQuantity > 1 && (
                                    <div className="flex items-center gap-2 border border-border px-2 py-1.5" role="group" aria-label={products.fields.minimum_order_quantity}>
                                        <button
                                            type="button"
                                            className="flex h-8 w-8 items-center justify-center text-lg font-semibold text-foreground disabled:opacity-40"
                                            onClick={() => setOrderQuantity((q) => Math.max(minOrderQuantity, q - 1))}
                                            disabled={orderQuantity <= minOrderQuantity}
                                            aria-label="-"
                                        >
                                            −
                                        </button>
                                        <input
                                            type="number"
                                            min={minOrderQuantity}
                                            max={product.quantity || minOrderQuantity}
                                            value={orderQuantity}
                                            onChange={(e) => setOrderQuantity(Math.max(minOrderQuantity, Number(e.target.value) || minOrderQuantity))}
                                            className="w-14 border-0 bg-transparent text-center text-sm font-semibold tabular-nums text-foreground focus:outline-none"
                                            aria-label={products.fields.quantity}
                                        />
                                        <button
                                            type="button"
                                            className="flex h-8 w-8 items-center justify-center text-lg font-semibold text-foreground"
                                            onClick={() => setOrderQuantity((q) => q + 1)}
                                            aria-label="+"
                                        >
                                            +
                                        </button>
                                    </div>
                                )}
                                <button
                                    type="button"
                                    onClick={() => {
                                        if (!buyerAllowed) {
                                            window.AppToast?.show(purchase.customer_only || '', 'warning');
                                            return;
                                        }
                                        addToCart(product.id, minOrderQuantity > 1 ? orderQuantity : 1);
                                    }}
                                    disabled={!inStock || !buyerAllowed}
                                    className="btn-primary flex-1 py-3.5"
                                    title={!buyerAllowed ? purchase.customer_only : undefined}
                                >
                                    <span className="flex items-center justify-center gap-2">
                                        {inStock ? <ShoppingBag className="h-5 w-5" /> : <Ban className="h-5 w-5" />}
                                        {inStock ? products.add_to_cart_btn : products.out_of_stock_button}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div className="border-b border-border py-7">
                            <h2 className="text-lg font-bold text-foreground">{products.description_title}</h2>
                            <p className="mt-4 whitespace-pre-wrap text-sm leading-8 text-muted-foreground">{product.description || products.no_description}</p>
                        </div>
                    </div>
                </div>

                {specRows.length > 0 && (
                    <div className="mt-10 border-t border-border py-9">
                        <h2 className="text-lg font-bold text-foreground">{productSpecs.heading}</h2>
                        <dl className="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                            {specRows.map((row) => (
                                <div key={row.key} className="border-s-2 border-border ps-3">
                                    <dt className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">{row.label}</dt>
                                    <dd className="mt-1 text-sm font-medium text-foreground">{row.value}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                )}

                <div className="mt-10">
                    <div className="border-t border-border py-9">
                        <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 className="text-xl font-bold text-foreground">
                                    {products.reviews} <span className="text-sm font-normal text-muted-foreground">{reviewsMeta.total ? `(${reviewsMeta.total} ${reviewsMeta.total === 1 ? products.review_single : products.review_plural})` : ''}</span>
                                </h2>
                            </div>
                        </div>

                        {user && <ReviewForm productId={productId} onSubmitted={() => loadReviews(1)} />}

                        {reviewsStatus === 'loading' && <p className="py-4 text-center text-sm text-muted-foreground">{products.loading_details}</p>}
                        {reviewsStatus === 'error' && <p className="py-8 text-center text-sm text-muted-foreground">{reviewsError}</p>}
                        {reviewsStatus === 'ready' && reviews.length === 0 && <p className="py-8 text-center text-sm text-muted-foreground">{products.no_reviews}</p>}

                        {reviewsStatus === 'ready' && reviews.length > 0 && (
                            <div className="divide-y divide-border">
                                {reviews.map((review) => {
                                    const canDelete = user && review.user && review.user.id === user.id;
                                    return (
                                        <article key={review.id} className="py-5">
                                            <div className="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-bold text-foreground">{review.user?.name || 'User'}</p>
                                                    {review.created_at && <p className="mt-1 text-[11px] text-muted-foreground">{new Date(review.created_at).toLocaleDateString(undefined, { dateStyle: 'medium' })}</p>}
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <StarRow rating={review.rating} size="h-4 w-4" />
                                                    {canDelete && (
                                                        <button type="button" onClick={() => deleteReview(review.id)} className="text-xs font-bold text-[var(--color-danger-strong)] hover:underline">{products.deleteReview}</button>
                                                    )}
                                                </div>
                                            </div>
                                            {review.body && <p className="mt-2 text-sm leading-7 text-muted-foreground">{review.body}</p>}
                                        </article>
                                    );
                                })}
                            </div>
                        )}

                        {reviewsMeta.last_page > 1 && (
                            <div className="mt-6 flex flex-wrap items-center justify-center gap-2">
                                <button type="button" onClick={() => loadReviews(reviewsMeta.current_page - 1)} disabled={reviewsMeta.current_page <= 1} className="btn-secondary btn-xs">{nav.prev}</button>
                                <span className="text-sm text-muted-foreground">{nav.page} {reviewsMeta.current_page} {nav.of} {reviewsMeta.last_page}</span>
                                <button type="button" onClick={() => loadReviews(reviewsMeta.current_page + 1)} disabled={reviewsMeta.current_page >= reviewsMeta.last_page} className="btn-secondary btn-xs">{nav.next}</button>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {lightbox && (
                <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" onClick={(e) => { if (e.target === e.currentTarget) setLightbox(null); }}>
                    <div className="relative max-h-[90vh] max-w-[90vw]">
                        <img src={lightbox} alt={product.name} className="max-h-[90vh] max-w-[90vw] rounded-lg object-contain" />
                        <button
                            type="button"
                            onClick={() => setLightbox(null)}
                            aria-label="Close"
                            className="absolute -end-2 -top-2 flex h-10 w-10 items-center justify-center rounded-full bg-white text-gray-900 shadow-lg"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}

/**
 * `product` is fetched server-side by the `products.show` web route (via the
 * same controller method /api/products/{id} calls), so this page has real
 * content on the very first SSR-rendered response instead of an empty shell
 * that only fills in after a client-side fetch — the difference between a
 * product page crawlers/AI answer engines can actually read and one they
 * can't.
 */
export default function ProductsShow({ productId, product }) {
    const { products } = useI18n();
    const locale = useLocale();

    if (!product) {
        return (
            <PublicLayout title={products.error_title} noindex>
                <div className="page-shell">
                    <div className="empty-state py-16">
                        <AlertTriangle className="mx-auto h-16 w-16 text-muted-foreground/30" />
                        <p className="mt-4 text-base font-bold text-foreground">{products.error_title}</p>
                        <p className="mt-1 text-sm text-muted-foreground">{products.error_copy}</p>
                        <Link href={route('products.index')} className="btn-primary mt-6 inline-flex">{products.back_to_products}</Link>
                    </div>
                </div>
            </PublicLayout>
        );
    }

    const inStock = Number(product.quantity || 0) > 0;
    const hasDiscount = !!product.has_active_discount;
    const effectivePrice = hasDiscount ? product.discounted_price : product.price;
    const primaryPhoto = (product.photos ?? []).find((ph) => ph.is_primary) ?? (product.photos ?? [])[0];
    const imageUrl = primaryPhoto ? (primaryPhoto.url || `/storage/${primaryPhoto.path}`) : (product.first_photo_url || product.fallback_photo_url || undefined);
    const description = (product.description || '').replace(/\s+/g, ' ').trim().slice(0, 160) || undefined;

    // shared_detail is always public (unlike `vendor`, which the API only exposes to
    // admin/vendor viewers), so brand/sku/gtin here come from the product's own
    // catalogue data rather than the seller — the correct source for `Product.brand`.
    const sharedDetail = product.shared_detail ?? null;
    const brandName = sharedDetail ? (locale === 'ar' ? (sharedDetail.brand_name_ar || sharedDetail.brand_name_en) : (sharedDetail.brand_name_en || sharedDetail.brand_name_ar)) : null;
    const priceValidUntil = hasDiscount && product.discount_ends_at
        ? new Date(String(product.discount_ends_at).replace(' ', 'T')).toISOString().slice(0, 10)
        : undefined;

    const productJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.name,
        description: product.description || undefined,
        image: imageUrl,
        category: product.category?.name || undefined,
        brand: brandName ? { '@type': 'Brand', name: brandName } : undefined,
        sku: sharedDetail?.sku || undefined,
        gtin: sharedDetail?.barcodes?.[0] || undefined,
        offers: {
            '@type': 'Offer',
            priceCurrency: 'SYP',
            price: effectivePrice,
            availability: inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            itemCondition: 'https://schema.org/NewCondition',
            ...(priceValidUntil ? { priceValidUntil } : {}),
            ...(product.vendor?.store_name ? { seller: { '@type': 'Organization', name: product.vendor.store_name } } : {}),
        },
        ...(Number(product.review_count) > 0 ? {
            aggregateRating: {
                '@type': 'AggregateRating',
                ratingValue: product.average_rating,
                reviewCount: product.review_count,
            },
        } : {}),
    };

    const breadcrumbJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Home', item: route('home') },
            { '@type': 'ListItem', position: 2, name: 'Products', item: route('products.index') },
            { '@type': 'ListItem', position: 3, name: product.name, item: route('products.show', product.id) },
        ],
    };

    return (
        <PublicLayout title={product.name} description={description} image={imageUrl} type="product" jsonLd={[productJsonLd, breadcrumbJsonLd]}>
            <ProductDetail product={product} productId={productId} />
        </PublicLayout>
    );
}
