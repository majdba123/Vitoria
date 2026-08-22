<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\ShippingRate;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Services\Commerce\ReturnService;
use App\Services\Commerce\VendorLedgerService;
use Laravel\Sanctum\Sanctum;

/**
 * Shipping, invoices and the vendor ledger (spec §14, §19, §20).
 */
function silProduct(int $quantity = 10, float $price = 100, ?Vendor $vendor = null, ?Category $category = null): Product
{
    $vendor ??= Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $category ??= Category::factory()->create(['commission' => 10]);

    return Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => $quantity,
        'price' => $price,
    ]);
}

/**
 * Places one order and drives it to `completed` via the real vendor HTTP
 * endpoints, mirroring PaymentsReturnsRefundsTest::prrDeliveredOrder.
 *
 * @return array{order: Order, customer: User, vendorUser: User, vendor: Vendor, product: Product, item: \App\Models\OrderItem, category: Category}
 */
function silDeliveredOrder(int $quantity = 10, int $purchaseQty = 2, float $price = 100, float $commission = 10, ?string $governorate = null): array
{
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $category = Category::factory()->create(['commission' => $commission]);
    $product = silProduct($quantity, $price, $vendor, $category);

    $customer = User::factory()->create();
    Sanctum::actingAs($customer);

    $address = UserAddress::factory()->default()->create(array_filter([
        'user_id' => $customer->id,
        'governorate' => $governorate,
    ]));
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

    return compact('order', 'customer', 'vendorUser', 'vendor', 'product', 'item', 'category');
}

// --- Shipping -----------------------------------------------------------

it('creates a pending shipment at checkout with the seeded zero rate', function () {
    $context = silDeliveredOrder(quantity: 10, purchaseQty: 1, price: 200);

    $shipment = Shipment::query()->where('order_id', $context['order']->id)->firstOrFail();

    expect($shipment->method->code)->toBe('standard_delivery')
        ->and((float) $context['order']->shipping_total)->toBe(0.0);
});

it('keeps the shipment status in lockstep with order fulfilment', function () {
    $context = silDeliveredOrder(purchaseQty: 1);

    $shipment = Shipment::query()->where('order_id', $context['order']->id)->firstOrFail();

    expect($shipment->status)->toBe(Shipment::STATUS_DELIVERED)
        ->and($shipment->delivered_at)->not->toBeNull()
        ->and(ShipmentEvent::query()->where('shipment_id', $shipment->id)->count())->toBe(4);
});

it('lets a vendor report a failed delivery and then a return to the vendor', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::query()->whereKey($vendor->id)->update(['user_id' => $vendorUser->id]);
    $order = Order::factory()->create(['vendor_id' => $vendor->id]);
    $shipment = Shipment::factory()->create(['order_id' => $order->id]);

    Sanctum::actingAs($vendorUser);

    $this->patchJson("/api/vendor/shipments/{$shipment->id}/failed", ['reason' => 'Recipient unreachable'])
        ->assertOk();
    expect($shipment->refresh()->status)->toBe(Shipment::STATUS_FAILED)
        ->and($shipment->failure_reason)->toBe('Recipient unreachable');

    $this->patchJson("/api/vendor/shipments/{$shipment->id}/returned")->assertOk();
    expect($shipment->refresh()->status)->toBe(Shipment::STATUS_RETURNED);

    // Terminal — cannot fail a shipment that was already returned.
    $this->patchJson("/api/vendor/shipments/{$shipment->id}/failed", ['reason' => 'again'])
        ->assertStatus(422);
});

it('stops a vendor from managing another vendor\'s shipment', function () {
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::factory()->create(['user_id' => $vendorUser->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    $otherVendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $foreignOrder = Order::factory()->create(['vendor_id' => $otherVendor->id]);
    $shipment = Shipment::factory()->create(['order_id' => $foreignOrder->id]);

    Sanctum::actingAs($vendorUser);

    $this->patchJson("/api/vendor/shipments/{$shipment->id}/failed", ['reason' => 'x'])->assertForbidden();
});

it('applies a real, non-zero shipping rate once an admin configures one — never a fake default', function () {
    $rate = ShippingRate::query()->whereHas('method', fn ($q) => $q->where('code', 'standard_delivery'))->firstOrFail();
    expect((float) $rate->rate)->toBe(0.0);

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->patchJson("/api/admin/shipping/rates/{$rate->id}", ['rate' => 1500])->assertOk();

    $product = silProduct(quantity: 5, price: 300);
    $customer = User::factory()->create();
    Sanctum::actingAs($customer);
    $address = UserAddress::factory()->default()->create(['user_id' => $customer->id]);
    $cart = Cart::factory()->create(['user_id' => $customer->id]);
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->postJson('/api/checkout', ['address_id' => $address->id, 'payment_method' => 'cash'])
        ->assertCreated()
        ->assertJsonPath('data.orders.0.grand_total', '1800.00');

    $order = Order::query()->where('user_id', $customer->id)->firstOrFail();
    expect((float) $order->shipping_total)->toBe(1500.0);
});

// --- Invoices -------------------------------------------------------------

it('creates an invoice snapshot matching the order totals at checkout', function () {
    $context = silDeliveredOrder(quantity: 10, purchaseQty: 3, price: 150);

    $invoice = Invoice::query()->where('order_id', $context['order']->id)->firstOrFail();

    expect($invoice->grand_total)->toEqual($context['order']->grand_total)
        ->and($invoice->payment_method)->toBe('cash')
        ->and($invoice->invoice_number)->toStartWith('INV-');
});

it('only the invoice owner, its vendor, or an admin may view it', function () {
    $context = silDeliveredOrder(purchaseQty: 1);
    $invoice = Invoice::query()->where('order_id', $context['order']->id)->firstOrFail();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/invoices/{$invoice->id}")->assertForbidden();

    Sanctum::actingAs($context['customer']);
    $this->getJson("/api/invoices/{$invoice->id}")->assertOk();
});

it('requires ownership to open the printable invoice page', function () {
    $context = silDeliveredOrder(purchaseQty: 1);
    $invoice = Invoice::query()->where('order_id', $context['order']->id)->firstOrFail();

    $this->actingAs(User::factory()->create())
        ->get("/invoices/{$invoice->id}/print")
        ->assertForbidden();

    $this->actingAs($context['customer'])
        ->get("/invoices/{$invoice->id}/print")
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('@page', false)
        ->assertSee('size: A4', false)
        ->assertSee('min-height: 0', false)
        ->assertSee('break-inside: avoid', false)
        ->assertSee('display: none !important', false);
});

// --- Vendor ledger ----------------------------------------------------

it('records a sale and its commission using the category rate captured at completion', function () {
    $context = silDeliveredOrder(quantity: 10, purchaseQty: 2, price: 100, commission: 10);

    $sale = VendorLedgerEntry::query()->where('order_id', $context['order']->id)->where('type', 'sale')->firstOrFail();
    $commission = VendorLedgerEntry::query()->where('order_id', $context['order']->id)->where('type', 'commission')->firstOrFail();

    expect((float) $sale->amount)->toBe(200.0)
        ->and($sale->direction)->toBe('credit')
        ->and((float) $commission->amount)->toBe(20.0)
        ->and($commission->direction)->toBe('debit');

    $summary = app(VendorLedgerService::class)->summary($context['vendor']);
    expect($summary['gross_sales'])->toBe(200.0)
        ->and($summary['commission'])->toBe(20.0)
        ->and($summary['net_earnings'])->toBe(180.0)
        ->and($summary['outstanding'])->toBe(180.0);
});

it('does not record a sale twice for the same order', function () {
    $context = silDeliveredOrder(purchaseQty: 1);

    app(VendorLedgerService::class)->recordSale($context['order']->fresh());

    expect(VendorLedgerEntry::query()->where('order_id', $context['order']->id)->where('type', 'sale')->count())->toBe(1);
});

it('records a refund on the ledger and reduces the outstanding balance', function () {
    $context = silDeliveredOrder(quantity: 10, purchaseQty: 1, price: 100, commission: 10);

    Sanctum::actingAs($context['customer']);
    $return = app(ReturnService::class)->request($context['order'], $context['customer'], [
        ['order_item_id' => $context['item']->id, 'quantity' => 1],
    ], 'other');

    Sanctum::actingAs($context['vendorUser']);
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => \App\Models\OrderReturn::STATUS_APPROVED])->assertOk();
    $this->patchJson("/api/vendor/returns/{$return->id}/status", ['status' => \App\Models\OrderReturn::STATUS_RECEIVED])->assertOk();
    $refundId = $this->postJson("/api/vendor/returns/{$return->id}/refund")->assertCreated()->json('data.id');

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->patchJson("/api/admin/refunds/{$refundId}/complete")->assertOk();

    $refundEntry = VendorLedgerEntry::query()->where('order_id', $context['order']->id)->where('type', 'refund')->firstOrFail();
    expect((float) $refundEntry->amount)->toBe(100.0);

    // gross 100, commission 10, refund 100 → net -10, floored to 0 outstanding.
    $summary = app(VendorLedgerService::class)->summary($context['vendor']);
    expect($summary['refunds'])->toBe(100.0)
        ->and($summary['net_earnings'])->toBe(-10.0)
        ->and($summary['outstanding'])->toBe(0.0);
});

it('caps a settlement at the outstanding balance and records it on the ledger', function () {
    $context = silDeliveredOrder(quantity: 10, purchaseQty: 2, price: 100, commission: 0);
    // net_earnings = 200 (no commission configured for this test).

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->postJson("/api/admin/vendors/{$context['vendor']->id}/settlements", [
        'amount' => 999,
        'method' => 'bank_transfer',
    ])->assertStatus(422);

    $this->postJson("/api/admin/vendors/{$context['vendor']->id}/settlements", [
        'amount' => 150,
        'method' => 'bank_transfer',
        'reference' => 'TRX-1',
    ])->assertCreated();

    $summary = app(VendorLedgerService::class)->summary($context['vendor']);
    expect($summary['settled'])->toBe(150.0)
        ->and($summary['outstanding'])->toBe(50.0);
});

it('records a manual admin adjustment on the ledger', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->postJson("/api/admin/vendors/{$vendor->id}/ledger/adjustments", [
        'amount' => 25,
        'direction' => 'credit',
        'description' => 'Goodwill credit',
    ])->assertCreated();

    $summary = app(VendorLedgerService::class)->summary($vendor);
    expect($summary['net_earnings'])->toBe(25.0);
});
