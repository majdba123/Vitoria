<?php

namespace App\Services\Commerce;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Order creation from the authoritative server cart (spec §7).
 *
 * The client supplies an address id, a payment method and optionally a coupon
 * code. Everything else — which products, what they cost, what is in stock,
 * what the coupon is worth — is read from the database inside the transaction.
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
        private readonly OrderStatusService $orderStatusService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @param  UserAddress|null  $address  Required by the current checkout. Null is
     *                                     accepted only for the legacy
     *                                     `POST /api/orders/checkout` endpoint,
     *                                     which predates the address book and is
     *                                     still used by shipped mobile clients.
     * @return Collection<int, Order> one order per vendor represented in the cart
     *
     * @throws CartException on any customer-actionable rejection
     */
    public function place(Cart $cart, User $user, ?UserAddress $address, string $paymentMethod): Collection
    {
        if (! in_array($paymentMethod, $this->availablePaymentMethods(), true)) {
            throw new CartException(__('cart.payment_method_unavailable'));
        }

        if ($address && $address->user_id !== $user->id) {
            // Defence in depth. The controller authorizes via policy first;
            // this makes the service safe to call from anywhere.
            throw new CartException(__('cart.address_invalid'));
        }

        // Drop withdrawn products and clamp over-large lines before pricing, so
        // the customer is never charged for something the cart cannot supply.
        $this->cartService->reconcile($cart);

        $summary = $this->cartService->summarize($cart);

        if ($summary['items'] === []) {
            throw new CartException(__('cart.empty'));
        }

        $subtotal = (float) $summary['subtotal'];
        $coupon = $cart->coupon_code
            ? $this->couponService->resolveUsable($cart->coupon_code, $user, $subtotal)
            : null;

        if ($cart->coupon_code && ! $coupon) {
            throw new CartException(__('cart.coupon_invalid'));
        }

        $orders = DB::transaction(function () use ($address, $cart, $coupon, $paymentMethod, $subtotal, $summary, $user) {
            $lines = collect($summary['items']);
            $byVendor = $lines->groupBy('vendor_id');

            $vendorSubtotals = $byVendor->map(
                fn (Collection $vendorLines) => round((float) $vendorLines->sum('line_total'), 2)
            );

            $totalDiscount = $coupon ? $this->couponService->discountFor($coupon, $subtotal) : 0.0;
            $discountByVendor = $this->allocateDiscount($vendorSubtotals, $totalDiscount);

            $created = [];

            foreach ($byVendor as $vendorId => $vendorLines) {
                $vendorSubtotal = (float) $vendorSubtotals->get($vendorId, 0);
                $vendorDiscount = (float) $discountByVendor->get($vendorId, 0);

                // Shipping and tax are structurally present but zero: no rate
                // exists in the business configuration yet (decisions D7, and
                // shipping is deferred). They are not invented here.
                $shipping = 0.0;
                $tax = 0.0;
                $grandTotal = max(round($vendorSubtotal - $vendorDiscount + $shipping + $tax, 2), 0);

                $order = Order::create(array_merge([
                    'order_number' => $this->generateOrderNumber(),
                    'user_id' => $user->id,
                    'vendor_id' => (int) $vendorId,
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'coupon_type' => $coupon?->discount_type,
                    'coupon_value' => $coupon?->discount_value,
                    'status' => Order::STATUS_PENDING,
                    'payment_way' => $paymentMethod,
                    'items_count' => 0,
                    'subtotal_amount' => $vendorSubtotal,
                    'coupon_discount_amount' => $vendorDiscount,
                    'shipping_total' => $shipping,
                    'tax_total' => $tax,
                    'grand_total' => $grandTotal,
                    'currency' => config('vetora.currency', 'SYP'),
                    // total_amount is retained for backward compatibility with
                    // existing reads; it mirrors grand_total (audit §2.2).
                    'total_amount' => $grandTotal,
                ], $address?->toOrderSnapshot() ?? []));

                $itemsCount = 0;

                foreach ($vendorLines as $line) {
                    $this->consumeStock((int) $line['product_id'], (int) $line['quantity'], (string) $line['name']);

                    $order->items()->create([
                        'product_id' => $line['product_id'],
                        'product_name' => $line['name'],
                        'original_unit_price' => $line['original_unit_price'],
                        'has_discount' => $line['has_discount'],
                        'applied_discount_percentage' => $line['has_discount']
                            ? round((1 - ($line['unit_price'] / max($line['original_unit_price'], 0.01))) * 100, 2)
                            : null,
                        'unit_price' => $line['unit_price'],
                        'quantity' => $line['quantity'],
                        'line_total' => $line['line_total'],
                        'discount_amount' => max(
                            round(($line['original_unit_price'] - $line['unit_price']) * $line['quantity'], 2),
                            0,
                        ),
                    ]);

                    $itemsCount += (int) $line['quantity'];
                }

                $order->update(['items_count' => $itemsCount]);
                $this->orderStatusService->recordInitial($order, $user);

                $created[] = $order;
            }

            if ($coupon) {
                // Claimed once for the checkout as a whole, against the first
                // order, so a multi-vendor cart consumes one coupon use.
                $this->couponService->claim($coupon, $user, $created[0], $totalDiscount);
            }

            $this->cartService->clear($cart);

            return collect($created);
        });

        $this->flushProductCache();

        foreach ($orders as $order) {
            $this->notificationService->notifyNewOrder($order);
        }

        return $orders;
    }

    /**
     * Payment methods actually configured for this platform. COD is the only
     * one the repository supports; no gateway is stubbed (decision D9).
     *
     * @return list<string>
     */
    public function availablePaymentMethods(): array
    {
        return ['cash'];
    }

    /**
     * Atomically take stock, or fail.
     *
     * The `quantity >= :qty` predicate lives in the UPDATE itself, so two
     * concurrent checkouts for the last unit cannot both succeed. An affected
     * -row count of 0 means someone else won the race. This is correct on
     * SQLite, MySQL and Postgres alike, unlike lockForUpdate() (audit R2).
     *
     * This is the only place product quantity is decremented (decision D1).
     *
     * @throws CartException
     */
    private function consumeStock(int $productId, int $quantity, string $productName): void
    {
        $affected = Product::query()
            ->whereKey($productId)
            ->where('quantity', '>=', $quantity)
            ->where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->update([
                'quantity' => DB::raw("quantity - {$quantity}"),
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            throw new CartException(__('cart.stock_taken', ['product' => $productName]));
        }
    }

    /**
     * Spread one order-level discount across vendor orders in proportion to
     * their subtotals, giving the rounding remainder to the last vendor so the
     * parts always sum exactly to the whole. Preserves the existing behaviour
     * documented in audit §2.2.
     *
     * @param  Collection<int|string, float>  $vendorSubtotals
     * @return Collection<int|string, float>
     */
    private function allocateDiscount(Collection $vendorSubtotals, float $totalDiscount): Collection
    {
        $sum = (float) $vendorSubtotals->sum();

        if ($totalDiscount <= 0 || $sum <= 0) {
            return $vendorSubtotals->map(fn () => 0.0);
        }

        $allocated = collect();
        $remaining = round($totalDiscount, 2);
        $lastKey = $vendorSubtotals->keys()->last();

        foreach ($vendorSubtotals as $vendorId => $vendorSubtotal) {
            if ($vendorId === $lastKey) {
                $allocated->put($vendorId, round(max($remaining, 0), 2));

                continue;
            }

            $share = min(round($totalDiscount * ($vendorSubtotal / $sum), 2), $vendorSubtotal);
            $allocated->put($vendorId, $share);
            $remaining = round($remaining - $share, 2);
        }

        return $allocated;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    private function flushProductCache(): void
    {
        try {
            Cache::tags(['products'])->flush();
        } catch (\Exception) {
            // The active cache driver is `file`, which does not support tags.
            // Stock figures are read live at checkout, so a stale catalog page
            // is a display concern only.
        }
    }
}
