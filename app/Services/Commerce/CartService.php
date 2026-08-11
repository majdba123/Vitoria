<?php

namespace App\Services\Commerce;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Authoritative server-side cart (spec §5).
 *
 * The frontend sends intent — "add product 12", "set line 4 to quantity 3".
 * It never sends a price, a discount, a subtotal, or an availability claim.
 * Every figure returned by this service is recomputed from the database.
 */
class CartService
{
    /**
     * Session key holding a guest's cart token. Guests are identified by the
     * web session rather than by a client-supplied id, so one guest cannot
     * read or mutate another guest's cart by guessing a token.
     */
    public const GUEST_TOKEN_KEY = 'vetora_cart_token';

    /**
     * Hard cap per line. Prevents a single request from reserving an entire
     * vendor's stock, and bounds the integer the DB has to hold.
     */
    public const MAX_LINE_QUANTITY = 999;

    /**
     * Resolve the caller's cart, creating one only when needed.
     */
    public function resolve(Request $request, bool $createIfMissing = true): ?Cart
    {
        $user = $request->user();

        if ($user instanceof User) {
            $cart = Cart::query()->where('user_id', $user->id)->first();

            if (! $cart && $createIfMissing) {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'last_activity_at' => now(),
                ]);
            }

            return $cart;
        }

        $token = $request->session()->get(self::GUEST_TOKEN_KEY);

        if ($token) {
            $cart = Cart::query()->where('session_token', $token)->first();

            if ($cart) {
                return $cart;
            }
        }

        if (! $createIfMissing) {
            return null;
        }

        $token = (string) Str::uuid();
        $request->session()->put(self::GUEST_TOKEN_KEY, $token);

        return Cart::create([
            'session_token' => $token,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Add a product to the cart, or increase an existing line.
     *
     * @throws CartException
     */
    public function add(Cart $cart, int $productId, int $quantity): Cart
    {
        $product = $this->findPurchasableProduct($productId);

        $existing = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $desired = ($existing?->quantity ?? 0) + $quantity;

        $this->assertQuantityAvailable($product, $desired);

        CartItem::query()->updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $product->id],
            ['quantity' => $desired],
        );

        $cart->touchActivity();

        return $cart->fresh();
    }

    /**
     * Set an existing line to an absolute quantity. Quantity 0 removes it.
     *
     * @throws CartException
     */
    public function updateQuantity(Cart $cart, int $productId, int $quantity): Cart
    {
        if ($quantity <= 0) {
            return $this->remove($cart, $productId);
        }

        $product = $this->findPurchasableProduct($productId);
        $this->assertQuantityAvailable($product, $quantity);

        $updated = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->update(['quantity' => $quantity, 'updated_at' => now()]);

        if ($updated === 0) {
            throw new CartException(__('cart.item_not_in_cart'));
        }

        $cart->touchActivity();

        return $cart->fresh();
    }

    public function remove(Cart $cart, int $productId): Cart
    {
        CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->delete();

        $cart->touchActivity();

        return $cart->fresh();
    }

    public function clear(Cart $cart): Cart
    {
        CartItem::query()->where('cart_id', $cart->id)->delete();
        $cart->forceFill(['coupon_code' => null, 'last_activity_at' => now()])->save();

        return $cart->fresh();
    }

    /**
     * Merge a guest cart into the authenticated user's cart at login (spec §5).
     *
     * Quantities are summed and then clamped to what the product can actually
     * supply, so a merge can never produce an unfulfillable line. The guest
     * cart is deleted afterwards and its session token dropped.
     */
    public function mergeGuestCartIntoUser(Request $request, User $user): ?Cart
    {
        $token = $request->session()->get(self::GUEST_TOKEN_KEY);

        if (! $token) {
            return Cart::query()->where('user_id', $user->id)->first();
        }

        $guestCart = Cart::query()->with('items')->where('session_token', $token)->first();
        $request->session()->forget(self::GUEST_TOKEN_KEY);

        if (! $guestCart) {
            return Cart::query()->where('user_id', $user->id)->first();
        }

        return DB::transaction(function () use ($guestCart, $user) {
            $userCart = Cart::query()->where('user_id', $user->id)->first();

            if (! $userCart) {
                // No existing cart: adopt the guest cart wholesale. Cheaper and
                // safer than copying rows, and preserves the line timestamps.
                $guestCart->forceFill([
                    'user_id' => $user->id,
                    'session_token' => null,
                    'last_activity_at' => now(),
                ])->save();

                return $guestCart->fresh();
            }

            $availability = Product::query()
                ->whereIn('id', $guestCart->items->pluck('product_id'))
                ->pluck('quantity', 'id');

            foreach ($guestCart->items as $guestItem) {
                $existing = CartItem::query()
                    ->where('cart_id', $userCart->id)
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                $available = (int) ($availability[$guestItem->product_id] ?? 0);

                if ($available <= 0) {
                    // Product went out of stock while the guest was shopping.
                    // Dropping the line is preferable to carrying a line that
                    // will only fail at checkout.
                    continue;
                }

                $merged = min(
                    ($existing?->quantity ?? 0) + $guestItem->quantity,
                    $available,
                    self::MAX_LINE_QUANTITY,
                );

                CartItem::query()->updateOrCreate(
                    ['cart_id' => $userCart->id, 'product_id' => $guestItem->product_id],
                    ['quantity' => $merged],
                );
            }

            if ($guestCart->coupon_code && ! $userCart->coupon_code) {
                $userCart->coupon_code = $guestCart->coupon_code;
            }

            $userCart->last_activity_at = now();
            $userCart->save();

            $guestCart->delete();

            return $userCart->fresh();
        });
    }

    /**
     * Drop lines that are no longer purchasable, and clamp lines that exceed
     * available stock (spec §5, "automatic invalid-item cleanup").
     *
     * @return array{removed: list<string>, adjusted: list<array{name: string, quantity: int}>}
     */
    public function reconcile(Cart $cart): array
    {
        $cart->load('items.product.vendor');
        $removed = [];
        $adjusted = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $this->isPurchasable($product)) {
                $removed[] = $product?->name ?? __('cart.unknown_product');
                $item->delete();

                continue;
            }

            if ($product->quantity <= 0) {
                $removed[] = $product->name;
                $item->delete();

                continue;
            }

            if ($item->quantity > $product->quantity) {
                $item->update(['quantity' => $product->quantity]);
                $adjusted[] = ['name' => $product->name, 'quantity' => (int) $product->quantity];
            }
        }

        if ($removed || $adjusted) {
            $cart->load('items.product.vendor');
        }

        return ['removed' => $removed, 'adjusted' => $adjusted];
    }

    /**
     * Fully-priced, display-ready cart. Every monetary value here is computed
     * from the database on this request — nothing is read back from the client.
     *
     * @return array<string, mixed>
     */
    public function summarize(Cart $cart): array
    {
        $cart->loadMissing('items.product.vendor');

        $lines = [];
        $subtotal = 0.0;

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $this->isPurchasable($product)) {
                continue;
            }

            $unitPrice = $product->getDiscountedPrice();
            $lineTotal = round($unitPrice * $item->quantity, 2);
            $subtotal = round($subtotal + $lineTotal, 2);

            $lines[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'vendor_id' => $product->vendor_id,
                'vendor_name' => $product->vendor?->store_name,
                'original_unit_price' => round((float) $product->price, 2),
                'unit_price' => $unitPrice,
                'has_discount' => $product->hasActiveDiscount(),
                'quantity' => $item->quantity,
                'available_quantity' => (int) $product->quantity,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'id' => $cart->id,
            'items' => $lines,
            'items_count' => array_sum(array_column($lines, 'quantity')),
            'subtotal' => $subtotal,
            'currency' => config('vetora.currency', 'SYP'),
        ];
    }

    /**
     * @throws CartException
     */
    private function findPurchasableProduct(int $productId): Product
    {
        $product = Product::query()->with('vendor:id,is_active')->find($productId);

        if (! $this->isPurchasable($product)) {
            throw new CartException(__('cart.product_unavailable'));
        }

        return $product;
    }

    private function isPurchasable(?Product $product): bool
    {
        return $product !== null
            && $product->is_active
            && $product->status === Product::STATUS_APPROVED
            && $product->vendor !== null
            && $product->vendor->is_active;
    }

    /**
     * @throws CartException
     */
    private function assertQuantityAvailable(Product $product, int $desired): void
    {
        if ($desired > self::MAX_LINE_QUANTITY) {
            throw new CartException(__('cart.quantity_too_large', ['max' => self::MAX_LINE_QUANTITY]));
        }

        if ($product->quantity < $desired) {
            throw new CartException(__('cart.insufficient_stock', [
                'product' => $product->name,
                'available' => (int) $product->quantity,
            ]));
        }
    }
}
