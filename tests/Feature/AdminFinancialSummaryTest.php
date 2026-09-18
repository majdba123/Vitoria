<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Services\Commerce\VendorLedgerService;
use Laravel\Sanctum\Sanctum;

/**
 * Admin-wide "total commission across all vendors" aggregate (stakeholder
 * review: "إجمالي العمولات — ربط العمولات لجميع التجار تظهر على صفحة
 * الأدمن"). Mirrors the ledger-authoritative pattern already proven for a
 * single vendor in VendorCommissionLedgerReconciliationTest, extended to the
 * platform-wide total exposed at GET /api/admin/financials/summary — built
 * with database aggregation over the same immutable vendor ledger, never
 * recomputed from the category's current commission rate.
 */
function afsCompletedOrder(Vendor $vendor, float $lineTotal, float $commissionRate): Order
{
    $category = Category::factory()->create(['commission' => $commissionRate]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);

    $order = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'subtotal_amount' => $lineTotal,
        'total_amount' => $lineTotal,
    ]);
    OrderItem::factory()->for($order)->for($product)->create(['line_total' => $lineTotal]);

    app(VendorLedgerService::class)->recordSale($order->fresh());

    return $order;
}

test('admin total commission equals the sum of every vendor\'s ledgered commission', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $vendorA = Vendor::factory()->create();
    $vendorB = Vendor::factory()->create();

    afsCompletedOrder($vendorA, 1000, 10); // commission 100
    afsCompletedOrder($vendorB, 2000, 5); // commission 100

    $response = $this->getJson('/api/admin/financials/summary')->assertOk();

    $response->assertJsonPath('data.commission_total', 200)
        ->assertJsonPath('data.gross_sales_total', 3000)
        ->assertJsonPath('data.vendor_count', 2);

    $commissionByVendor = collect($response->json('data.top_vendors_by_commission'))->pluck('commission', 'vendor_id');
    expect((float) $commissionByVendor[$vendorA->id])->toBe(100.0)
        ->and((float) $commissionByVendor[$vendorB->id])->toBe(100.0);
});

test('a vendor with no commission activity does not corrupt the admin total', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $vendorWithSales = Vendor::factory()->create();
    $vendorWithNothing = Vendor::factory()->create(); // no orders, no ledger entries at all

    afsCompletedOrder($vendorWithSales, 500, 20); // commission 100

    $response = $this->getJson('/api/admin/financials/summary')->assertOk();

    $response->assertJsonPath('data.commission_total', 100)
        // The zero-activity vendor never wrote a ledger row, so it is
        // absent from vendor_count entirely rather than counted as a 0.
        ->assertJsonPath('data.vendor_count', 1);

    $vendorIds = collect($response->json('data.top_vendors_by_commission'))->pluck('vendor_id');
    expect($vendorIds)->not->toContain($vendorWithNothing->id);
});

test('a refund and a settlement do not inflate the admin commission total', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $vendor = Vendor::factory()->create();
    afsCompletedOrder($vendor, 1000, 10); // gross 1000, commission 100, net 900

    VendorLedgerEntry::factory()->create([
        'vendor_id' => $vendor->id,
        'type' => VendorLedgerEntry::TYPE_REFUND,
        'direction' => VendorLedgerEntry::DIRECTION_DEBIT,
        'amount' => 200,
    ]);

    app(VendorLedgerService::class)->recordSettlement($vendor, $admin, 300, 'bank_transfer');

    $response = $this->getJson('/api/admin/financials/summary')->assertOk();

    // Commission is snapshotted once at sale time and only ever summed — a
    // refund or a settlement moves net earnings/outstanding, never this.
    $response->assertJsonPath('data.commission_total', 100)
        ->assertJsonPath('data.refunds_total', 200)
        ->assertJsonPath('data.settled_total', 300)
        // net = 1000 - 100 - 200 = 700; outstanding = 700 - 300 = 400
        ->assertJsonPath('data.outstanding_total', 400);
});

test('non-admin roles cannot access the admin financial summary', function () {
    Sanctum::actingAs(User::factory()->create(['type' => User::TYPE_VENDOR]));
    $this->getJson('/api/admin/financials/summary')->assertStatus(403);

    $syndicateUser = User::factory()->syndicate()->create();
    Syndicate::factory()->for($syndicateUser)->create(['status' => Syndicate::STATUS_ACTIVE]);
    Sanctum::actingAs($syndicateUser);
    $this->getJson('/api/admin/financials/summary')->assertStatus(403);

    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/admin/financials/summary')->assertStatus(403);
});

test('syndicate reports never expose vendor-private commission figures', function () {
    $syndicateUser = User::factory()->syndicate()->create();
    $syndicate = Syndicate::factory()->for($syndicateUser)->create([
        'type' => Category::TYPE_AGRICULTURE,
        'status' => Syndicate::STATUS_ACTIVE,
    ]);

    $category = Category::factory()->create(['type' => Category::TYPE_AGRICULTURE, 'commission' => 15]);
    $vendor = Vendor::factory()->create(['business_type' => Category::TYPE_AGRICULTURE]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);
    $order = Order::factory()->for($vendor)->create(['status' => Order::STATUS_COMPLETED]);
    OrderItem::factory()->for($order)->for($product)->create(['line_total' => 1000]);
    app(VendorLedgerService::class)->recordSale($order->fresh());

    Sanctum::actingAs($syndicateUser);

    $reports = $this->getJson('/api/syndicate/reports')->assertOk();
    expect(json_encode($reports->json('data')))->not->toContain('commission');

    $overview = $this->getJson('/api/syndicate/overview')->assertOk();
    expect(json_encode($overview->json('data')))->not->toContain('commission');
});
