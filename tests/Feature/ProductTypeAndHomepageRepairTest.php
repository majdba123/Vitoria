<?php

use App\Models\Category;
use App\Models\Product;
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

    $vendor = Vendor::factory()->create([
        'business_type' => $type,
        'status' => Vendor::STATUS_ACTIVE,
        'is_active' => true,
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 10,
        'image' => null,
        'icon' => null,
    ]);

    return compact('category', 'vendor', 'product');
}

test('homepage product api is safe with missing optional image data', function () {
    $category = Category::query()->create([
        'name' => 'Safe Listing Category',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $vendor = Vendor::factory()->create([
        'status' => Vendor::STATUS_ACTIVE,
        'is_active' => true,
    ]);
    $vendor->categories()->sync([$category->id]);

    Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 5,
        'image' => null,
        'icon' => null,
    ]);

    $response = $this->getJson('/api/products?per_page=5')->assertOk();

    $response->assertJsonPath('data.0.category_id', $category->id)
        ->assertJsonPath('data.0.category.id', $category->id);

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
    $agriculture = repairCategorySet(Category::TYPE_AGRICULTURE, 'Ø§Ø®ØªØ¨Ø§Ø± Ø²Ø±Ø§Ø¹ÙŠ');
    $veterinary = repairCategorySet(Category::TYPE_VETERINARY, 'Ø§Ø®ØªØ¨Ø§Ø± Ø¨ÙŠØ·Ø±ÙŠ');
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
});

test('admin can create syndicate without logo and with valid logo', function () {
    Storage::fake('public');
    repairAdmin();

    $this->postJson('/api/admin/syndicates', [
        'name' => 'Ù†Ù‚Ø§Ø¨Ø© Ø¨Ø¯ÙˆÙ† Ø´Ø¹Ø§Ø±',
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
        'name' => 'Ù†Ù‚Ø§Ø¨Ø© Ù…Ø¹ Ø´Ø¹Ø§Ø±',
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
