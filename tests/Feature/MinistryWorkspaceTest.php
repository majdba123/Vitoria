<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ImportShipment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Syndicate\SyndicateReportService;
use App\Support\DomainScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function ministryUser(): User
{
    return User::factory()->create(['type' => User::TYPE_MINISTRY]);
}

/**
 * One agriculture line, one veterinary line, and one order carrying both, so
 * combined totals can be checked against per-sector totals.
 *
 * @return array<string, mixed>
 */
function ministryWorld(): array
{
    $agriSyndicate = Syndicate::factory()->agriculture()->create();
    $vetSyndicate = Syndicate::factory()->create(['type' => Category::TYPE_VETERINARY]);
    $agriCategory = Category::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    $vetCategory = Category::factory()->create(['type' => Category::TYPE_VETERINARY]);

    $agriVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);
    $vetVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_VETERINARY]);
    $bothVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_BOTH]);

    $agriProduct = Product::factory()->for($agriVendor)->create(['category_id' => $agriCategory->id]);
    $vetProduct = Product::factory()->for($vetVendor)->create(['category_id' => $vetCategory->id]);
    $bothAgriProduct = Product::factory()->for($bothVendor)->create(['category_id' => $agriCategory->id]);
    $bothVetProduct = Product::factory()->for($bothVendor)->create(['category_id' => $vetCategory->id]);

    $agriOrder = ministryOrder($agriVendor, [$agriProduct]);
    $vetOrder = ministryOrder($vetVendor, [$vetProduct]);
    $mixedOrder = ministryOrder($bothVendor, [$bothAgriProduct, $bothVetProduct]);

    return compact('agriSyndicate', 'vetSyndicate', 'agriVendor', 'vetVendor', 'bothVendor', 'agriProduct', 'vetProduct', 'agriOrder', 'vetOrder', 'mixedOrder');
}

/**
 * @param  list<Product>  $products
 */
function ministryOrder(Vendor $vendor, array $products, string $status = Order::STATUS_COMPLETED): Order
{
    $order = Order::factory()->for($vendor)->create(['status' => $status, 'subtotal_amount' => 100 * count($products), 'total_amount' => 100 * count($products)]);

    foreach ($products as $product) {
        OrderItem::factory()->for($order)->for($product)->create([
            'quantity' => 1, 'unit_price' => 100, 'line_total' => 100,
            'category_id_snapshot' => $product->category_id, 'category_type' => $product->category->type,
        ]);
    }

    return $order;
}

it('redirects a Ministry account to the Ministry dashboard after login', function () {
    $password = Str::random(40);
    $user = User::factory()->create(['type' => User::TYPE_MINISTRY, 'phone_number' => '0935111222', 'password' => $password]);

    $this->postJson('/api/auth/login', ['phone_number' => '0935111222', 'password' => $password])
        ->assertOk()
        ->assertJsonPath('data.redirect_url', route('ministry.dashboard'));

    expect($user->redirectAfterLogin()->getTargetUrl())->toBe(route('ministry.dashboard'));
});

it('renders the Ministry pages for the Ministry and refuses every other role', function () {
    $ministry = ministryUser();

    $this->actingAs($ministry)->get('/ministry/dashboard')->assertOk()
        ->assertInertia(fn ($page) => $page->component('Syndicate/Dashboard')->where('workspace', 'ministry')->where('sector', 'combined'));
    $this->actingAs($ministry)->get('/ministry/vendors?sector=veterinary')->assertOk()
        ->assertInertia(fn ($page) => $page->where('sector', 'veterinary'));
    $this->actingAs($ministry)->get('/ministry/orders?sector=bogus')->assertOk()
        ->assertInertia(fn ($page) => $page->where('sector', 'combined'));
    $this->actingAs($ministry)->get('/ministry/shipments')->assertOk()
        ->assertInertia(fn ($page) => $page->component('Shipments/Index')->where('workspace', 'ministry'));

    $syndicate = Syndicate::factory()->agriculture()->create();
    foreach ([User::factory()->create(), User::factory()->create(['type' => User::TYPE_ADMIN]), $syndicate->user, User::factory()->create(['type' => User::TYPE_EMPLOYEE])] as $other) {
        $this->actingAs($other)->getJson('/api/ministry/overview')->assertForbidden();
        $this->actingAs($other)->getJson('/api/ministry/reports.pdf?range=30_days&locale=en')->assertForbidden();
        $this->actingAs($other)->get('/ministry/dashboard')->assertRedirect();
    }
});

it('requires authentication for the Ministry API', function () {
    $this->getJson('/api/ministry/overview')->assertUnauthorized();
});

it('keeps the Ministry out of the admin and syndicate APIs', function () {
    Sanctum::actingAs(ministryUser());

    $this->getJson('/api/admin/users')->assertForbidden();
    $this->getJson('/api/syndicate/overview')->assertForbidden();
});

it('registers no write endpoints and serves only the scoped Ministry PDF report', function () {
    $world = ministryWorld();
    Sanctum::actingAs(ministryUser());

    $this->postJson('/api/ministry/import-shipments', [])->assertStatus(405);
    $this->deleteJson('/api/ministry/orders/'.$world['agriOrder']->id)->assertStatus(405);
    $response = $this->get('/api/ministry/reports.pdf?range=30_days&locale=en&sector=combined');
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Vetora-Report-Domain', 'combined');
    expect($response->getContent())->toStartWith('%PDF-');
    $audit = AuditLog::query()->where('action', 'export.ministry_report')->firstOrFail();
    expect($audit->actor_user_id)->toBe(User::query()->where('type', User::TYPE_MINISTRY)->firstOrFail()->id)
        ->and($audit->new_values)->toMatchArray(['range' => '30_days', 'locale' => 'en', 'sector' => 'combined', 'format' => 'pdf']);
    $this->get('/api/ministry/reports.pdf?range=30_days&locale=ar&sector=agriculture')->assertOk()->assertHeader('X-Vetora-Report-Domain', 'agriculture');
    $this->get('/api/ministry/reports.pdf?range=30_days&locale=en&sector=veterinary')->assertOk()->assertHeader('X-Vetora-Report-Domain', 'veterinary');
    $this->getJson('/api/ministry/reports.pdf?range=30_days&locale=en&sector=all')->assertUnprocessable()->assertJsonValidationErrors('sector');
    $this->getJson("/api/ministry/vendors/{$world['agriVendor']->id}/report.pdf")->assertNotFound();
    $this->get('/ministry/shipments/create')->assertNotFound();
});

it('rejects an unknown sector instead of widening the scope', function (mixed $sector) {
    Sanctum::actingAs(ministryUser());

    $this->getJson('/api/ministry/overview?'.http_build_query(['sector' => $sector]))->assertUnprocessable()->assertJsonValidationErrors('sector');
})->with([['all'], ['both'], [['agriculture']]]);

it('counts a mixed-domain order and a both-sector vendor once in the combined view', function () {
    ministryWorld();
    Sanctum::actingAs(ministryUser());

    $combined = $this->getJson('/api/ministry/overview')->assertOk()->json('data');
    $agriculture = $this->getJson('/api/ministry/overview?sector=agriculture')->assertOk()->json('data');
    $veterinary = $this->getJson('/api/ministry/overview?sector=veterinary')->assertOk()->json('data');

    expect($combined['order_stats']['total_orders'])->toBe(3)
        ->and($agriculture['order_stats']['total_orders'])->toBe(2)
        ->and($veterinary['order_stats']['total_orders'])->toBe(2)
        ->and($combined['total_merchants'])->toBe(3)
        ->and($agriculture['total_merchants'])->toBe(2)
        ->and($veterinary['total_merchants'])->toBe(2)
        ->and($combined['syndicate']['id'])->toBeNull()
        ->and($combined['syndicate']['type'])->toBe('combined');

    $this->getJson('/api/ministry/orders')->assertOk()->assertJsonPath('meta.total', 3);
});

it('matches the syndicate figures exactly when a single sector is selected', function () {
    $world = ministryWorld();

    Sanctum::actingAs($world['agriSyndicate']->user);
    $syndicate = $this->getJson('/api/syndicate/overview')->assertOk()->json('data');

    Sanctum::actingAs(ministryUser());
    $ministry = $this->getJson('/api/ministry/overview?sector=agriculture')->assertOk()->json('data');

    foreach (['total_merchants', 'total_products', 'total_categories', 'order_stats', 'merchant_stats'] as $key) {
        expect($ministry[$key])->toEqual($syndicate[$key]);
    }
});

it('aggregates Ministry report totals once across combined sectors and by selected sector', function () {
    ministryWorld();
    $period = ['key' => 'all', 'from' => null, 'to' => null];
    $reports = app(SyndicateReportService::class);
    $combined = $reports->data(DomainScope::forSector('combined'), $period);
    $agriculture = $reports->data(DomainScope::forSector('agriculture'), $period);
    $veterinary = $reports->data(DomainScope::forSector('veterinary'), $period);

    expect($combined['kpis']['completed_orders'])->toBe(3)
        ->and($combined['kpis']['units_sold'])->toBe(4)
        ->and($combined['kpis']['gross_sales'])->toBe(400.0)
        ->and($agriculture['kpis']['completed_orders'])->toBe(2)
        ->and($agriculture['kpis']['gross_sales'])->toBe(200.0)
        ->and($veterinary['kpis']['completed_orders'])->toBe(2)
        ->and($veterinary['kpis']['gross_sales'])->toBe(200.0);
});

it('follows the order line snapshot, not the current category, when filtering by sector', function () {
    $world = ministryWorld();
    // Recategorising the product later must not move an already-sold line.
    $world['agriProduct']->update(['category_id' => Category::factory()->create(['type' => Category::TYPE_VETERINARY])->id]);
    Sanctum::actingAs(ministryUser());

    $ids = collect($this->getJson('/api/ministry/orders?sector=agriculture')->assertOk()->json('data'))->pluck('id');

    expect($ids)->toContain($world['agriOrder']->id)->not->toContain($world['vetOrder']->id);
});

it('scopes product and order detail to the selected sector', function () {
    $world = ministryWorld();
    Sanctum::actingAs(ministryUser());

    $this->getJson("/api/ministry/products/{$world['vetProduct']->id}")->assertOk();
    $this->getJson("/api/ministry/products/{$world['vetProduct']->id}?sector=agriculture")->assertNotFound();
    $this->getJson("/api/ministry/orders/{$world['vetOrder']->id}?sector=veterinary")->assertOk();
    $this->getJson("/api/ministry/orders/{$world['vetOrder']->id}?sector=agriculture")->assertNotFound();
});

it('never exposes the vendor ledger or profit to the Ministry', function () {
    $world = ministryWorld();
    Sanctum::actingAs(ministryUser());

    $finance = $this->getJson("/api/ministry/vendors/{$world['bothVendor']->id}/analytics/overview?range=all")->assertOk()->json('data.finance');

    expect($finance)->not->toHaveKey('all_time')->not->toHaveKey('net_earnings')->not->toHaveKey('commission');
    $this->getJson("/api/ministry/vendors/{$world['vetVendor']->id}/analytics/overview?sector=agriculture")->assertNotFound();
    $this->getJson("/api/ministry/vendors/{$world['agriVendor']->id}/ledger")->assertNotFound();
});

it('shows the Ministry only syndicate shipments, filtered by sector', function () {
    $agri = Syndicate::factory()->agriculture()->create();
    $vet = Syndicate::factory()->create(['type' => Category::TYPE_VETERINARY]);
    $agriShipment = ImportShipment::factory()->create(['syndicate_id' => $agri->id]);
    $vetShipment = ImportShipment::factory()->create(['syndicate_id' => $vet->id]);
    $orphan = ImportShipment::factory()->create(['syndicate_id' => null]);
    Sanctum::actingAs(ministryUser());

    $all = collect($this->getJson('/api/ministry/import-shipments')->assertOk()->json('data'))->pluck('id');
    $vetOnly = collect($this->getJson('/api/ministry/import-shipments?sector=veterinary')->assertOk()->json('data'))->pluck('id');

    expect($all)->toContain($agriShipment->id)->toContain($vetShipment->id)->not->toContain($orphan->id)
        ->and($vetOnly->all())->toBe([$vetShipment->id]);
    $this->getJson("/api/ministry/import-shipments/{$orphan->id}")->assertNotFound();
    $this->putJson("/api/ministry/import-shipments/{$agriShipment->id}", [])->assertStatus(405);
});

it('blocks changing an account to or from the Ministry type through user management', function () {
    $ministry = ministryUser();
    $customer = User::factory()->create();
    Sanctum::actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));

    $this->putJson("/api/admin/users/{$customer->id}", ['type' => User::TYPE_MINISTRY])->assertUnprocessable()->assertJsonValidationErrors('type');
    $this->putJson("/api/admin/users/{$ministry->id}", ['type' => User::TYPE_USER])->assertUnprocessable()->assertJsonValidationErrors('type');
    $this->putJson("/api/admin/users/{$ministry->id}", ['type' => User::TYPE_MINISTRY, 'name' => 'Renamed'])->assertOk();

    expect($customer->fresh()->type)->toBe(User::TYPE_USER)
        ->and($ministry->fresh()->type)->toBe(User::TYPE_MINISTRY);
});

it('provisions a Ministry account idempotently with a generated password and an audit record', function () {
    $this->artisan('ministry:provision', ['--phone' => '0935222333', '--name' => 'Ministry Oversight', '--national-id' => 'MIN-0001', '--generate-password' => true, '--no-interaction' => true])
        ->assertSuccessful();

    $user = User::query()->where('phone_number', '0935222333')->firstOrFail();
    expect($user->type)->toBe(User::TYPE_MINISTRY)->and($user->password)->not->toBeEmpty();
    $hash = $user->password;

    $this->artisan('ministry:provision', ['--phone' => '0935222333', '--name' => 'Ministry Oversight Office', '--no-interaction' => true])
        ->assertSuccessful();

    $user->refresh();
    expect(User::query()->where('phone_number', '0935222333')->count())->toBe(1)
        ->and($user->name)->toBe('Ministry Oversight Office')
        ->and($user->password)->toBe($hash);

    $logs = AuditLog::query()->where('entity_type', 'User')->where('entity_id', $user->id)->orderBy('id')->get();
    expect($logs->pluck('action')->all())->toBe(['ministry_account.provisioned', 'ministry_account.updated'])
        ->and(json_encode($logs->pluck('new_values')))->not->toContain($hash);
});

it('refuses to provision without a password source or over a non-Ministry account', function () {
    $this->artisan('ministry:provision', ['--phone' => '0935444555', '--name' => 'Ministry', '--national-id' => 'MIN-0002', '--no-interaction' => true])->assertFailed();
    expect(User::query()->where('phone_number', '0935444555')->exists())->toBeFalse();

    $customer = User::factory()->create(['phone_number' => '0935666777']);
    $this->artisan('ministry:provision', ['--phone' => '0935666777', '--name' => 'Ministry', '--national-id' => 'MIN-0003', '--generate-password' => true, '--no-interaction' => true])->assertFailed();
    expect($customer->fresh()->type)->toBe(User::TYPE_USER);
});

it('records Ministry actions with the ministry actor type', function () {
    $ministry = ministryUser();

    $log = app(\App\Services\AuditLogService::class)->record($ministry, 'test.action', 'User', $ministry->id);

    expect($log->actor_type)->toBe('ministry');
    expect(Hash::needsRehash($ministry->password))->toBeFalse();
});
