<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Vendor;
use App\Services\Commerce\RefundService;
use App\Services\Commerce\ReturnService;
use Laravel\Sanctum\Sanctum;

/**
 * Payments, returns and refunds (spec §11–§13, Phase C).
 *
 * The load-bearing properties mirror CheckoutAndOrderLifecycleTest: a payment
 * settles exactly once, stock is restored on receipt exactly once, and a
 * refund can never be duplicated or overdraw what was actually paid.
 */
function prrProduct(int $quantity = 10, float $price = 100, ?Vendor $vendor = null): Product
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
 * Places one order for one product and drives it, via the real vendor HTTP
 * endpoints, all the way to `completed` — so its payment settles through the
 * same path production traffic uses.
 *
 * @return array{order: Order, customer: User, vendorUser: User, vendor: Vendor, product: Product, item: OrderItem}
 */
function prrDeliveredOrder(int $quantity = 10, int $purchaseQty = 2, float $price = 100): array
{
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $product = prrProduct($quantity, $price, $vendor);

    $customer = User::factory()->create();
    Sanctum::actingAs($customer);

    $address = UserAddress::factory()->default()->create(['user_id' => $customer->id]);
    $cart = Cart::factory()->create(['user_id' => $customer->id]);
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => $purchaseQty]);

    test()->postJson('/api/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cash',
    ])->assertCreated();

    $order = Order::query()->where('user_id', $customer->id)->firstOrFail();

    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::query()->whereKey($vendor->id)->update(['user_id' => $vendorUser->id]);

    Sanctum::actingAs($vendorUser);

    foreach ([
        Order::STATUS_CONFIRMED,
        Order::STATUS_PREPARING,
        Order::STATUS_SHIPPED,
        Order::STATUS_OUT_FOR_DELIVERY,
        Order::STATUS_COMPLETED,
    ] as $status) {
        test()->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => $status])->assertOk();
    }

    $order->refresh();
    $item = $order->items()->firstOrFail();

    return compact('order', 'customer', 'vendorUser', 'vendor', 'product', 'item');
}

it('creates a payment at checkout, for the grand total, that settles once delivered', function () {
    $context = prrDeliveredOrder(quantity: 10, purchaseQty: 2, price: 150);

    $payment = Payment::query()->where('order_id', $context['order']->id)->firstOrFail();

    // Driven all the way to `completed` by the helper, so COD has settled by
    // the time we get here — see the next test for the moment it flips.
    expect($payment->status)->toBe(Payment::STATUS_PAID)
        ->and((float) $payment->amount)->toBe(300.0)
        ->and($payment->provider)->toBe(Payment::PROVIDER_COD);
});

it('marks the payment paid only once the order reaches completed', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::query()->whereKey($vendor->id)->update(['user_id' => $vendorUser->id]);
    $order = Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_OUT_FOR_DELIVERY, 'grand_total' => 300]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 300]);

    expect($payment->status)->toBe(Payment::STATUS_PENDING);

    Sanctum::actingAs($vendorUser);
    $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => Order::STATUS_COMPLETED])->assertOk();

    expect($payment->refresh()->status)->toBe(Payment::STATUS_PAID)
        ->and($payment->paid_at)->not->toBeNull();
});

it('cancels an unsettled payment when the order is cancelled', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => Order::STATUS_PENDING]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'user_id' => $customer->id]);

    Sanctum::actingAs($customer);
    $this->patchJson("/api/orders/{$order->id}/cancel", ['reason' => 'customer_changed_mind'])->assertOk();

    expect($payment->refresh()->status)->toBe(Payment::STATUS_CANCELLED);
});

it('leaves an already-settled payment untouched by cancellation', function () {
    $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);
    $payment = Payment::factory()->paid()->create(['order_id' => $order->id]);

    app(\App\Services\Commerce\PaymentService::class)->cancelIfUnsettled($payment);

    expect($payment->refresh()->status)->toBe(Payment::STATUS_PAID);
});

it('requests a return for a delivered order', function () {
    $context = prrDeliveredOrder(purchaseQty: 3);

    Sanctum::actingAs($context['customer']);

    $this->postJson("/api/orders/{$context['order']->id}/returns", [
        'reason' => 'damaged_on_arrival',
        'items' => [['order_item_id' => $context['item']->id, 'quantity' => 2]],
    ])->assertCreated()->assertJsonPath('data.status', OrderReturn::STATUS_REQUESTED);

    expect(OrderReturn::query()->where('order_id', $context['order']->id)->count())->toBe(1);
});

it('rejects a return request for an order that has not been delivered', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => Order::STATUS_PENDING]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

    Sanctum::actingAs($customer);

    $this->postJson("/api/orders/{$order->id}/returns", [
        'reason' => 'other',
        'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
    ])->assertStatus(422);

    expect(OrderReturn::query()->count())->toBe(0);
});

it('rejects returning more than was purchased', function () {
    $context = prrDeliveredOrder(purchaseQty: 2);

    Sanctum::actingAs($context['customer']);

    $this->postJson("/api/orders/{$context['order']->id}/returns", [
        'reason' => 'other',
        'items' => [['order_item_id' => $context['item']->id, 'quantity' => 3]],
    ])->assertStatus(422);
});

it('stops a customer from requesting a return on someone else\'s order', function () {
    $context = prrDeliveredOrder();

    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/orders/{$context['order']->id}/returns", [
        'reason' => 'other',
        'items' => [['order_item_id' => $context['item']->id, 'quantity' => 1]],
    ])->assertForbidden();
});

it('stops a vendor from reviewing another vendor\'s return', function () {
    $context = prrDeliveredOrder(purchaseQty: 2);
    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 1],
    ], 'other');

    $otherVendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::factory()->create(['user_id' => $otherVendorUser->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    Sanctum::actingAs($otherVendorUser);
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_UNDER_REVIEW])
        ->assertForbidden();
});

it('rejects an invalid return transition', function () {
    $context = prrDeliveredOrder(purchaseQty: 2);
    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 1],
    ], 'other');

    Sanctum::actingAs($context['vendorUser']);
    // requested may not jump straight to received.
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_RECEIVED])
        ->assertStatus(422);

    expect($return->refresh()->status)->toBe(OrderReturn::STATUS_REQUESTED);
});

it('restores stock exactly once when a return is received', function () {
    $context = prrDeliveredOrder(quantity: 10, purchaseQty: 3);
    $quantityAfterPurchase = $context['product']->refresh()->quantity;

    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 2],
    ], 'quality_issue');

    Sanctum::actingAs($context['vendorUser']);
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_APPROVED])->assertOk();
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_RECEIVED])->assertOk();

    expect($context['product']->refresh()->quantity)->toBe($quantityAfterPurchase + 2);

    $repeated = app(ReturnService::class)->restoreStockOnce($return->refresh());

    expect($repeated)->toBeFalse()
        ->and($context['product']->refresh()->quantity)->toBe($quantityAfterPurchase + 2);
});

it('lets a customer cancel their own pending return but not someone else\'s', function () {
    $context = prrDeliveredOrder(purchaseQty: 2);
    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 1],
    ], 'other');

    Sanctum::actingAs(User::factory()->create());
    $this->patchJson("/api/returns/{$return->id}/cancel")->assertForbidden();

    Sanctum::actingAs($context['customer']);
    $this->patchJson("/api/returns/{$return->id}/cancel")->assertOk();

    expect($return->refresh()->status)->toBe(OrderReturn::STATUS_CANCELLED);
});

it('initiates and completes a refund, settling the payment', function () {
    $context = prrDeliveredOrder(purchaseQty: 2, price: 100);
    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 2],
    ], 'damaged_on_arrival');

    Sanctum::actingAs($context['vendorUser']);
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_APPROVED])->assertOk();
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_RECEIVED])->assertOk();

    $refundId = $this->postJson("/api/vendor/returns/{$return->id}/refund")
        ->assertCreated()
        ->json('data.id');

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/refunds/{$refundId}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', Refund::STATUS_COMPLETED);

    $payment = Payment::query()->where('order_id', $context['order']->id)->firstOrFail();
    expect((float) $payment->refunded_amount)->toBe(200.0)
        ->and($payment->status)->toBe(Payment::STATUS_REFUNDED)
        ->and($return->refresh()->status)->toBe(OrderReturn::STATUS_COMPLETED);
});

it('prevents a duplicate refund for the same return', function () {
    $context = prrDeliveredOrder(purchaseQty: 2, price: 100);
    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 1],
    ], 'other');

    Sanctum::actingAs($context['vendorUser']);
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_APPROVED])->assertOk();
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_RECEIVED])->assertOk();

    $this->postJson("/api/vendor/returns/{$return->id}/refund")->assertCreated();
    $this->postJson("/api/vendor/returns/{$return->id}/refund")->assertStatus(422);

    expect(Refund::query()->where('order_return_id', $return->id)->count())->toBe(1);
});

it('cannot complete the same refund twice', function () {
    $context = prrDeliveredOrder(purchaseQty: 1, price: 100);
    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 1],
    ], 'other');

    Sanctum::actingAs($context['vendorUser']);
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_APPROVED])->assertOk();
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_RECEIVED])->assertOk();
    $refund = app(RefundService::class)->initiate($return->refresh(), $context['vendorUser']);

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/refunds/{$refund->id}/complete")->assertOk();
    $this->patchJson("/api/admin/refunds/{$refund->id}/complete")->assertStatus(422);

    $payment = Payment::query()->where('order_id', $context['order']->id)->firstOrFail();
    expect((float) $payment->refunded_amount)->toBe(100.0);
});

it('caps a refund at what remains available on the payment', function () {
    $context = prrDeliveredOrder(purchaseQty: 1, price: 100);
    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 1],
    ], 'other');

    Sanctum::actingAs($context['vendorUser']);
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_APPROVED])->assertOk();
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_RECEIVED])->assertOk();

    $this->postJson("/api/vendor/returns/{$return->id}/refund", ['amount' => 999])->assertStatus(422);

    expect(Refund::query()->where('order_return_id', $return->id)->count())->toBe(0);
});

it('only an admin can complete or cancel a refund', function () {
    $context = prrDeliveredOrder(purchaseQty: 1, price: 100);
    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 1],
    ], 'other');

    Sanctum::actingAs($context['vendorUser']);
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_APPROVED])->assertOk();
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => OrderReturn::STATUS_RECEIVED])->assertOk();
    $refund = app(RefundService::class)->initiate($return->refresh(), $context['vendorUser']);

    $this->patchJson("/api/admin/refunds/{$refund->id}/complete")->assertForbidden();
});
