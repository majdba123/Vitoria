<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Services\Vendor\SyndicateVendorPdfService;
use Laravel\Sanctum\Sanctum;

function finalRoundWorld(): array
{
    $agri = Syndicate::factory()->agriculture()->create();
    $vet = Syndicate::factory()->create(['type' => Category::TYPE_VETERINARY]);
    $agriCategory = Category::factory()->create(['type' => Category::TYPE_AGRICULTURE, 'commission' => 10]);
    $vetCategory = Category::factory()->create(['type' => Category::TYPE_VETERINARY]);
    $vendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);
    $otherVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);
    $agriProduct = Product::factory()->for($vendor)->create(['category_id' => $agriCategory->id]);
    $vetProduct = Product::factory()->for(Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_VETERINARY]))->create(['category_id' => $vetCategory->id]);

    return compact('agri', 'vet', 'agriCategory', 'vetCategory', 'vendor', 'otherVendor', 'agriProduct', 'vetProduct');
}

function finalRoundOrder(Vendor $vendor, Product $product, float $lineTotal = 1000, string $status = Order::STATUS_COMPLETED, ?User $customer = null): Order
{
    $order = Order::factory()->for($vendor)->create([
        'user_id' => ($customer ?? User::factory()->create())->id,
        'status' => $status,
        'subtotal_amount' => $lineTotal,
        'total_amount' => $lineTotal,
        'ship_phone' => '0999111222',
        'ship_street' => 'PRIVATE-STREET-9',
        'ship_recipient_name' => 'PRIVATE RECIPIENT',
        'ship_alternate_phone' => '0966ALT-PHONE',
        'ship_notes' => 'PRIVATE-DELIVERY-NOTE',
        'cancellation_notes' => 'PRIVATE-CANCEL-NOTE',
        'ship_governorate' => 'damascus',
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 4, 'unit_price' => $lineTotal / 4, 'line_total' => $lineTotal,
        'category_id_snapshot' => $product->category_id, 'category_type' => $product->category->type,
    ]);

    return $order;
}

function ledger(Vendor $vendor, ?Order $order, string $type, string $direction, float $amount): void
{
    VendorLedgerEntry::factory()->create(['vendor_id' => $vendor->id, 'order_id' => $order?->id, 'type' => $type, 'direction' => $direction, 'amount' => $amount]);
}

it('hides vendor profit (net earnings) and platform commission from syndicates, exposing only a domain-scoped refunds figure', function () {
    $w = finalRoundWorld();
    $order = finalRoundOrder($w['vendor'], $w['agriProduct'], 1000);
    ledger($w['vendor'], $order, VendorLedgerEntry::TYPE_SALE, VendorLedgerEntry::DIRECTION_CREDIT, 1000);
    ledger($w['vendor'], $order, VendorLedgerEntry::TYPE_COMMISSION, VendorLedgerEntry::DIRECTION_DEBIT, 100);
    ledger($w['vendor'], $order, VendorLedgerEntry::TYPE_REFUND, VendorLedgerEntry::DIRECTION_DEBIT, 50);
    ledger($w['vendor'], null, VendorLedgerEntry::TYPE_SETTLEMENT, VendorLedgerEntry::DIRECTION_DEBIT, 400);
    // A different vendor's ledger must never leak into this vendor's report.
    $foreignOrder = finalRoundOrder($w['otherVendor'], Product::factory()->for($w['otherVendor'])->create(['category_id' => $w['agriCategory']->id]), 9999);
    ledger($w['otherVendor'], $foreignOrder, VendorLedgerEntry::TYPE_SALE, VendorLedgerEntry::DIRECTION_CREDIT, 9999);
    ledger($w['otherVendor'], $foreignOrder, VendorLedgerEntry::TYPE_COMMISSION, VendorLedgerEntry::DIRECTION_DEBIT, 999);

    // Changing the mutable category commission afterwards must not rewrite history.
    $w['agriCategory']->update(['commission' => 90]);

    Sanctum::actingAs($w['agri']->user);
    $response = $this->getJson("/api/syndicate/vendors/{$w['vendor']->id}/analytics/overview?range=all")->assertOk();
    $finance = $response->json('data.finance');

    expect($finance)->not->toHaveKey('net_earnings')->and($finance)->not->toHaveKey('commission');
    expect($finance['refunds'])->toEqual(50)
        ->and($finance['attribution_complete'])->toBeTrue();

    // The admin surface still sees the merchant's real profit - only syndicates are scoped down.
    expect(app(\App\Services\Commerce\VendorLedgerService::class)->summary($w['vendor'])['net_earnings'])->toBe(850.0);
});

it('omits the vendor profit section from the vendor report PDF served to syndicates', function () {
    $w = finalRoundWorld();
    $order = finalRoundOrder($w['vendor'], $w['agriProduct'], 1000);
    ledger($w['vendor'], $order, VendorLedgerEntry::TYPE_SALE, VendorLedgerEntry::DIRECTION_CREDIT, 1000);
    ledger($w['vendor'], $order, VendorLedgerEntry::TYPE_COMMISSION, VendorLedgerEntry::DIRECTION_DEBIT, 100);

    $result = app(SyndicateVendorPdfService::class)->render($w['vendor'], $w['agri'], ['key' => 'all', 'from' => null, 'to' => null], 'ar');
    expect($result['bytes'])->toStartWith('%PDF')->and($result['data']['finance'])->not->toHaveKey('net_earnings');

    $html = view('reports.syndicate-vendor', ['data' => $result['data'], 'syndicate' => $w['agri'], 'isArabic' => true])->render();
    expect($html)->not->toContain('أرباح التاجر (الصافي)');

    // The admin report (no syndicate scope) still renders the merchant's profit section.
    $adminResult = app(SyndicateVendorPdfService::class)->render($w['vendor'], null, ['key' => 'all', 'from' => null, 'to' => null], 'ar');
    $adminHtml = view('reports.syndicate-vendor', ['data' => $adminResult['data'], 'syndicate' => null, 'isArabic' => true])->render();
    expect($adminHtml)->toContain('أرباح التاجر (الصافي)')->toContain('900.00');
});

it('blocks another syndicate from reading an unrelated vendor financials', function () {
    $w = finalRoundWorld();
    Sanctum::actingAs($w['vet']->user);
    $this->getJson("/api/syndicate/vendors/{$w['vendor']->id}/analytics/overview")->assertNotFound();
    $this->get("/api/syndicate/vendors/{$w['vendor']->id}/report.pdf?range=custom&date_from=2020-01-01&date_to=".now()->toDateString().'&locale=ar')->assertNotFound();
});

it('serves syndicate product detail only inside the syndicate domain', function () {
    $w = finalRoundWorld();
    Sanctum::actingAs($w['agri']->user);
    $this->getJson("/api/syndicate/products/{$w['agriProduct']->id}")->assertOk()
        ->assertJsonPath('data.id', $w['agriProduct']->id)
        ->assertJsonPath('data.vendor.store_name', $w['vendor']->store_name)
        ->assertJsonMissingPath('data.rejection_reason')
        ->assertJsonMissingPath('data.category.commission')
        ->assertJsonMissingPath('data.vendor.email')
        ->assertJsonMissingPath('data.vendor.phone');
    $this->getJson("/api/syndicate/products/{$w['vetProduct']->id}")->assertNotFound();
    $this->getJson('/api/syndicate/products/999999')->assertNotFound();
});

it('serves syndicate order detail with line items but no private customer data', function () {
    $w = finalRoundWorld();
    $customer = User::factory()->create(['email' => 'private-customer@example.test', 'phone_number' => '0977000111']);
    $order = finalRoundOrder($w['vendor'], $w['agriProduct'], 1000, customer: $customer);

    Sanctum::actingAs($w['agri']->user);
    $response = $this->getJson("/api/syndicate/orders/{$order->id}")->assertOk()
        ->assertJsonPath('data.order_number', $order->order_number)
        ->assertJsonPath('data.items.0.quantity', 4)
        ->assertJsonPath('data.items.0.line_total', 1000)
        ->assertJsonPath('data.items.0.product_id', $w['agriProduct']->id)
        ->assertJsonPath('data.is_partial', false)
        ->assertJsonPath('data.totals.total', 1000);

    $raw = $response->getContent();
    foreach (['private-customer@example.test', '0977000111', '0999111222', 'PRIVATE-STREET-9', 'PRIVATE RECIPIENT', '0966ALT-PHONE', 'PRIVATE-DELIVERY-NOTE', 'PRIVATE-CANCEL-NOTE'] as $secret) {
        expect($raw)->not->toContain($secret);
    }

    // The list endpoint no longer ships the customer's e-mail either.
    $list = $this->getJson('/api/syndicate/orders')->assertOk()->getContent();
    foreach (['private-customer@example.test', '0977000111', '0966ALT-PHONE', 'PRIVATE-DELIVERY-NOTE'] as $secret) {
        expect($list)->not->toContain($secret);
    }
});

it('hides other-domain lines and order-level totals from a mixed order', function () {
    $w = finalRoundWorld();
    $order = finalRoundOrder($w['vendor'], $w['agriProduct'], 1000);
    OrderItem::factory()->for($order)->for($w['vetProduct'])->create([
        'quantity' => 1, 'unit_price' => 555, 'line_total' => 555, 'product_name' => 'VET-SECRET-ITEM',
        'category_id_snapshot' => $w['vetCategory']->id, 'category_type' => Category::TYPE_VETERINARY,
    ]);

    Sanctum::actingAs($w['agri']->user);
    $response = $this->getJson("/api/syndicate/orders/{$order->id}")->assertOk()
        ->assertJsonPath('data.is_partial', true)
        ->assertJsonPath('data.totals', null)
        ->assertJsonPath('data.scoped_total', 1000);
    expect($response->getContent())->not->toContain('VET-SECRET-ITEM');
});

it('returns 404 for an out-of-scope order id and rejects other roles', function () {
    $w = finalRoundWorld();
    $order = finalRoundOrder($w['vendor'], $w['agriProduct']);

    Sanctum::actingAs($w['vet']->user);
    $this->getJson("/api/syndicate/orders/{$order->id}")->assertNotFound();

    foreach ([User::factory()->create(), User::factory()->admin()->create(), User::factory()->employee()->create(), $w['vendor']->user] as $user) {
        Sanctum::actingAs($user);
        $this->getJson("/api/syndicate/orders/{$order->id}")->assertForbidden();
        $this->getJson("/api/syndicate/products/{$w['agriProduct']->id}")->assertForbidden();
    }

    auth()->guard('sanctum')->forgetUser();
    $this->app['auth']->forgetGuards();
    $this->getJson("/api/syndicate/orders/{$order->id}")->assertUnauthorized();
    $this->getJson("/api/syndicate/products/{$w['agriProduct']->id}")->assertUnauthorized();

    // An unrelated syndicate cannot read the product either.
    Sanctum::actingAs($w['vet']->user);
    $this->getJson("/api/syndicate/products/{$w['agriProduct']->id}")->assertNotFound();
});

it('routes the syndicate product and order pages to detail pages, not reports', function () {
    $w = finalRoundWorld();
    $order = finalRoundOrder($w['vendor'], $w['agriProduct']);
    expect(route('syndicate.products.show', $w['agriProduct']->id, false))->toBe("/syndicate/products/{$w['agriProduct']->id}")
        ->and(route('syndicate.orders.show', $order->id, false))->toBe("/syndicate/orders/{$order->id}");

    $this->actingAs($w['agri']->user)->get("/syndicate/products/{$w['agriProduct']->id}")
        ->assertOk()->assertInertia(fn ($page) => $page->component('Syndicate/Products/Show')->where('productId', $w['agriProduct']->id));
    $this->actingAs($w['agri']->user)->get("/syndicate/orders/{$order->id}")
        ->assertOk()->assertInertia(fn ($page) => $page->component('Syndicate/Orders/Show')->where('orderId', $order->id));
    $this->actingAs($w['vendor']->user)->get("/syndicate/orders/{$order->id}")->assertRedirect();
});

it('has matching ar/en keys for every new syndicate label', function () {
    foreach (['syndicate', 'vendor_analytics'] as $file) {
        $ar = array_keys(require lang_path("ar/{$file}.php"));
        $en = array_keys(require lang_path("en/{$file}.php"));
        expect(array_diff($ar, $en))->toBe([])->and(array_diff($en, $ar))->toBe([]);
    }
    expect(array_keys(trans('reports.vendor.labels', [], 'ar')))->toEqualCanonicalizing(array_keys(trans('reports.vendor.labels', [], 'en')));
});

it('keeps admin order and product routes reachable for admins and closed to syndicates', function () {
    $w = finalRoundWorld();
    $order = finalRoundOrder($w['vendor'], $w['agriProduct']);

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson('/api/admin/orders')->assertOk();

    Sanctum::actingAs($w['agri']->user);
    $this->getJson('/api/admin/orders')->assertForbidden();
    $this->getJson("/api/admin/orders/{$order->id}")->assertForbidden();
});
