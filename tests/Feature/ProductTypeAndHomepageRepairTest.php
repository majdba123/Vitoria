<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function repairAdmin(): User
{
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    return $admin;
}

function repairCategorySet(string $type, string $name): array
{
    $category = Category::query()->create([
        'name' => $name,
        'type' => $type,
    ]);

    $subcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name' => $name.' فرعي',
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => $type,
        'status' => Vendor::STATUS_ACTIVE,
        'is_active' => true,
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'subcategory_id' => $subcategory->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 10,
        'image' => null,
        'icon' => null,
    ]);

    return compact('category', 'subcategory', 'vendor', 'product');
}

test('homepage product api is safe with missing optional image and category data', function () {
    $vendor = Vendor::factory()->create([
        'status' => Vendor::STATUS_ACTIVE,
        'is_active' => true,
    ]);

    Product::factory()->for($vendor)->create([
        'subcategory_id' => null,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 5,
        'image' => null,
        'icon' => null,
    ]);

    $response = $this->getJson('/api/products?per_page=5')->assertOk();

    $response->assertJsonPath('data.0.category_id', null)
        ->assertJsonPath('data.0.category', null);

    expect($response->json('data.0.first_photo_url'))->toBeNull()
        ->and($response->json('data.0.fallback_photo_url'))->toContain('product-placeholder.svg');
});

test('normal website users can choose product type from the homepage', function () {
    $user = User::factory()->create([
        'type' => User::TYPE_USER,
        'preferred_product_type' => null,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('preferred_product_type='.Category::TYPE_AGRICULTURE, false)
        ->assertSee('preferred_product_type='.Category::TYPE_VETERINARY, false)
        ->assertSee('redirect_to=home', false);

    $this->actingAs($user)
        ->post(route('product-type.store'), [
            'preferred_product_type' => Category::TYPE_AGRICULTURE,
            'redirect_to' => 'home',
        ])
        ->assertRedirect(route('home', ['type' => Category::TYPE_AGRICULTURE]))
        ->assertCookie('preferred_product_type', Category::TYPE_AGRICULTURE);

    expect($user->refresh()->preferred_product_type)->toBe(Category::TYPE_AGRICULTURE)
        ->and(session('preferred_product_type'))->toBe(Category::TYPE_AGRICULTURE);
});

test('guests can choose product type from the homepage and still use the dedicated selection page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('preferred_product_type='.Category::TYPE_AGRICULTURE, false)
        ->assertSee('preferred_product_type='.Category::TYPE_VETERINARY, false)
        ->assertSee('redirect_to=home', false);

    $this->get(route('product-type.select'))
        ->assertOk()
        ->assertSee('preferred_product_type='.Category::TYPE_AGRICULTURE, false)
        ->assertSee('preferred_product_type='.Category::TYPE_VETERINARY, false)
        ->assertSee('redirect_to=categories', false);

    $this->post(route('product-type.store'), [
        'preferred_product_type' => Category::TYPE_VETERINARY,
    ])
        ->assertRedirect(route('categories.index', ['type' => Category::TYPE_VETERINARY]))
        ->assertCookie('preferred_product_type', Category::TYPE_VETERINARY);

    $this->withCookie('preferred_product_type', Category::TYPE_VETERINARY)
        ->get(route('home'))
        ->assertOk();
});

test('homepage product type form redirects back home with selected type', function () {
    $this->post(route('product-type.store'), [
        'preferred_product_type' => Category::TYPE_AGRICULTURE,
        'redirect_to' => 'home',
    ])
        ->assertRedirect(route('home', ['type' => Category::TYPE_AGRICULTURE]))
        ->assertCookie('preferred_product_type', Category::TYPE_AGRICULTURE);

    $this->withCookie('preferred_product_type', Category::TYPE_AGRICULTURE)
        ->get(route('home', ['type' => Category::TYPE_AGRICULTURE]))
        ->assertOk()
        ->assertSee('preferred_product_type='.Category::TYPE_AGRICULTURE, false)
        ->assertSee('preferred_product_type='.Category::TYPE_VETERINARY, false)
        ->assertSee('redirect_to=home', false);
});

test('guests can choose product type through the dedicated selection links without posting', function () {
    $this->get(route('product-type.select', [
        'preferred_product_type' => Category::TYPE_VETERINARY,
        'redirect_to' => 'categories',
    ]))
        ->assertRedirect(route('categories.index', ['type' => Category::TYPE_VETERINARY]))
        ->assertCookie('preferred_product_type', Category::TYPE_VETERINARY);

    $this->withCookie('preferred_product_type', Category::TYPE_VETERINARY)
        ->get(route('home'))
        ->assertOk();
});

test('homepage and product type selection page render the shared themed cards', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('surface-card', false)
        ->assertSee('workspace-hero', false);

    $this->get(route('product-type.select'))
        ->assertOk()
        ->assertSee('surface-card', false)
        ->assertSee('workspace-hero', false);
});

test('products catalog page renders shared filter selects', function () {
    $this->withCookie('preferred_product_type', Category::TYPE_AGRICULTURE)
        ->get(route('products.index', ['type' => Category::TYPE_AGRICULTURE]))
        ->assertOk()
        ->assertSee('form-select', false)
        ->assertSee('btn-apply', false)
        ->assertSee('btn-clear', false);
});

test('public products api allows explicit all types filter', function () {
    $agriculture = repairCategorySet(Category::TYPE_AGRICULTURE, 'Agriculture Filter Test');
    $veterinary = repairCategorySet(Category::TYPE_VETERINARY, 'Veterinary Filter Test');

    $user = User::factory()->create([
        'type' => User::TYPE_USER,
        'preferred_product_type' => Category::TYPE_VETERINARY,
    ]);

    $this->actingAs($user);

    $productIds = collect(
        $this->getJson('/api/products?per_page=100&category_type=')
            ->assertOk()
            ->json('data')
    )->pluck('id');

    expect($productIds)->toContain($agriculture['product']->id)
        ->and($productIds)->toContain($veterinary['product']->id);
});

test('category page preserves selected type in view all link', function () {
    $set = repairCategorySet(Category::TYPE_AGRICULTURE, 'Linked Category');

    $this->get(route('categories.show', [
        'id' => $set['category']->id,
        'type' => Category::TYPE_AGRICULTURE,
    ]))
        ->assertOk()
        ->assertSee('category_id='.$set['category']->id, false)
        ->assertSee('type=agriculture', false);
});

test('selected user product type filters categories and public products on backend', function () {
    $agriculture = repairCategorySet(Category::TYPE_AGRICULTURE, 'اختبار زراعي');
    $veterinary = repairCategorySet(Category::TYPE_VETERINARY, 'اختبار بيطري');
    $user = User::factory()->create([
        'type' => User::TYPE_USER,
        'preferred_product_type' => Category::TYPE_VETERINARY,
    ]);

    $this->actingAs($user);

    $categoryIds = collect($this->getJson('/api/categories?per_page=100')->assertOk()->json('data'))->pluck('id');
    $productIds = collect($this->getJson('/api/products?per_page=100')->assertOk()->json('data'))->pluck('id');

    expect($categoryIds)->toContain($veterinary['category']->id)->not->toContain($agriculture['category']->id)
        ->and($productIds)->toContain($veterinary['product']->id)->not->toContain($agriculture['product']->id);
});

test('public detail endpoints do not expose opposite type data', function () {
    $agriculture = repairCategorySet(Category::TYPE_AGRICULTURE, 'Agriculture Category');
    $veterinary = repairCategorySet(Category::TYPE_VETERINARY, 'Veterinary Category');

    $this->getJson('/api/categories/'.$agriculture['category']->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertOk();

    $this->getJson('/api/categories/'.$veterinary['category']->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertNotFound();

    $this->getJson('/api/subcategories/'.$agriculture['subcategory']->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertOk();

    $this->getJson('/api/subcategories/'.$veterinary['subcategory']->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertNotFound();

    $this->getJson('/api/products/'.$agriculture['product']->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertOk();

    $this->getJson('/api/products/'.$veterinary['product']->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertNotFound();
});

test('syndicate login redirects to syndicate dashboard and skips user type selection', function () {
    $user = User::factory()->syndicate()->create([
        'phone_number' => '0998000001',
        'password' => 'password',
    ]);

    Syndicate::factory()->for($user)->agriculture()->create([
        'status' => Syndicate::STATUS_ACTIVE,
    ]);

    $this->postJson('/api/auth/login', [
        'phone_number' => '0998000001',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.type', User::TYPE_SYNDICATE)
        ->assertJsonPath('data.redirect_url', route('syndicate.dashboard'));

    $this->actingAs($user)
        ->get('/product-type/select')
        ->assertRedirect(route('syndicate.dashboard'));
});

test('admin can create syndicate without logo and with valid logo', function () {
    Storage::fake('public');
    repairAdmin();

    $this->postJson('/api/admin/syndicates', [
        'name' => 'نقابة بدون شعار',
        'email' => 'no-logo-syndicate@example.com',
        'phone' => '0998000002',
        'password' => 'password',
        'password_confirmation' => 'password',
        'type' => Category::TYPE_AGRICULTURE,
        'status' => Syndicate::STATUS_ACTIVE,
    ])
        ->assertCreated()
        ->assertJsonPath('data.logo', null)
        ->assertJsonPath('data.logo_url', asset('images/syndicate-placeholder.svg'));

    $response = $this->postJson('/api/admin/syndicates', [
        'name' => 'نقابة مع شعار',
        'email' => 'logo-syndicate@example.com',
        'phone' => '0998000003',
        'password' => 'password',
        'password_confirmation' => 'password',
        'type' => Category::TYPE_VETERINARY,
        'status' => Syndicate::STATUS_ACTIVE,
        'logo' => UploadedFile::fake()->image('logo.png', 240, 240),
    ])
        ->assertCreated();

    expect($response->json('data.logo'))->toStartWith('syndicates/logos/');
    Storage::disk('public')->assertExists($response->json('data.logo'));
});

test('admin can create syndicate when an empty logo value is submitted by the browser', function () {
    Storage::fake('public');
    repairAdmin();

    $this->postJson('/api/admin/syndicates', [
        'name' => 'Empty Logo Syndicate',
        'email' => 'empty-logo-syndicate@example.com',
        'phone' => '0998000011',
        'password' => 'password',
        'password_confirmation' => 'password',
        'type' => Category::TYPE_AGRICULTURE,
        'status' => Syndicate::STATUS_ACTIVE,
        'logo' => '',
    ])
        ->assertCreated()
        ->assertJsonPath('data.logo', null)
        ->assertJsonPath('data.logo_url', asset('images/syndicate-placeholder.svg'));
});

test('admin can upload supported syndicate logo image formats', function (string $extension, string $phone) {
    Storage::fake('public');
    repairAdmin();

    $response = $this->postJson('/api/admin/syndicates', [
        'name' => 'Logo Format '.$extension,
        'email' => 'logo-format-'.$extension.'@example.com',
        'phone' => $phone,
        'password' => 'password',
        'password_confirmation' => 'password',
        'type' => Category::TYPE_VETERINARY,
        'status' => Syndicate::STATUS_ACTIVE,
        'logo' => UploadedFile::fake()->image('logo.'.$extension, 200, 200),
    ]);

    $response->assertCreated();
    expect($response->json('data.logo'))->toStartWith('syndicates/logos/');
})->with([
    ['jpg', '0998000020'],
    ['jpeg', '0998000021'],
    ['png', '0998000022'],
    ['webp', '0998000023'],
    ['gif', '0998000024'],
]);

test('admin syndicate logo upload rejects invalid files and details include aggregate fields', function () {
    Storage::fake('public');
    repairAdmin();

    $this->postJson('/api/admin/syndicates', [
        'name' => 'نقابة ملف خاطئ',
        'email' => 'bad-logo-syndicate@example.com',
        'phone' => '0998000004',
        'password' => 'password',
        'password_confirmation' => 'password',
        'type' => Category::TYPE_AGRICULTURE,
        'status' => Syndicate::STATUS_ACTIVE,
        'logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['logo']);

    $syndicate = Syndicate::factory()->agriculture()->create();

    $this->getJson('/api/admin/syndicates/'.$syndicate->id)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'user',
                'categories_count',
                'vendors_count',
                'products_count',
                'orders_count',
                'completed_orders_count',
                'total_sales',
                'logo_url',
            ],
        ]);
});
