import { useRef, useState } from 'react';
import { Minus, Plus, X, ShoppingBag } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { useCart } from '@/hooks/use-cart';
import { useI18n } from '@/hooks/use-i18n';
import { useFocusTrap } from '@/hooks/use-focus-trap';

function formatMoney(amount, currency, locale) {
    const value = Number(amount) || 0;
    return `${value.toLocaleString(locale, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ${currency}`;
}

export function CartModal() {
    const { cart, common } = useI18n();
    const {
        isOpen, closeCart, items, itemsCount, total, currency, message,
        updateQty, removeFromCart, applyCoupon, isBusy,
    } = useCart();
    const [couponCode, setCouponCode] = useState('');
    const panelRef = useRef(null);

    useFocusTrap(panelRef, isOpen, closeCart);

    if (!isOpen) return null;

    const locale = document.documentElement.lang || 'en';
    const itemsLabel = itemsCount === 1 ? (cart.items_count ?? '').replace(':count', itemsCount) : (cart.items_count_plural ?? '').replace(':count', itemsCount);

    const checkout = () => {
        if (!items.length) return;
        if (!window.Auth?.isAuthenticated()) {
            window.location.href = '/login?redirect=' + encodeURIComponent('/checkout');
            return;
        }
        window.location.href = '/checkout';
    };

    return (
        <div className="fixed inset-0 z-[100] flex items-stretch justify-end bg-black/45 backdrop-blur-sm" role="dialog" aria-modal="true">
            <div className="absolute inset-0" onClick={closeCart} />
            <div ref={panelRef} className="relative flex h-full w-full max-w-md flex-col bg-card shadow-2xl">
                <div className="flex items-center justify-between border-b border-border px-5 py-4">
                    <div>
                        <h2 className="text-lg font-bold text-foreground">{cart.shopping_cart}</h2>
                        <p className="mt-0.5 text-xs text-muted-foreground">{itemsLabel}</p>
                    </div>
                    <button type="button" onClick={closeCart} className="rounded-md p-2 text-muted-foreground hover:bg-accent" aria-label={common.close ?? 'Close'}>
                        <X className="size-5" />
                    </button>
                </div>

                {message && (
                    <div className={`mx-5 mt-3 rounded-md border px-3 py-2 text-xs font-semibold ${message.tone === 'success' ? 'border-[var(--color-success-200)] bg-[var(--color-success-soft)] text-[var(--color-success-strong)]' : 'border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]'}`}>
                        {message.text}
                    </div>
                )}

                <div className={`flex-1 space-y-3 overflow-y-auto p-5 ${isBusy ? 'pointer-events-none opacity-60' : ''}`}>
                    {items.length === 0 ? (
                        <div className="flex h-full flex-col items-center justify-center text-center">
                            <ShoppingBag className="size-10 text-muted-foreground/40" />
                            <p className="mt-3 text-sm font-semibold text-foreground">{cart.empty}</p>
                            <p className="mt-1 text-xs text-muted-foreground">{cart.empty_hint}</p>
                        </div>
                    ) : (
                        items.map((item) => {
                            const atCeiling = item.quantity >= item.available_quantity;
                            return (
                                <div key={item.product_id} className="flex items-center gap-3 rounded-md border border-border bg-background p-3">
                                    <div className="size-14 shrink-0 overflow-hidden rounded-md bg-muted">
                                        {item.photo_url ? (
                                            <img
                                                src={item.photo_url}
                                                alt=""
                                                className="size-full object-contain p-1"
                                                onError={(e) => { e.currentTarget.onerror = null; e.currentTarget.src = '/images/product-placeholder.svg'; }}
                                            />
                                        ) : (
                                            <div className="flex size-full items-center justify-center"><ShoppingBag className="size-5 text-muted-foreground/30" /></div>
                                        )}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <h4 className="truncate text-sm font-bold text-foreground">{item.name}</h4>
                                        {item.vendor_name && <p className="truncate text-[11px] text-muted-foreground">{item.vendor_name}</p>}
                                        <p className="text-xs text-muted-foreground">{formatMoney(item.unit_price, currency, locale)}</p>
                                        <p className="text-xs font-bold text-primary">{formatMoney(item.line_total, currency, locale)}</p>
                                    </div>
                                    <div className="flex flex-col items-end gap-2">
                                        <div className="flex items-center rounded-md border border-border bg-muted">
                                            <button type="button" onClick={() => updateQty(item.product_id, item.quantity - 1)} className="flex size-7 items-center justify-center text-muted-foreground hover:text-primary" aria-label="-">
                                                <Minus className="size-3" />
                                            </button>
                                            <span className="w-6 text-center text-xs font-bold tabular-nums text-foreground">{item.quantity}</span>
                                            <button type="button" onClick={() => updateQty(item.product_id, item.quantity + 1)} disabled={atCeiling} className="flex size-7 items-center justify-center text-muted-foreground hover:text-primary disabled:cursor-not-allowed disabled:opacity-40" aria-label="+">
                                                <Plus className="size-3" />
                                            </button>
                                        </div>
                                        <button type="button" onClick={() => removeFromCart(item.product_id)} className="text-[10px] font-semibold text-[var(--color-danger-strong)] hover:underline">
                                            {cart.remove_line}
                                        </button>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>

                {items.length > 0 && (
                    <div className="border-t border-border p-5">
                        <div className="mb-3">
                            <label htmlFor="cart-coupon" className="mb-1.5 block text-xs font-semibold text-muted-foreground">{cart.coupon_label}</label>
                            <Input
                                id="cart-coupon"
                                placeholder={cart.coupon_placeholder}
                                value={couponCode}
                                onChange={(e) => setCouponCode(e.target.value)}
                                onBlur={() => { if (couponCode.trim()) applyCoupon(couponCode.trim()); }}
                            />
                        </div>
                        <div className="mb-4 flex items-center justify-between">
                            <span className="text-sm font-semibold text-muted-foreground">{cart.subtotal}</span>
                            <span className="text-lg font-bold text-foreground">{formatMoney(total, currency, locale)}</span>
                        </div>
                        <Button className="w-full" onClick={checkout} disabled={isBusy}>{cart.proceed_checkout}</Button>
                        <p className="mt-2 text-center text-[11px] text-muted-foreground">{cart.payment_way} {cart.cash_only}</p>
                    </div>
                )}
            </div>
        </div>
    );
}
