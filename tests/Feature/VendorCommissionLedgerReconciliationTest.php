<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Commerce\VendorLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Full-stack audit finding: the vendor/admin commission dashboards recomputed
 * commission live from each category's *current* commission rate on every
 * request — exactly the drift VendorLedgerService was built to eliminate —
 * and a separate `paid_amount` scalar could be double-written with no cap,
 * no ledger entry, and no audit trail. These tests prove both are fixed.
 */
function completedOrderWithLedgerEntry(Vendor $vendor, Product $product, float $lineTotal): Order
{
    $order = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'subtotal_amount' => $lineTotal,
        'total_amount' => $lineTotal,
    ]);
    OrderItem::factory()->for($order)->for($product)->create(['line_total' => $lineTotal]);

    app(VendorLedgerService::class)->recordSale($order->fresh());

    return $order;
}

test('changing a category commission rate does not retroactively change an already-ledgered order\'s commission total', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    $category = Category::query()->create(['name' => 'Rate Drift Category', 'type' => Category::TYPE_AGRICULTURE, 'commission' => 10]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);

    completedOrderWithLedgerEntry($vendor, $product, 1000);

    Sanctum::actingAs($vendor->user);
    $this->getJson('/api/vendor/commission-stats')
        ->assertOk()
        ->assertJsonPath('data.financials.commission_total', 100);

    // Admin raises the category's commission rate after the sale already happened.
    $category->update(['commission' => 50]);

    $this->getJson('/api/vendor/commission-stats')
        ->assertOk()
        ->assertJsonPath('data.financials.commission_total', 100); // unchanged — snapshotted, not recomputed
});

test('the admin vendor commission dashboard reads the same ledger-sourced commission total as the vendor\'s own', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    $category = Category::query()->create(['name' => 'Shared Ledger Category', 'type' => Category::TYPE_AGRICULTURE, 'commission' => 15]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);

    completedOrderWithLedgerEntry($vendor, $product, 2000);

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson("/api/admin/vendors/{$vendor->id}/commission-stats")
        ->assertOk()
        ->assertJsonPath('data.financials.commission_total', 300)
        // outstanding = net earnings owed to the vendor (gross sales − commission),
        // not the commission itself: 2000 − 300 = 1700.
        ->assertJsonPath('data.financials.remaining_amount', 1700)
        ->assertJsonPath('data.financials.paid_amount', 0);
});

test('admin paid-amount screen records an incremental, capped, ledgered settlement instead of an uncapped raw overwrite', function () {
    $vendor = Vendor::factory()->create();
    $category = Category::query()->create(['name' => 'Payout Category', 'type' => Category::TYPE_AGRICULTURE, 'commission' => 20]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);
    completedOrderWithLedgerEntry($vendor, $product, 1000); // commission = 200, net-earnings/outstanding = 800

    Sanctum::actingAs(User::factory()->admin()->create());

    // First payout: sets the "paid to date" total to 150.
    $this->postJson("/api/admin/vendors/{$vendor->id}/commission-paid", ['paid_amount' => 150])
        ->assertOk()
        ->assertJsonPath('data.paid_amount', 150);

    expect(\App\Models\VendorSettlement::query()->where('vendor_id', $vendor->id)->count())->toBe(1);
    expect($vendor->fresh()->paid_amount)->toEqualWithDelta(150.0, 0.001);

    // Second call with the SAME total must not double-pay — no new settlement,
    // rejected as "not greater than what's already settled".
    $this->postJson("/api/admin/vendors/{$vendor->id}/commission-paid", ['paid_amount' => 150])
        ->assertStatus(422);
    expect(\App\Models\VendorSettlement::query()->where('vendor_id', $vendor->id)->count())->toBe(1);

    // Raising the total to 200 (the full outstanding amount) records only the
    // 50 delta as a second settlement.
    $this->postJson("/api/admin/vendors/{$vendor->id}/commission-paid", ['paid_amount' => 200])
        ->assertOk()
        ->assertJsonPath('data.paid_amount', 200);
    expect(\App\Models\VendorSettlement::query()->where('vendor_id', $vendor->id)->count())->toBe(2);

    // Attempting to pay beyond the outstanding balance is rejected, capped by
    // the ledger — the old endpoint had no such cap at all.
    $this->postJson("/api/admin/vendors/{$vendor->id}/commission-paid", ['paid_amount' => 9999])
        ->assertStatus(422);
    expect($vendor->fresh()->paid_amount)->toEqualWithDelta(200.0, 0.001);
});

test('the ledger backfill command records a missing sale entry for a pre-existing completed order exactly once', function () {
    $vendor = Vendor::factory()->create();
    $category = Category::query()->create(['name' => 'Backfill Category', 'type' => Category::TYPE_AGRICULTURE, 'commission' => 10]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);

    // Simulate an order that completed before the ledger existed: no
    // recordSale() call was ever made for it.
    $order = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'subtotal_amount' => 500,
        'total_amount' => 500,
    ]);
    OrderItem::factory()->for($order)->for($product)->create(['line_total' => 500]);

    expect(\App\Models\VendorLedgerEntry::query()->where('order_id', $order->id)->count())->toBe(0);

    $this->artisan('ledger:backfill-completed-orders')->assertSuccessful();

    expect(\App\Models\VendorLedgerEntry::query()->where('order_id', $order->id)->where('type', 'sale')->count())->toBe(1);
    expect(\App\Models\VendorLedgerEntry::query()->where('order_id', $order->id)->where('type', 'commission')->count())->toBe(1);

    // Running it again must not duplicate anything (recordSale is idempotent).
    $this->artisan('ledger:backfill-completed-orders')->assertSuccessful();
    expect(\App\Models\VendorLedgerEntry::query()->where('order_id', $order->id)->count())->toBe(2);
});
