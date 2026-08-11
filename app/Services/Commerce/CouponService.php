<?php

namespace App\Services\Commerce;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * All coupon validation is server-side (spec §21). The client may send a code
 * and nothing else; the discount is always computed here.
 */
class CouponService
{
    /**
     * Resolve a code into a coupon the given user may actually use on a cart
     * of the given subtotal. Returns null when the code is unusable, so the
     * caller can present one generic message rather than enumerating which
     * rule failed (which would let an attacker probe coupon configuration).
     */
    public function resolveUsable(string $code, ?User $user, float $subtotal): ?Coupon
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        $coupon = Coupon::query()->where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active || $coupon->status !== Coupon::STATUS_ACTIVE) {
            return null;
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->gt($now)) {
            return null;
        }

        if ($coupon->ends_at && $coupon->ends_at->lt($now)) {
            return null;
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return null;
        }

        if ($coupon->min_order_subtotal !== null && $subtotal < (float) $coupon->min_order_subtotal) {
            return null;
        }

        if ($user && ! $this->passesUserRules($coupon, $user)) {
            return null;
        }

        return $coupon;
    }

    /**
     * Per-user limit and first-order-only both require knowing who is buying,
     * so they are only enforceable for authenticated customers.
     */
    private function passesUserRules(Coupon $coupon, User $user): bool
    {
        if ($coupon->per_user_limit !== null) {
            $used = CouponRedemption::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();

            if ($used >= $coupon->per_user_limit) {
                return false;
            }
        }

        if ($coupon->first_order_only) {
            $hasOrdered = Order::query()
                ->where('user_id', $user->id)
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->exists();

            if ($hasOrdered) {
                return false;
            }
        }

        return true;
    }

    /**
     * Discount for a given subtotal, respecting the cap and never exceeding
     * the subtotal itself (a coupon can zero an order, never invert it).
     */
    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $discount = $coupon->discount_type === 'fixed'
            ? (float) $coupon->discount_value
            : $subtotal * ((float) $coupon->discount_value / 100);

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return round(min($discount, $subtotal), 2);
    }

    /**
     * Atomically claim one use of the coupon.
     *
     * The conditional UPDATE is what enforces the cap. `lockForUpdate()` would
     * be a no-op on SQLite (audit §1.1), so the guard lives in the WHERE clause
     * and correctness comes from the affected-row count.
     *
     * @throws CartException when the coupon was exhausted by a concurrent order
     */
    public function claim(Coupon $coupon, User $user, Order $order, float $discountAmount): void
    {
        $query = Coupon::query()->whereKey($coupon->id);

        if ($coupon->usage_limit !== null) {
            $query->whereRaw('used_count < usage_limit');
        }

        $claimed = $query->update([
            'used_count' => DB::raw('used_count + 1'),
            'updated_at' => now(),
        ]);

        if ($claimed === 0) {
            throw new CartException(__('cart.coupon_exhausted'));
        }

        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
        ]);
    }
}
