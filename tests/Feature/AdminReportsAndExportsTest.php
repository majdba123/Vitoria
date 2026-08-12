<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use Laravel\Sanctum\Sanctum;

/**
 * Admin reports and exports (spec §36, §37).
 *
 * The load-bearing properties: the sales report's revenue total actually
 * matches the sum of non-cancelled orders, cancelled orders are excluded,
 * and the CSV exports stream real rows with the expected header shape.
 */
it('excludes cancelled orders from the sales report total', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $vendor = Vendor::factory()->create();

    Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_COMPLETED, 'grand_total' => 100]);
    Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_CONFIRMED, 'grand_total' => 50]);
    Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_CANCELLED, 'grand_total' => 9999]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/reports/sales')->assertOk();

    expect($response->json('data.total_orders'))->toBe(2)
        ->and((float) $response->json('data.total_revenue'))->toBe(150.0);
});

it('reports vendor revenue and ledger totals separately per vendor', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $vendorA = Vendor::factory()->create(['store_name' => 'Vendor A']);
    $vendorB = Vendor::factory()->create(['store_name' => 'Vendor B']);

    Order::factory()->create(['vendor_id' => $vendorA->id, 'status' => Order::STATUS_COMPLETED, 'grand_total' => 200]);
    Order::factory()->create(['vendor_id' => $vendorB->id, 'status' => Order::STATUS_COMPLETED, 'grand_total' => 80]);

    VendorLedgerEntry::query()->create([
        'vendor_id' => $vendorA->id,
        'type' => 'adjustment',
        'direction' => 'debit',
        'amount' => 30,
        'description' => 'Fee',
        'created_by_user_id' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/reports/vendors')->assertOk();
    $rows = collect($response->json('data.vendors'))->keyBy('vendor_id');

    expect((float) $rows[$vendorA->id]['revenue'])->toBe(200.0)
        ->and((float) $rows[$vendorA->id]['net_ledger_amount'])->toBe(-30.0)
        ->and((float) $rows[$vendorB->id]['revenue'])->toBe(80.0)
        ->and((float) $rows[$vendorB->id]['net_ledger_amount'])->toBe(0.0);
});

it('ranks products by revenue in the product performance report', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $vendor = Vendor::factory()->create();
    $order = Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_COMPLETED, 'grand_total' => 300]);

    $bestSeller = Product::factory()->for($vendor)->create(['name' => 'Best Seller']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $bestSeller->id,
        'product_name' => 'Best Seller',
        'quantity' => 5,
        'line_total' => 250,
    ]);

    $other = Product::factory()->for($vendor)->create(['name' => 'Other']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $other->id,
        'product_name' => 'Other',
        'quantity' => 1,
        'line_total' => 50,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/reports/products')->assertOk();
    $products = $response->json('data.products');

    expect($products[0]['product_name'])->toBe('Best Seller')
        ->and($products[0]['quantity_sold'])->toBe(5);
});

it('exports orders as a CSV with the expected header row', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $vendor = Vendor::factory()->create(['store_name' => 'CSV Vendor']);
    Order::factory()->create(['vendor_id' => $vendor->id, 'status' => Order::STATUS_COMPLETED, 'grand_total' => 42]);

    Sanctum::actingAs($admin);

    $response = $this->get('/api/admin/exports/orders')->assertOk();
    $content = $response->streamedContent();

    expect($content)->toContain('Order Number')
        ->and($content)->toContain('CSV Vendor')
        ->and($response->headers->get('Content-Type'))->toContain('text/csv');
});

it('exports vendors as a CSV filtered by status', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Vendor::factory()->create(['store_name' => 'Active Vendor', 'status' => Vendor::STATUS_ACTIVE]);
    Vendor::factory()->create(['store_name' => 'Pending Vendor', 'status' => Vendor::STATUS_PENDING]);

    Sanctum::actingAs($admin);

    $response = $this->get('/api/admin/exports/vendors?status='.Vendor::STATUS_ACTIVE)->assertOk();
    $content = $response->streamedContent();

    expect($content)->toContain('Active Vendor')
        ->and($content)->not->toContain('Pending Vendor');
});

it('rejects report and export access from a non-admin user', function () {
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    Sanctum::actingAs($customer);

    $this->getJson('/api/admin/reports/sales')->assertForbidden();
    $this->get('/api/admin/exports/orders', ['Accept' => 'application/json'])->assertForbidden();
});
