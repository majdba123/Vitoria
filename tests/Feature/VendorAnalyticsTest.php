<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Services\Commerce\VendorLedgerService;
use Laravel\Sanctum\Sanctum;

function analyticsCompletedOrder(Vendor $vendor, array $lines): Order
{
    $subtotal = collect($lines)->sum(fn (array $line) => $line['quantity'] * $line['unit_price']);
    $order = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'items_count' => collect($lines)->sum('quantity'),
        'subtotal_amount' => $subtotal,
        'shipping_total' => 0,
        'tax_total' => 0,
        'grand_total' => $subtotal,
        'total_amount' => $subtotal,
    ]);

    foreach ($lines as $line) {
        OrderItem::factory()->for($order)->for($line['product'])->create([
            'category_id_snapshot' => $line['product']->category_id,
            'category_type' => $line['product']->category->type,
            'commission_rate_snapshot' => $line['product']->category->commission,
            'product_name' => $line['product']->name,
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
            'original_unit_price' => $line['unit_price'],
            'line_total' => $line['quantity'] * $line['unit_price'],
        ]);
    }

    app(VendorLedgerService::class)->recordSale($order->fresh());

    return $order;
}

function analyticsFixture(): array
{
    $agriculture = Category::factory()->create(['type' => Category::TYPE_AGRICULTURE, 'commission' => 10]);
    $veterinary = Category::factory()->create(['type' => Category::TYPE_VETERINARY, 'commission' => 20]);
    $both = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_BOTH]);
    $other = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_VETERINARY]);
    $both->categories()->sync([$agriculture->id, $veterinary->id]);
    $other->categories()->sync([$veterinary->id]);
    $agProduct = Product::factory()->for($both)->create(['category_id' => $agriculture->id, 'status' => Product::STATUS_APPROVED]);
    $vetProduct = Product::factory()->for($both)->create(['category_id' => $veterinary->id, 'status' => Product::STATUS_APPROVED]);
    $otherProduct = Product::factory()->for($other)->create(['category_id' => $veterinary->id, 'status' => Product::STATUS_APPROVED]);
    $agOrder = analyticsCompletedOrder($both, [['product' => $agProduct, 'quantity' => 2, 'unit_price' => 100]]);
    $vetOrder = analyticsCompletedOrder($both, [['product' => $vetProduct, 'quantity' => 3, 'unit_price' => 200]]);
    analyticsCompletedOrder($other, [['product' => $otherProduct, 'quantity' => 4, 'unit_price' => 500]]);

    return compact('agriculture', 'veterinary', 'both', 'other', 'agProduct', 'vetProduct', 'otherProduct', 'agOrder', 'vetOrder');
}

test('admin vendor 360 uses the immutable ledger and excludes another vendor', function () {
    $data = analyticsFixture();
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->getJson("/api/admin/vendors/{$data['both']->id}/analytics/overview?range=all")->assertOk();

    $response->assertJsonPath('data.kpis.total_products', 2)
        ->assertJsonPath('data.kpis.completed_orders', 2)
        ->assertJsonPath('data.kpis.units_sold', 5)
        ->assertJsonPath('data.kpis.gross_sales', 800)
        ->assertJsonPath('data.finance.all_time.gross_sales', 800)
        ->assertJsonPath('data.finance.all_time.commission', 140)
        ->assertJsonPath('data.finance.all_time.net_earnings', 660);

    expect(app(VendorLedgerService::class)->summary($data['both']))->toMatchArray($response->json('data.finance.all_time'));
});

test('admin product and order endpoints are vendor isolated', function () {
    $data = analyticsFixture();
    Sanctum::actingAs(User::factory()->admin()->create());

    $products = $this->getJson("/api/admin/vendors/{$data['both']->id}/analytics/products?range=all")->assertOk();
    $orders = $this->getJson("/api/admin/vendors/{$data['both']->id}/analytics/orders?range=all")->assertOk();

    expect(collect($products->json('data'))->pluck('id'))->toContain($data['agProduct']->id, $data['vetProduct']->id)->not->toContain($data['otherProduct']->id)
        ->and(collect($orders->json('data'))->pluck('id'))->toContain($data['agOrder']->id, $data['vetOrder']->id);
});

test('completed sales reconcile one for one with sale ledger snapshots', function () {
    $data = analyticsFixture();
    $sale = VendorLedgerEntry::query()->where('order_id', $data['vetOrder']->id)->where('type', VendorLedgerEntry::TYPE_SALE)->sole();

    expect((float) $sale->amount)->toBe((float) $data['vetOrder']->subtotal_amount)
        ->and($sale->vendor_id)->toBe($data['both']->id)
        ->and($data['vetOrder']->items()->whereHas('product', fn ($query) => $query->where('vendor_id', '!=', $data['both']->id))->exists())->toBeFalse();
});

test('completed refunds change the ledger-backed vendor summary without recomputing commission', function () {
    $data = analyticsFixture();
    $refund = Refund::factory()->for($data['vetOrder'])->create(['amount' => 75, 'status' => Refund::STATUS_COMPLETED, 'completed_at' => now()]);
    app(VendorLedgerService::class)->recordRefund($refund);
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson("/api/admin/vendors/{$data['both']->id}/analytics/overview?range=all")
        ->assertOk()->assertJsonPath('data.finance.all_time.commission', 140)
        ->assertJsonPath('data.finance.all_time.refunds', 75)
        ->assertJsonPath('data.finance.all_time.net_earnings', 585)
        ->assertJsonPath('data.finance.all_time.outstanding', 585);
});

test('syndicates see only their category domain for a both vendor', function (string $type, string $productKey, int $units, float $sales) {
    $data = analyticsFixture();
    $syndicate = Syndicate::factory()->create(['type' => $type]);
    Sanctum::actingAs($syndicate->user);

    $overview = $this->getJson("/api/syndicate/vendors/{$data['both']->id}/analytics/overview?range=all")->assertOk();
    $products = $this->getJson("/api/syndicate/vendors/{$data['both']->id}/analytics/products?range=all")->assertOk();

    $overview->assertJsonPath('data.scope.domain', $type)->assertJsonPath('data.kpis.units_sold', $units);
    expect((float) $overview->json('data.kpis.gross_sales'))->toBe($sales)
        ->and(collect($products->json('data'))->pluck('id'))->toEqual(collect([$data[$productKey]->id]));
})->with([
    'agriculture isolation' => [Category::TYPE_AGRICULTURE, 'agProduct', 2, 200],
    'veterinary isolation' => [Category::TYPE_VETERINARY, 'vetProduct', 3, 600],
]);

test('moving a product later does not reclassify historical syndicate sales', function () {
    $data = analyticsFixture();
    $data['agProduct']->update(['category_id' => $data['veterinary']->id]);

    $agricultureSyndicate = Syndicate::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    Sanctum::actingAs($agricultureSyndicate->user);
    $agricultureOverview = $this->getJson("/api/syndicate/vendors/{$data['both']->id}/analytics/overview?range=all")
        ->assertOk()
        ->assertJsonPath('data.kpis.units_sold', 2)
        ->assertJsonPath('data.kpis.gross_sales', 200);
    expect(collect($agricultureOverview->json('data.category_performance'))->pluck('id'))
        ->toEqual(collect([$data['agriculture']->id]));

    $veterinarySyndicate = Syndicate::factory()->veterinary()->create();
    Sanctum::actingAs($veterinarySyndicate->user);
    $veterinaryOverview = $this->getJson("/api/syndicate/vendors/{$data['both']->id}/analytics/overview?range=all")
        ->assertOk()
        ->assertJsonPath('data.kpis.units_sold', 3)
        ->assertJsonPath('data.kpis.gross_sales', 600);
    expect(collect($veterinaryOverview->json('data.category_performance'))->pluck('id'))
        ->toEqual(collect([$data['veterinary']->id]));
});

test('a mixed domain order never leaks its other domain totals or fabricates finance attribution', function () {
    $data = analyticsFixture();
    $mixed = analyticsCompletedOrder($data['both'], [
        ['product' => $data['agProduct'], 'quantity' => 1, 'unit_price' => 100],
        ['product' => $data['vetProduct'], 'quantity' => 1, 'unit_price' => 300],
    ]);
    $syndicate = Syndicate::factory()->veterinary()->create();
    Sanctum::actingAs($syndicate->user);

    $overview = $this->getJson("/api/syndicate/vendors/{$data['both']->id}/analytics/overview?range=all")->assertOk();
    $orders = $this->getJson("/api/syndicate/vendors/{$data['both']->id}/analytics/orders?range=all")->assertOk();
    $row = collect($orders->json('data'))->firstWhere('id', $mixed->id);

    $overview->assertJsonPath('data.kpis.gross_sales', 900)->assertJsonPath('data.finance.attribution_complete', false)->assertJsonPath('data.finance.net_earnings', null);
    expect($row['scoped_sales'])->toBe(300)->and($row['grand_total'])->toBeNull()->and(collect($row['products'])->pluck('id'))->toEqual(collect([$data['vetProduct']->id]));
});

test('syndicate route manipulation cannot access an unrelated vendor domain', function () {
    $agriculture = Category::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    $vendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);
    $vendor->categories()->sync([$agriculture->id]);
    $syndicate = Syndicate::factory()->veterinary()->create();
    Sanctum::actingAs($syndicate->user);

    $this->getJson("/api/syndicate/vendors/{$vendor->id}/analytics/overview")->assertNotFound();
});

test('normal users and vendors cannot access privileged analytics surfaces', function () {
    $vendor = Vendor::factory()->create();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/admin/vendors/{$vendor->id}/analytics/overview")->assertForbidden();
    $this->getJson("/api/syndicate/vendors/{$vendor->id}/analytics/overview")->assertForbidden();

    Sanctum::actingAs($vendor->user);
    $this->getJson("/api/admin/vendors/{$vendor->id}/analytics/overview")->assertForbidden();
    $this->getJson("/api/syndicate/vendors/{$vendor->id}/analytics/overview")->assertForbidden();
});
