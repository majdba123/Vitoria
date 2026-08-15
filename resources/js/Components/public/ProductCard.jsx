import { Link } from '@inertiajs/react';
import { Star, Heart, ShoppingBag } from 'lucide-react';
import { useCart } from '@/hooks/use-cart';
import { useFavourites } from '@/hooks/use-favourites';
import { useI18n } from '@/hooks/use-i18n';

function StarRating({ rating }) {
    const resolved = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
    return (
        <div className="product-card-rating-stars flex items-center gap-0.5">
            {Array.from({ length: 5 }).map((_, i) => (
                <Star key={i} className={`h-3.5 w-3.5 ${i < resolved ? 'fill-amber-400 text-amber-400' : 'fill-none text-muted-foreground/30'}`} />
            ))}
        </div>
    );
}

export function ProductCard({ product, href, context = '', rank = null }) {
    const { products, nav } = useI18n();
    const { addToCart } = useCart();
    const { ids: favIds, toggle: toggleFav } = useFavourites();

    const photo = product.first_photo_url || product.fallback_photo_url || '/images/product-placeholder.svg';
    const inStock = Number(product.quantity || 0) > 0;
    const isFav = favIds.has(product.id);
    const reviewCount = parseInt(product.review_count, 10) || 0;
    const unitPrice = product.has_active_discount ? product.discounted_price : product.price;
    const displayPrice = parseFloat(unitPrice || 0).toLocaleString();
    const linkHref = href ?? `/products/${product.id}`;
    const reviewText = reviewCount ? (nav.reviews_count ?? '').replace(':count', String(reviewCount)) : '';

    return (
        <article className="product-card">
            <div className="product-card-media">
                <Link href={linkHref} className="absolute inset-0 block">
                    <img
                        src={photo}
                        alt={product.name}
                        width="640"
                        height="640"
                        loading="lazy"
                        decoding="async"
                        sizes="(max-width: 359px) calc(100vw - 2rem), (max-width: 767px) calc(50vw - 1.5rem), (max-width: 1199px) 33vw, 20vw"
                        onError={(e) => { e.currentTarget.onerror = null; e.currentTarget.src = '/images/product-placeholder.svg'; }}
                    />
                </Link>
                {rank !== null ? (
                    <span className="product-card-rank">#{rank}</span>
                ) : product.has_active_discount ? (
                    <span className="product-card-badge">-{parseFloat(product.discount_percentage || 0).toFixed(0)}%</span>
                ) : null}
                <button
                    type="button"
                    onClick={(e) => { e.preventDefault(); e.stopPropagation(); toggleFav(product.id); }}
                    aria-label={product.name}
                    aria-pressed={isFav}
                    className={`product-card-fav ${isFav ? 'is-active' : ''}`}
                >
                    <Heart className="h-4 w-4" fill={isFav ? 'currentColor' : 'none'} />
                </button>
                {!inStock && <div className="product-card-media-overlay">{nav.sold_out}</div>}
            </div>
            <div className="product-card-body">
                {context && <p className="product-card-context">{context}</p>}
                <Link href={linkHref}><h3 className="product-card-title">{product.name}</h3></Link>
                {reviewCount > 0 && (
                    <div className="product-card-rating">
                        <StarRating rating={product.average_rating} />
                        <span>{reviewText}</span>
                    </div>
                )}
                <div className="product-card-footer">
                    <div className="product-card-price-group" dir="auto">
                        <span className="product-card-price">{displayPrice} <span className="product-card-price-currency">SYP</span></span>
                        {product.has_active_discount && (
                            <span className="product-card-price-was">{parseFloat(product.price || 0).toLocaleString()} SYP</span>
                        )}
                        <span className={`product-card-stock ${inStock ? '' : 'is-out'}`}>{inStock ? products.in_stock ?? '' : nav.sold_out}</span>
                    </div>
                    <button
                        type="button"
                        onClick={(e) => { e.preventDefault(); e.stopPropagation(); addToCart(product.id); }}
                        disabled={!inStock}
                        className="product-card-cta"
                        aria-label={`${products.add_to_cart_btn ?? ''}: ${product.name}`}
                    >
                        <ShoppingBag className="h-4 w-4" />
                    </button>
                </div>
            </div>
        </article>
    );
}
