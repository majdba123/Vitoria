<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Vendor;
use App\Services\Commerce\CartException;
use App\Services\Commerce\OrderCancellationService;
use Laravel\Sanctum\Sanctum;

/**
 * Checkout, order lifecycle and cancellation (spec §7–§10).
 *
 * The load-bearing properties here are the ones the audit flagged as broken:
 * quantity is never negative, never decremented twice, and never restored
 * twice (audit R1, R2, decision D1).
 */
function checkoutProduct(int $quantity = 10, float $price = 100, ?Vendor $vendor = null): Product
{
    $vendor ??= Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    return Product::factory()->for($vendor)->create([
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => $quantity,
        'price' => $price,
    ]);
}

/**
 * A signed-in customer with a default address and a populated server cart.
 *
 * @param  array<int, int>  $items  product id => quantity
 * @return array{user: User, address: UserAddress, cart: Cart}
 */
function checkoutContext(array $items): array
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $address = UserAddress::factory()->default()->create([
        'user_id' => $user->id,
        'recipient_name' => 'Layla Haddad',
        'city' => 'Homs',
    ]);

    $cart = Cart::factory()->create(['user_id' => $user->id]);

    foreach ($items as $productId => $quantity) {
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    return ['user' => $user, 'address' => $address, 'cart' => $cart];
}

it('places an order, decrements stock once, and empties the cart', function () {
    $product = checkoutProduct(quantity: 10, price: 250);
    $context = checkoutContext([$product->id => 3]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated()
        ->assertJsonPath('data.orders_count', 1)
        ->assertJsonPath('data.orders.0.grand_total', '750.00');

    expect($product->refresh()->quantity)->toBe(7)
        ->and(CartItem::query()->count())->toBe(0);
});

it('snapshots the delivery address onto the order and never reads it back', function () {
    $product = checkoutProduct();
    $context = checkoutContext([$product->id => 1]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    $order = Order::query()->firstOrFail();
    expect($order->ship_recipient_name)->toBe('Layla Haddad')
        ->and($order->ship_city)->toBe('Homs');

    // Editing — or deleting — the address must not rewrite history.
    $context['address']->update(['recipient_name' => 'Someone Else', 'city' => 'Aleppo']);
    $context['address']->delete();

    expect($order->refresh()->ship_recipient_name)->toBe('Layla Haddad')
        ->and($order->ship_city)->toBe('Homs');
});

it('splits a multi-vendor cart into one order per vendor and allocates the coupon across them', function () {
    $first = checkoutProduct(quantity: 10, price: 600);
    $second = checkoutProduct(quantity: 10, price: 400);

    $context = checkoutContext([$first->id => 1, $second->id => 1]);

    Coupon::factory()->create([
        'code' => 'TEN',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
    ]);
    $context['cart']->update(['coupon_code' => 'TEN']);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated()->assertJsonPath('data.orders_count', 2);

    $orders = Order::query()->get();

    // 10% of the 1000 subtotal, split 60/40 by vendor subtotal, summing exactly.
    expect(round((float) $orders->sum('coupon_discount_amount'), 2))->toBe(100.0)
        ->and(round((float) $orders->sum('grand_total'), 2))->toBe(900.0);
});

it('consumes exactly one coupon use for a multi-vendor checkout', function () {
    $first = checkoutProduct(quantity: 5, price: 100);
    $second = checkoutProduct(quantity: 5, price: 100);
    $context = checkoutContext([$first->id => 1, $second->id => 1]);

    $coupon = Coupon::factory()->create([
        'code' => 'ONCE',
        'discount_type' => 'fixed',
        'discount_value' => 50,
        'usage_limit' => 1,
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
    ]);
    $context['cart']->update(['coupon_code' => 'ONCE']);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    expect($coupon->refresh()->used_count)->toBe(1)
        ->and(\App\Models\CouponRedemption::query()->count())->toBe(1);
});

it('will not let a coupon exceed its usage limit', function () {
    $product = checkoutProduct(quantity: 50, price: 100);
    $context = checkoutContext([$product->id => 1]);

    Coupon::factory()->create([
        'code' => 'LASTONE',
        'discount_type' => 'fixed',
        'discount_value' => 10,
        'usage_limit' => 1,
        'used_count' => 1,
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
    ]);
    $context['cart']->update(['coupon_code' => 'LASTONE']);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertStatus(422);

    expect(Order::query()->count())->toBe(0)
        ->and($product->refresh()->quantity)->toBe(50);
});

it('enforces a per-user coupon limit', function () {
    $product = checkoutProduct(quantity: 50, price: 100);
    $context = checkoutContext([$product->id => 1]);

    $coupon = Coupon::factory()->create([
        'code' => 'ONEPERUSER',
        'discount_type' => 'fixed',
        'discount_value' => 10,
        'per_user_limit' => 1,
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
    ]);

    \App\Models\CouponRedemption::create([
        'coupon_id' => $coupon->id,
        'user_id' => $context['user']->id,
        'discount_amount' => 10,
    ]);

    $context['cart']->update(['coupon_code' => 'ONEPERUSER']);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertStatus(422);
});

it('rejects checkout against another customer address', function () {
    $product = checkoutProduct();
    $context = checkoutContext([$product->id => 1]);

    $strangerAddress = UserAddress::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->postJson('/api/checkout', [
        'address_id' => $strangerAddress->id,
        'payment_method' => 'cash',
    ])->assertForbidden();

    expect(Order::query()->count())->toBe(0)
        ->and($product->refresh()->quantity)->toBe(10);
});

it('rejects a payment method that is not configured', function () {
    $product = checkoutProduct();
    $context = checkoutContext([$product->id => 1]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'card',
    ])->assertStatus(422);

    expect(Order::query()->count())->toBe(0);
});

it('never lets stock go negative when the cart outruns availability', function () {
    $product = checkoutProduct(quantity: 2);
    $context = checkoutContext([$product->id => 2]);

    // Stock is taken by someone else between adding to cart and checking out.
    $product->update(['quantity' => 1]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    // Reconcile clamped the line to the 1 that remained rather than failing.
    expect($product->refresh()->quantity)->toBe(0)
        ->and(Order::query()->firstOrFail()->items_count)->toBe(1);
});

it('does not decrement stock twice when checkout is attempted from a stale cart', function () {
    $product = checkoutProduct(quantity: 5);
    $context = checkoutContext([$product->id => 2]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    expect($product->refresh()->quantity)->toBe(3);

    // Replaying the same request finds an empty cart — the order is not duplicated.
    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertStatus(422);

    expect($product->refresh()->quantity)->toBe(3)
        ->and(Order::query()->count())->toBe(1);
});

it('records the order creation on the status timeline', function () {
    $product = checkoutProduct();
    $context = checkoutContext([$product->id => 1]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    $history = OrderStatusHistory::query()->firstOrFail();

    expect($history->previous_status)->toBeNull()
        ->and($history->new_status)->toBe(Order::STATUS_PENDING)
        ->and($history->changed_by_user_id)->toBe($context['user']->id);
});

/*
|--------------------------------------------------------------------------
| Cancellation and stock restoration (audit R1)
|--------------------------------------------------------------------------
*/

it('restores stock exactly once no matter how many times restoration is invoked', function () {
    $product = checkoutProduct(quantity: 10);
    $context = checkoutContext([$product->id => 4]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    expect($product->refresh()->quantity)->toBe(6);

    $order = Order::query()->firstOrFail();
    $service = app(OrderCancellationService::class);

    $service->cancel($order, $context['user'], 'customer', 'customer_changed_mind');
    expect($product->refresh()->quantity)->toBe(10);

    // This is the exact race the old implementation lost. Every repeat call
    // must be a no-op.
    foreach (range(1, 5) as $ignored) {
        expect($service->restoreStockOnce($order->refresh()))->toBeFalse();
    }

    expect($product->refresh()->quantity)->toBe(10);
});

it('rejects a second cancellation request over HTTP without touching stock', function () {
    $product = checkoutProduct(quantity: 10);
    $context = checkoutContext([$product->id => 4]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    $order = Order::query()->firstOrFail();

    $this->patchJson("/api/orders/{$order->id}/cancel", ['reason' => 'wrong_order'])->assertOk();
    expect($product->refresh()->quantity)->toBe(10);

    $this->patchJson("/api/orders/{$order->id}/cancel", ['reason' => 'wrong_order'])->assertStatus(422);
    expect($product->refresh()->quantity)->toBe(10);
});

it('stores who cancelled, why, and when', function () {
    $product = checkoutProduct();
    $context = checkoutContext([$product->id => 1]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    $order = Order::query()->firstOrFail();

    $this->patchJson("/api/orders/{$order->id}/cancel", [
        'reason' => 'duplicate_order',
        'notes' => 'Ordered the same thing twice.',
    ])->assertOk();

    $order->refresh();

    expect($order->status)->toBe(Order::STATUS_CANCELLED)
        ->and($order->cancellation_reason)->toBe('duplicate_order')
        ->and($order->cancelled_by_user_id)->toBe($context['user']->id)
        ->and($order->cancelled_at)->not->toBeNull()
        ->and($order->cancellation_notes)->toBe('Ordered the same thing twice.');
});

it('refuses an unrecognised cancellation reason', function () {
    $product = checkoutProduct();
    $context = checkoutContext([$product->id => 1]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    $order = Order::query()->firstOrFail();

    $this->patchJson("/api/orders/{$order->id}/cancel", ['reason' => 'because_i_said_so'])
        ->assertStatus(422);

    expect($order->refresh()->status)->toBe(Order::STATUS_PENDING);
});

it('will not cancel an order that has already shipped', function () {
    $product = checkoutProduct(quantity: 10);
    $context = checkoutContext([$product->id => 2]);

    $this->postJson('/api/checkout', [
        'address_id' => $context['address']->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    $order = Order::query()->firstOrFail();
    $order->forceFill(['status' => Order::STATUS_SHIPPED])->save();

    $this->patchJson("/api/orders/{$order->id}/cancel", ['reason' => 'wrong_order'])
        ->assertStatus(422);

    // Stock stays consumed: the goods have left the vendor.
    expect($product->refresh()->quantity)->toBe(8)
        ->and($order->refresh()->status)->toBe(Order::STATUS_SHIPPED);
});

/*
|--------------------------------------------------------------------------
| Status machine and authorization (spec §8, §54)
|--------------------------------------------------------------------------
*/

it('rejects a status the state machine does not allow from the current one', function () {
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $order = Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_PENDING]);

    Sanctum::actingAs($vendorUser);

    // pending may only go to confirmed or cancelled — never straight to shipped.
    $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => Order::STATUS_SHIPPED])
        ->assertStatus(422);

    expect($order->refresh()->status)->toBe(Order::STATUS_PENDING);
});

it('rejects a status value that is not a real status at all', function () {
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $order = Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_PENDING]);

    Sanctum::actingAs($vendorUser);

    $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'refunded_lol'])
        ->assertStatus(422);

    expect($order->refresh()->status)->toBe(Order::STATUS_PENDING);
});

it('advances an order through the fulfilment path and logs every step', function () {
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $order = Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_PENDING]);

    Sanctum::actingAs($vendorUser);

    foreach ([
        Order::STATUS_CONFIRMED,
        Order::STATUS_PREPARING,
        Order::STATUS_SHIPPED,
        Order::STATUS_OUT_FOR_DELIVERY,
        Order::STATUS_COMPLETED,
    ] as $status) {
        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => $status])
            ->assertOk()
            ->assertJsonPath('data.status', $status);
    }

    expect($order->refresh()->status)->toBe(Order::STATUS_COMPLETED)
        ->and(OrderStatusHistory::query()->where('order_id', $order->id)->count())->toBe(5);
});

it('stops a vendor from touching another vendor\'s order', function () {
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::factory()->create(['user_id' => $vendorUser->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    $otherVendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $foreignOrder = Order::factory()->create(['vendor_id' => $otherVendor->id, 'status' => Order::STATUS_PENDING]);

    Sanctum::actingAs($vendorUser);

    $this->patchJson("/api/vendor/orders/{$foreignOrder->id}/status", ['status' => Order::STATUS_CONFIRMED])
        ->assertForbidden();
    $this->patchJson("/api/vendor/orders/{$foreignOrder->id}/cancel", ['reason' => 'vendor_issue'])
        ->assertForbidden();

    expect($foreignOrder->refresh()->status)->toBe(Order::STATUS_PENDING);
});

it('stops a customer from reading another customer\'s order', function () {
    $owner = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/orders/{$order->id}")->assertForbidden();
});

it('stops a customer from driving fulfilment', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => Order::STATUS_PENDING]);

    Sanctum::actingAs($customer);

    // There is no customer-facing status route; the service refuses regardless.
    expect(fn () => app(\App\Services\Commerce\OrderStatusService::class)->transition(
        $order,
        Order::STATUS_CONFIRMED,
        $customer,
        'customer',
    ))->toThrow(CartException::class);

    expect($order->refresh()->status)->toBe(Order::STATUS_PENDING);
});
