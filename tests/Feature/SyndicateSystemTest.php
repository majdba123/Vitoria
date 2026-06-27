<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

function syndicateAdmin(): User
{
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    return $admin;
}

function syndicateUser(string $type): User
{
    $user = User::factory()->syndicate()->create([
        'name' => ucfirst($type).' Agent',
        'email' => $type.'-agent@example.com',
        'phone_number' => $type === Category::TYPE_AGRICULTURE ? '0993000001' : '0993000002',
        'password' => 'password',
    ]);

    Syndicate::factory()->for($user)->create([
        'name' => ucfirst($type).' Syndicate',
        'type' => $type,
        'status' => Syndicate::STATUS_ACTIVE,
    ]);

    return $user;
}

function syndicateCategorySet(string $type, string $categoryName): array
{
    $category = Category::query()->create([
        'name' => $categoryName,
        'type' => $type,
    ]);

    $subcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name' => $categoryName.' Subcategory',
    ]);

    $vendor = Vendor::factory()->create([
        'store_name' => $categoryName.' Vendor',
        'business_type' => $type,
        'status' => Vendor::STATUS_ACTIVE,
        'is_active' => true,
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'name' => $categoryName.' Product',
        'subcategory_id' => $subcategory->id,
        'price' => 100,
        'quantity' => 20,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
    ]);

    $order = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'items_count' => 1,
        'subtotal_amount' => 200,
        'total_amount' => 200,
    ]);

    OrderItem::factory()->for($order)->for($product)->create([
        'product_name' => $product->name,
        'original_unit_price' => 100,
        'unit_price' => 100,
        'quantity' => 2,
        'line_total' => 200,
    ]);

    return compact('category', 'subcategory', 'vendor', 'product', 'order');
}

test('admin can create a syndicate agent', function () {
    syndicateAdmin();

    $response = $this->postJson('/api/admin/syndicates', [
        'name' => 'North Agriculture Syndicate',
        'email' => 'north-agriculture@example.com',
        'phone' => '0993999001',
        'password' => 'password',
        'password_confirmation' => 'password',
        'type' => Category::TYPE_AGRICULTURE,
        'status' => Syndicate::STATUS_ACTIVE,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'North Agriculture Syndicate')
        ->assertJsonPath('data.type', Category::TYPE_AGRICULTURE)
        ->assertJsonPath('data.user.type', User::TYPE_SYNDICATE);

    $this->assertDatabaseHas('users', [
        'email' => 'north-agriculture@example.com',
        'phone_number' => '0993999001',
        'type' => User::TYPE_SYNDICATE,
    ]);

    $this->assertDatabaseHas('syndicates', [
        'name' => 'North Agriculture Syndicate',
        'type' => Category::TYPE_AGRICULTURE,
        'status' => Syndicate::STATUS_ACTIVE,
    ]);
});

test('admin can update a syndicate agent', function () {
    syndicateAdmin();

    $syndicate = Syndicate::factory()->agriculture()->create([
        'name' => 'Editable Agriculture Agent',
        'status' => Syndicate::STATUS_ACTIVE,
    ]);

    $response = $this->putJson('/api/admin/syndicates/'.$syndicate->id, [
        'name' => 'Updated Veterinary Agent',
        'email' => 'updated-syndicate@example.com',
        'phone' => '0993999005',
        'type' => Category::TYPE_VETERINARY,
        'status' => Syndicate::STATUS_INACTIVE,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Veterinary Agent')
        ->assertJsonPath('data.type', Category::TYPE_VETERINARY)
        ->assertJsonPath('data.status', Syndicate::STATUS_INACTIVE);

    $this->assertDatabaseHas('syndicates', [
        'id' => $syndicate->id,
        'type' => Category::TYPE_VETERINARY,
        'status' => Syndicate::STATUS_INACTIVE,
    ]);
});

test('syndicate agents cannot access admin syndicate edit api', function () {
    $user = syndicateUser(Category::TYPE_AGRICULTURE);
    $target = Syndicate::factory()->veterinary()->create();

    Sanctum::actingAs($user);

    $this->putJson('/api/admin/syndicates/'.$target->id, [
        'name' => 'Blocked Update',
        'email' => 'blocked@example.com',
        'phone' => '0993999010',
        'type' => Category::TYPE_VETERINARY,
        'status' => Syndicate::STATUS_ACTIVE,
    ])
        ->assertForbidden()
        ->assertJsonPath('message', __('Unauthorized. Admin access required.'));

    $this->assertDatabaseMissing('syndicates', [
        'id' => $target->id,
        'name' => 'Blocked Update',
    ]);
});

test('syndicate creation validates required type status and password confirmation', function () {
    syndicateAdmin();

    $this->postJson('/api/admin/syndicates', [
        'name' => 'Invalid Syndicate',
        'email' => 'invalid-syndicate@example.com',
        'phone' => '0993999020',
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password', 'type', 'status']);
});

test('syndicate login redirects to the syndicate dashboard', function () {
    $user = syndicateUser(Category::TYPE_AGRICULTURE);

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect(route('syndicate.dashboard'));

    $this->postJson('/api/auth/login', [
        'phone_number' => $user->phone_number,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.type', User::TYPE_SYNDICATE)
        ->assertJsonPath('data.user.syndicate.type', Category::TYPE_AGRICULTURE);
});

test('agriculture syndicate sees only agriculture categories vendors products and orders', function () {
    $user = syndicateUser(Category::TYPE_AGRICULTURE);
    $agriculture = syndicateCategorySet(Category::TYPE_AGRICULTURE, 'Syndicate Seeds');
    $veterinary = syndicateCategorySet(Category::TYPE_VETERINARY, 'Syndicate Vaccines');

    Sanctum::actingAs($user);

    $categoryIds = collect($this->getJson('/api/syndicate/categories')->assertOk()->json('data'))->pluck('id');
    $vendorIds = collect($this->getJson('/api/syndicate/vendors')->assertOk()->json('data'))->pluck('id');
    $productIds = collect($this->getJson('/api/syndicate/products')->assertOk()->json('data'))->pluck('id');
    $orderIds = collect($this->getJson('/api/syndicate/orders')->assertOk()->json('data'))->pluck('id');

    expect($categoryIds)->toContain($agriculture['category']->id)->not->toContain($veterinary['category']->id)
        ->and($vendorIds)->toContain($agriculture['vendor']->id)->not->toContain($veterinary['vendor']->id)
        ->and($productIds)->toContain($agriculture['product']->id)->not->toContain($veterinary['product']->id)
        ->and($orderIds)->toContain($agriculture['order']->id)->not->toContain($veterinary['order']->id);
});

test('veterinary syndicate sees only veterinary categories vendors products and orders', function () {
    $user = syndicateUser(Category::TYPE_VETERINARY);
    $agriculture = syndicateCategorySet(Category::TYPE_AGRICULTURE, 'Vet Test Seeds');
    $veterinary = syndicateCategorySet(Category::TYPE_VETERINARY, 'Vet Test Vaccines');

    Sanctum::actingAs($user);

    $categoryIds = collect($this->getJson('/api/syndicate/categories')->assertOk()->json('data'))->pluck('id');
    $vendorIds = collect($this->getJson('/api/syndicate/vendors')->assertOk()->json('data'))->pluck('id');
    $productIds = collect($this->getJson('/api/syndicate/products')->assertOk()->json('data'))->pluck('id');
    $orderIds = collect($this->getJson('/api/syndicate/orders')->assertOk()->json('data'))->pluck('id');

    expect($categoryIds)->toContain($veterinary['category']->id)->not->toContain($agriculture['category']->id)
        ->and($vendorIds)->toContain($veterinary['vendor']->id)->not->toContain($agriculture['vendor']->id)
        ->and($productIds)->toContain($veterinary['product']->id)->not->toContain($agriculture['product']->id)
        ->and($orderIds)->toContain($veterinary['order']->id)->not->toContain($agriculture['order']->id);
});

test('syndicate dashboard statistics are filtered by assigned category type', function () {
    Cache::flush();
    $user = syndicateUser(Category::TYPE_AGRICULTURE);
    syndicateCategorySet(Category::TYPE_AGRICULTURE, 'Stats Seeds');
    syndicateCategorySet(Category::TYPE_VETERINARY, 'Stats Vaccines');

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/syndicate/overview');

    $response->assertOk()
        ->assertJsonPath('data.total_categories', 1)
        ->assertJsonPath('data.total_merchants', 1)
        ->assertJsonPath('data.total_products', 1)
        ->assertJsonPath('data.order_stats.completed_orders', 1)
        ->assertJsonPath('data.sales_stats.completed_sales', 200);

    expect($response->json('data.product_stats.products_by_category.0.type'))->toBe(Category::TYPE_AGRICULTURE)
        ->and($response->json('data.category_stats.top_by_products.0.type'))->toBe(Category::TYPE_AGRICULTURE)
        ->and($response->json('data.merchant_stats.total'))->toBe(1)
        ->and($response->json('data.podcast_stats.total'))->toBe(0);
});

test('syndicate podcasts endpoint is type protected and ready for future podcast module', function () {
    $user = syndicateUser(Category::TYPE_VETERINARY);

    Sanctum::actingAs($user);

    $this->getJson('/api/syndicate/podcasts')
        ->assertOk()
        ->assertJsonPath('data.data', [])
        ->assertJsonPath('data.meta.total', 0);
});

test('admin can filter syndicate agents by type and status', function () {
    syndicateAdmin();

    Syndicate::factory()->agriculture()->create([
        'name' => 'Filter Agriculture Agent',
        'status' => Syndicate::STATUS_ACTIVE,
    ]);
    Syndicate::factory()->veterinary()->inactive()->create([
        'name' => 'Filter Veterinary Agent',
    ]);

    $this->getJson('/api/admin/syndicates?type='.Category::TYPE_AGRICULTURE.'&status='.Syndicate::STATUS_ACTIVE)
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'Filter Agriculture Agent')
        ->assertJsonPath('data.0.type', Category::TYPE_AGRICULTURE);
});

test('syndicate list pagination has a safe maximum per page', function () {
    syndicateAdmin();

    Syndicate::factory()->count(60)->agriculture()->create();

    $this->getJson('/api/admin/syndicates?per_page=500')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 50);
});

test('syndicate and admin pages render with Vetora Arabic branding', function () {
    $admin = syndicateAdmin();

    $this->actingAs($admin)
        ->get('/admin/syndicates')
        ->assertOk()
        ->assertSee('Vetora')
        ->assertSee('إدارة النقابات');

    expect(config('app.name'))->toBe('Vetora')
        ->and(config('app.locale'))->toBe('ar')
        ->and(__('Vetora'))->toBe('Vetora');
});

test('admin syndicate details page renders cleanly', function () {
    $admin = syndicateAdmin();
    $syndicate = Syndicate::factory()->agriculture()->create([
        'name' => 'Details Agriculture Syndicate',
    ]);

    $this->actingAs($admin)
        ->get('/admin/syndicates/'.$syndicate->id)
        ->assertOk()
        ->assertSee('Syndicate Details')
        ->assertSee('Loading syndicate details...')
        ->assertSee('const syndicateId = "'.$syndicate->id.'";', false);
});

test('syndicate dashboard page renders the professional workspace shell', function () {
    $user = syndicateUser(Category::TYPE_AGRICULTURE);

    $this->actingAs($user)
        ->get(route('syndicate.dashboard'))
        ->assertOk()
        ->assertSee('مساحة عمل النقابة')
        ->assertSee('نظرة عامة')
        ->assertSee('الأعلى أداء');
});

test('admin dashboard overview includes syndicate statistics', function () {
    Cache::forget('admin_dashboard_overview');
    syndicateAdmin();

    Syndicate::factory()->agriculture()->create(['status' => Syndicate::STATUS_ACTIVE]);
    Syndicate::factory()->veterinary()->inactive()->create();

    $response = $this->getJson('/api/admin/dashboard/overview');

    $response->assertOk()
        ->assertJsonPath('data.total_syndicates', 2)
        ->assertJsonPath('data.active_syndicates', 1)
        ->assertJsonPath('data.inactive_syndicates', 1);

    $byType = collect($response->json('data.syndicates_by_type'));

    expect($byType->firstWhere('type', Category::TYPE_AGRICULTURE)['total'])->toBe(1)
        ->and($byType->firstWhere('type', Category::TYPE_VETERINARY)['total'])->toBe(1)
        ->and($response->json('data.recent_syndicate_agents'))->toHaveCount(2);
});
