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
use App\Services\Syndicate\SyndicateReportService;
use App\Services\Vendor\SyndicateVendorPdfService;
use App\Services\Vendor\VendorAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
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
    expect($response->json('data.vendor'))->not->toHaveKey('owner');
});

test('admin vendor list exposes owner phone only on the authorized admin endpoint', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['phone_number' => '+963944123456']);

    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/admin/vendors')->assertForbidden();

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson('/api/admin/vendors')->assertOk()
        ->assertJsonPath('data.0.user.phone_number', '+963944123456');
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

test('syndicate exports a non-empty scoped pdf with safe headers', function () {
    $data = analyticsFixture();
    $syndicate = Syndicate::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    Sanctum::actingAs($syndicate->user);

    $response = $this->get("/api/syndicate/vendors/{$data['both']->id}/report.pdf?range=30_days&locale=en");

    $response->assertOk()->assertHeader('content-type', 'application/pdf')
        ->assertHeader('x-vetora-report-domain', Category::TYPE_AGRICULTURE);
    expect($response->headers->get('content-disposition'))
        ->toContain("vetora-vendor-report-{$data['both']->id}-")
        ->and(strlen($response->getContent()))->toBeGreaterThan(1000)
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('admin exports Arabic and English Vendor PDFs across all Vendor domains', function () {
    $data = analyticsFixture();
    Sanctum::actingAs(User::factory()->admin()->create());

    foreach (['ar', 'en'] as $locale) {
        $response = $this->get("/api/admin/vendors/{$data['both']->id}/report.pdf?range=30_days&locale={$locale}");
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        expect(strlen($response->getContent()))->toBeGreaterThan(1000)
            ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
    }

    $period = ['key' => '30_days', 'from' => CarbonImmutable::today()->subDays(29)->startOfDay(), 'to' => CarbonImmutable::today()->endOfDay()];
    $report = app(SyndicateVendorPdfService::class)->render($data['both'], null, $period, 'en');
    expect($report['data']['scope']['domain'])->toBeNull()
        ->and($report['data']['kpis']['gross_sales'])->toBe(800.0)
        ->and(collect($report['data']['products'])->pluck('id'))->toContain($data['agProduct']->id, $data['vetProduct']->id);
});

test('general syndicate PDF aggregates only the authenticated domain and selected period', function () {
    $data = analyticsFixture();
    $syndicate = Syndicate::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    Sanctum::actingAs($syndicate->user);

    $response = $this->get('/api/syndicate/reports.pdf?range=30_days&locale=ar');
    $response->assertOk()->assertHeader('content-type', 'application/pdf')
        ->assertHeader('x-vetora-report-domain', Category::TYPE_AGRICULTURE);
    expect(strlen($response->getContent()))->toBeGreaterThan(1000);

    $period = ['key' => '30_days', 'from' => CarbonImmutable::today()->subDays(29)->startOfDay(), 'to' => CarbonImmutable::today()->endOfDay()];
    $report = app(SyndicateReportService::class)->data($syndicate, $period);
    expect($report['kpis']['gross_sales'])->toBe(200.0)
        ->and($report['kpis']['completed_orders'])->toBe(1)
        ->and(collect($report['top_products'])->pluck('id'))->toContain($data['agProduct']->id)->not->toContain($data['vetProduct']->id, $data['otherProduct']->id)
        ->and(collect($report['vendor_performance'])->pluck('id'))->toContain($data['both']->id)->not->toContain($data['other']->id);
});

test('syndicate pdf source data reconciles with dashboard and excludes the other domain', function (string $domain, int $units, float $sales, string $includedProduct, string $excludedProduct) {
    $data = analyticsFixture();
    $syndicate = Syndicate::factory()->create(['type' => $domain]);
    $period = ['key' => '30_days', 'from' => CarbonImmutable::today()->subDays(29)->startOfDay(), 'to' => CarbonImmutable::today()->endOfDay()];

    $dashboard = app(VendorAnalyticsService::class)->overview($data['both'], $period, $domain);
    $report = app(SyndicateVendorPdfService::class)->render($data['both'], $syndicate, $period, 'en');

    expect($report['data']['kpis']['gross_sales'])->toBe($dashboard['kpis']['gross_sales'])
        ->and($report['data']['kpis']['units_sold'])->toBe($units)
        ->and((float) $report['data']['kpis']['gross_sales'])->toBe($sales)
        ->and(collect($report['data']['products'])->pluck('id'))->toContain($data[$includedProduct]->id)->not->toContain($data[$excludedProduct]->id)
        ->and($report['data']['vendor'])->not->toHaveKey('owner')
        ->and($report['data']['orders']->first())->not->toHaveKeys(['customer', 'shipping_address', 'payment']);
})->with([
    'agriculture report' => [Category::TYPE_AGRICULTURE, 2, 200.0, 'agProduct', 'vetProduct'],
    'veterinary report' => [Category::TYPE_VETERINARY, 3, 600.0, 'vetProduct', 'agProduct'],
]);

test('syndicate report period changes totals and custom ranges validate', function () {
    $data = analyticsFixture();
    DB::table('orders')->where('id', $data['agOrder']->id)->update(['created_at' => now()->subDays(45), 'updated_at' => now()->subDays(45)]);
    $syndicate = Syndicate::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    Sanctum::actingAs($syndicate->user);

    $period = ['key' => '30_days', 'from' => CarbonImmutable::today()->subDays(29)->startOfDay(), 'to' => CarbonImmutable::today()->endOfDay()];
    $report = app(SyndicateVendorPdfService::class)->render($data['both'], $syndicate, $period, 'en');
    expect($report['data']['kpis']['gross_sales'])->toBe(0.0)
        ->and($report['data']['kpis']['completed_orders'])->toBe(0);

    $this->get("/api/syndicate/vendors/{$data['both']->id}/report.pdf?range=custom&date_from=2026-08-20&date_to=2026-08-01&locale=en")->assertUnprocessable();
    $this->get("/api/syndicate/vendors/{$data['both']->id}/report.pdf?range=all&locale=en")->assertUnprocessable();
});

test('syndicate report denies unrelated vendors and ignores domain manipulation', function () {
    $data = analyticsFixture();
    $agriculture = Syndicate::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    Sanctum::actingAs($agriculture->user);

    $this->get("/api/syndicate/vendors/{$data['other']->id}/report.pdf?range=30_days&locale=en")->assertNotFound();
    $this->get("/api/syndicate/vendors/{$data['both']->id}/report.pdf?range=30_days&locale=en&syndicate_type=veterinary")
        ->assertOk()->assertHeader('x-vetora-report-domain', Category::TYPE_AGRICULTURE);
});

test('syndicate report supports explicit Arabic and English presentation from identical scoped data', function () {
    $data = analyticsFixture();
    $syndicate = Syndicate::factory()->create(['type' => Category::TYPE_VETERINARY]);
    Sanctum::actingAs($syndicate->user);

    $arabic = $this->get("/api/syndicate/vendors/{$data['both']->id}/report.pdf?range=30_days&locale=ar");
    $english = $this->get("/api/syndicate/vendors/{$data['both']->id}/report.pdf?range=30_days&locale=en");
    $this->get("/api/syndicate/vendors/{$data['both']->id}/report.pdf?range=30_days&locale=fr")->assertUnprocessable();

    $arabic->assertOk()->assertHeader('content-type', 'application/pdf');
    $english->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(strlen($arabic->getContent()))->toBeGreaterThan(1000)
        ->and(strlen($english->getContent()))->toBeGreaterThan(1000);

    $period = ['key' => '30_days', 'from' => CarbonImmutable::today()->subDays(29)->startOfDay(), 'to' => CarbonImmutable::today()->endOfDay()];
    $report = app(VendorAnalyticsService::class)->report($data['both'], $period, Category::TYPE_VETERINARY);
    $arabicHtml = view('reports.syndicate-vendor', ['data' => $report, 'syndicate' => $syndicate, 'isArabic' => true])->render();
    $englishHtml = view('reports.syndicate-vendor', ['data' => $report, 'syndicate' => $syndicate, 'isArabic' => false])->render();

    expect($arabicHtml)->toContain('dir="rtl"', 'تقرير أداء التاجر', 'زراعي وبيطري', 'نشط', 'مكتمل', '600.00 ل.س')
        ->not->toContain('Vendor Performance Report')
        ->and($englishHtml)->toContain('dir="ltr"', 'Vendor Performance Report', '600.00 SYP')
        ->and($report['kpis']['gross_sales'])->toBe(600.0);
});
