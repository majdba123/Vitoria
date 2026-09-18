<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Vendor\SyndicateVendorPdfService;
use Laravel\Sanctum\Sanctum;

/**
 * Vendor Performance Report PDF (stakeholder reporting batch).
 *
 * These assert the report's *data*, not a full-PDF snapshot: the financial
 * totals it renders must match what a completed order actually sold, the
 * category breakdown must carry real quantities, and only vendors within
 * the syndicate's own domain scope may be reported on.
 */
function makeCompletedOrderForReportTest(Vendor $vendor, Product $product, int $quantity, float $unitPrice): Order
{
    $lineTotal = round($unitPrice * $quantity, 2);
    $order = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'subtotal_amount' => $lineTotal,
        'total_amount' => $lineTotal,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'line_total' => $lineTotal,
        'category_id_snapshot' => $product->category_id,
        'category_type' => $product->category->type,
    ]);

    return $order;
}

it('renders a vendor report pdf with financial totals matching the underlying completed order', function () {
    $syndicate = Syndicate::factory()->agriculture()->create();
    $vendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);
    $category = Category::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id, 'price' => 100]);

    makeCompletedOrderForReportTest($vendor, $product, quantity: 3, unitPrice: 100);

    $period = ['key' => 'all', 'from' => null, 'to' => null];
    $result = app(SyndicateVendorPdfService::class)->render($vendor, $syndicate, $period, 'en');

    expect($result['bytes'])->toStartWith('%PDF')
        ->and($result['data']['kpis']['gross_sales'])->toBe(300.0)
        ->and($result['data']['kpis']['units_sold'])->toBe(3)
        ->and($result['data']['vendor']['store_name'])->toBe($vendor->store_name);

    $categoryRow = collect($result['data']['category_performance'])->firstWhere('id', $category->id);
    expect($categoryRow)->not->toBeNull()
        ->and($categoryRow['units_sold'])->toBe(3)
        ->and($categoryRow['sales'])->toBe(300.0);
});

it('serves the syndicate vendor report over http for a vendor within the syndicate domain', function () {
    $syndicate = Syndicate::factory()->agriculture()->create();
    $vendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);

    Sanctum::actingAs($syndicate->user);

    $response = $this->get("/api/syndicate/vendors/{$vendor->id}/report.pdf?range=30_days&locale=en");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('denies a syndicate vendor report for a vendor outside its domain scope', function () {
    $syndicate = Syndicate::factory()->agriculture()->create();
    $outOfScopeVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_VETERINARY]);

    Sanctum::actingAs($syndicate->user);

    $this->get("/api/syndicate/vendors/{$outOfScopeVendor->id}/report.pdf?range=30_days&locale=en")
        ->assertNotFound();
});

it('denies vendor report access to a non-syndicate user', function () {
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    $vendor = Vendor::factory()->create();

    Sanctum::actingAs($customer);

    $this->get("/api/syndicate/vendors/{$vendor->id}/report.pdf?range=30_days&locale=en", ['Accept' => 'application/json'])
        ->assertForbidden();
});
