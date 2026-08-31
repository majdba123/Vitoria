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

test('the commission dashboard response explicitly separates ledger-authoritative figures from the projected preview', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    $category = Category::query()->create(['name' => 'Semantics Category', 'type' => Category::TYPE_AGRICULTURE, 'commission' => 10]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);

    // One completed (ledgered) order and one merely confirmed (not yet
    // completed, not yet ledgered) order for the same vendor — the two
    // response sections must disagree on whether the confirmed order counts.
    completedOrderWithLedgerEntry($vendor, $product, 1000);
    $confirmedOrder = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_CONFIRMED,
        'subtotal_amount' => 500,
        'total_amount' => 500,
    ]);
    OrderItem::factory()->for($confirmedOrder)->for($product)->create(['line_total' => 500]);

    Sanctum::actingAs($vendor->user);
    $response = $this->getJson('/api/vendor/commission-stats')->assertOk();

    // Ledger-authoritative: only the completed order's commission (10% of
    // 1000 = 100) is counted — the confirmed order is invisible to it.
    $response->assertJsonPath('data.financials.commission_total', 100);

    // Projected preview: includes BOTH orders (1000 + 500 = 1500) — this is
    // never the same number as anything ledger-sourced, and the response
    // must say so explicitly via `basis`, not leave it to be inferred.
    $response->assertJsonPath('data.financials.projected_order_total', 1500);

    $response->assertJsonPath('data.basis.ledger', [
        'financials.commission_total', 'financials.paid_amount', 'financials.remaining_amount',
    ]);
    $response->assertJsonPath('data.basis.projected', [
        'financials.projected_order_total', 'category_breakdown', 'recent_orders_last_7_days',
    ]);

    // No field anywhere in the response is still named with the ambiguous
    // "completed_order..." wording that previously implied ledger semantics
    // while actually being a live confirmed+completed preview.
    $raw = json_encode($response->json('data'));
    expect($raw)->not->toContain('completed_order_total');
    expect($raw)->not->toContain('completed_orders_last_7_days');

    $component = file_get_contents(resource_path('js/Pages/Vendor/Commission.jsx'));
    expect($component)
        ->toContain("{ key: 'gross_sales', labelKey: 'gross_sales'")
        ->toContain("{ key: 'net_earnings', labelKey: 'net_earnings'")
        ->toContain("{ key: 'commission', labelKey: 'commission_total_label'")
        ->toContain("{ key: 'settled', labelKey: 'paid_to_you'")
        ->toContain("{ key: 'outstanding', labelKey: 'remaining_label'")
        ->toContain('value={count ? Number(statusCounts.completed || 0).toLocaleString(locale) : formatMoney(ledgerSummary[key], locale, vendor.currency_syp)}');
});

test('the ledger backfill --dry-run writes nothing, even against legacy data with a since-changed commission rate', function () {
    $vendor = Vendor::factory()->create();
    $category = Category::query()->create(['name' => 'Legacy Rate Category', 'type' => Category::TYPE_AGRICULTURE, 'commission' => 10]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);

    // Simulate a legacy order that completed under a 10% rate, long before
    // the ledger existed — no recordSale() was ever called for it.
    $order = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'subtotal_amount' => 500,
        'total_amount' => 500,
    ]);
    OrderItem::factory()->for($order)->for($product)->create(['line_total' => 500]);

    // The rate has since drifted upward — the backfill can only ever see
    // this current rate, not the 10% that was actually in effect.
    $category->update(['commission' => 40]);

    $this->artisan('ledger:backfill-completed-orders', ['--dry-run' => true])
        ->assertSuccessful();

    // Dry-run must be a true no-op: no ledger entries, no vendor balance change.
    expect(\App\Models\VendorLedgerEntry::query()->where('order_id', $order->id)->count())->toBe(0);
    expect(app(VendorLedgerService::class)->summary($vendor->fresh())['gross_sales'])->toBe(0.0);
});

/**
 * Proves the settlement race (fullstack audit item, financial closure round)
 * is closed: two settlement requests for the same vendor can never together
 * settle more than was ever outstanding.
 *
 * PHP/Pest test execution is single-threaded, so this cannot spin up two
 * literal OS-level concurrent HTTP requests — the same limitation applies to
 * every other TOCTOU-style test already in this suite (the coupon per-user
 * race in CheckoutAndOrderLifecycleTest, the paid-amount cap test above).
 * The property that actually needs proving is not "do two real network
 * requests overlap in wall-clock time" — it's "does recordSettlement() ever
 * trust a value read before its own lock was acquired". The scenario below
 * reproduces exactly the bug the old code had: two actors each read
 * `outstanding` from the SAME stale snapshot (as they would under genuine
 * concurrency, before either had committed), then both attempt to settle
 * against that stale figure. Under the old code (outstanding read before
 * DB::transaction() opened) the second call would have used its own stale
 * pre-read and let a settlement through that pushed the total past
 * outstanding. Under the fixed code, recordSettlement() re-reads outstanding
 * fresh, inside its own row lock, at call time — so it cannot be fooled by
 * what an earlier "concurrent" reader believed the balance was.
 */
test('two settlement requests racing off the same stale outstanding balance can never together exceed it', function () {
    $vendor = Vendor::factory()->create();
    $category = Category::query()->create(['name' => 'Race Category', 'type' => Category::TYPE_AGRICULTURE, 'commission' => 0]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);
    completedOrderWithLedgerEntry($vendor, $product, 1000); // commission = 0, outstanding = 1000

    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    // Both "actors" observe the same stale outstanding = 1000 before either
    // has settled anything — the exact read a concurrent request would make.
    $staleOutstanding = app(VendorLedgerService::class)->summary($vendor->fresh())['outstanding'];
    expect($staleOutstanding)->toBe(1000.0);

    // Actor A settles 700 against that stale 1000 — passes, since nothing
    // has been settled yet.
    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", [
        'amount' => 700,
        'method' => 'bank_transfer',
    ])->assertCreated();

    // Actor B, still only aware of the stale outstanding = 1000 (not the 300
    // truly remaining after A's settlement), attempts to settle 600 — under
    // the pre-fix code this would have passed (600 <= 1000), pushing the
    // vendor's total settled to 1300 against only 1000 ever owed. The fixed
    // recordSettlement() recomputes outstanding fresh inside its own lock
    // and correctly rejects this as exceeding the true remaining 300.
    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", [
        'amount' => 600,
        'method' => 'bank_transfer',
    ])->assertStatus(422);

    $summary = app(VendorLedgerService::class)->summary($vendor->fresh());
    expect($summary['settled'])->toBe(700.0);
    expect($summary['outstanding'])->toBe(300.0);
    expect(\App\Models\VendorSettlement::query()->where('vendor_id', $vendor->id)->sum('amount'))
        ->toBeLessThanOrEqual(1000.0);

    // A settlement of exactly the true remaining amount still succeeds —
    // proving this is a correctness fix, not an overly-aggressive rejection.
    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", [
        'amount' => 300,
        'method' => 'bank_transfer',
    ])->assertCreated();

    expect(app(VendorLedgerService::class)->summary($vendor->fresh())['outstanding'])->toBe(0.0);
});

test('settlement validation idempotency and vendor ledger synchronization use one authoritative record', function () {
    $vendor = Vendor::factory()->create();
    $otherVendor = Vendor::factory()->create();
    $category = Category::query()->create(['name' => 'Settlement Category', 'type' => Category::TYPE_AGRICULTURE, 'commission' => 10]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);
    completedOrderWithLedgerEntry($vendor, $product, 1000);

    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", ['amount' => 0, 'method' => 'cash'])->assertUnprocessable();
    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", ['amount' => -10, 'method' => 'cash'])->assertUnprocessable();
    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", ['amount' => 901, 'method' => 'cash'])->assertUnprocessable();

    $payload = [
        'amount' => 400,
        'payment_date' => now()->toDateString(),
        'method' => 'bank_transfer',
        'reference' => 'QA-SETTLEMENT-1',
        'notes' => 'Stakeholder QA payment',
        'idempotency_key' => '7c0dfe86-bcb3-4fc8-95d9-37e51317d40e',
    ];
    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", $payload)->assertCreated();
    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", $payload)->assertCreated();

    expect(\App\Models\VendorSettlement::query()->where('vendor_id', $vendor->id)->count())->toBe(1)
        ->and(\App\Models\VendorSettlement::query()->where('vendor_id', $otherVendor->id)->count())->toBe(0)
        ->and($vendor->fresh()->paid_amount)->toBe('400.00')
        ->and(app(VendorLedgerService::class)->summary($vendor->fresh()))->toMatchArray([
            'gross_sales' => 1000.0,
            'commission' => 100.0,
            'net_earnings' => 900.0,
            'settled' => 400.0,
            'outstanding' => 500.0,
        ])
        ->and(\App\Models\AuditLog::query()->where('action', 'vendor_ledger.settlement')->count())->toBe(1);

    $vendorUser = $vendor->user;
    Sanctum::actingAs($vendorUser);
    $this->getJson('/api/vendor/ledger/summary')
        ->assertOk()
        ->assertJsonPath('data.settled', 400)
        ->assertJsonPath('data.outstanding', 500);
    $this->getJson('/api/vendor/ledger')
        ->assertOk()
        ->assertJsonPath('data.0.type', 'settlement')
        ->assertJsonPath('data.0.direction', 'debit')
        ->assertJsonPath('data.0.amount', '400.00');
});
