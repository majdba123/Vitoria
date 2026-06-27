<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ApplicationCacheService;
use App\Services\ProductService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function performanceAdmin(): User
{
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    return $admin;
}

function performanceProductSet(int $quantity = 10): array
{
    $category = Category::query()->create([
        'name' => 'Performance Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'is_active' => true,
        'status' => Vendor::STATUS_ACTIVE,
    ]);
    $vendor->categories()->sync([$category->id]);
    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => $quantity,
        'price' => 100,
    ]);

    return compact('category', 'vendor', 'product');
}

test('guest can complete first visit startup timezone flow', function () {
    $this->getJson('/api/startup/preferences')
        ->assertOk()
        ->assertJsonPath('data.completed', false)
        ->assertJsonStructure(['data' => ['timezones']]);

    $response = $this->postJson('/api/startup/preferences', [
        'timezone' => 'Asia/Damascus',
        'location_preference' => 'Damascus delivery',
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.completed', true)
        ->assertJsonPath('data.timezone', 'Asia/Damascus')
        ->assertCookie('sz_timezone')
        ->assertCookie('sz_startup_completed');

    expect(session('timezone'))->toBe('Asia/Damascus')
        ->and(session('startup_completed'))->toBeTrue();
});

test('startup and profile timezone validation reject unsupported values', function () {
    $this->postJson('/api/startup/preferences', [
        'timezone' => 'Mars/Olympus',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['timezone']);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/profile', [
        'timezone' => 'Mars/Olympus',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['timezone']);
});

test('logged in startup flow stores timezone on user profile', function () {
    $user = User::factory()->create(['timezone' => null]);
    $this->actingAs($user);

    $this->postJson('/api/startup/preferences', [
        'timezone' => 'Europe/Istanbul',
        'location_preference' => 'Aleppo',
    ])->assertOk();

    expect($user->refresh()->timezone)->toBe('Europe/Istanbul');
});

test('category listing is paginated and preserves bounded per page', function () {
    Category::query()->create(['name' => 'Page Agriculture', 'type' => Category::TYPE_AGRICULTURE]);
    Category::query()->create(['name' => 'Page Veterinary', 'type' => Category::TYPE_VETERINARY]);

    $response = $this->getJson('/api/categories?per_page=1&type='.Category::TYPE_AGRICULTURE);

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.type', Category::TYPE_AGRICULTURE);
});

test('category product vendor settings and dashboard caches are synchronized by observers', function () {
    $set = performanceProductSet();
    Cache::put('admin_dashboard_overview', 'stale', 3600);
    Cache::put(ApplicationCacheService::DASHBOARD_ADMIN_STATS, 'stale', 3600);
    Cache::put(ApplicationCacheService::SETTINGS_WEBSITE, 'stale', 3600);

    $set['category']->update(['name' => 'Performance Agriculture Updated']);

    expect(Cache::get('admin_dashboard_overview'))->toBeNull()
        ->and(Cache::get(ApplicationCacheService::DASHBOARD_ADMIN_STATS))->toBeNull();

    Cache::put(ApplicationCacheService::DASHBOARD_ADMIN_STATS, 'stale', 3600);
    $set['product']->update(['name' => 'Updated Product']);
    expect(Cache::get(ApplicationCacheService::DASHBOARD_ADMIN_STATS))->toBeNull();

    Cache::put(ApplicationCacheService::DASHBOARD_ADMIN_STATS, 'stale', 3600);
    $set['vendor']->update(['status' => Vendor::STATUS_INACTIVE]);
    expect(Cache::get(ApplicationCacheService::DASHBOARD_ADMIN_STATS))->toBeNull();

    \App\Models\FooterSetting::instance()->update(['default_timezone' => 'Asia/Damascus']);
    expect(Cache::get(ApplicationCacheService::SETTINGS_WEBSITE))->toBeNull();
});

test('auth login is rate limited with clean response', function () {
    RateLimiter::clear('10.10.10.10');

    for ($i = 0; $i < 5; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])->postJson('/api/auth/login', [
            'phone_number' => '0990000000',
            'password' => 'wrong',
        ])->assertUnprocessable();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])->postJson('/api/auth/login', [
        'phone_number' => '0990000000',
        'password' => 'wrong',
    ])
        ->assertTooManyRequests()
        ->assertJsonPath('message', 'Too many attempts. Please try again soon.');
});

test('checkout failure does not create orders or decrement stock', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $set = performanceProductSet(quantity: 1);

    $this->postJson('/api/orders/checkout', [
        'items' => [
            ['product_id' => $set['product']->id, 'quantity' => 2],
        ],
    ])->assertUnprocessable();

    $this->assertDatabaseCount('orders', 0);
    expect($set['product']->refresh()->quantity)->toBe(1);
});

test('product display image upload is removed when product transaction fails', function () {
    Storage::fake('public');
    performanceAdmin();
    $set = performanceProductSet();

    app()->instance(ProductService::class, new class extends ProductService
    {
        public function create(?Vendor $vendor, array $data): Product
        {
            throw new RuntimeException('Forced product failure.');
        }
    });

    $this->post('/api/admin/products', [
        'vendor_id' => $set['vendor']->id,
        'category_id' => $set['category']->id,
        'name' => 'Rollback Product',
        'price' => 50,
        'quantity' => 5,
        'image' => UploadedFile::fake()->image('rollback.jpg'),
        'icon' => UploadedFile::fake()->image('rollback-icon.png'),
    ], ['Accept' => 'application/json'])->assertStatus(500);

    expect(Storage::disk('public')->allFiles('products/image'))->toBeEmpty()
        ->and(Storage::disk('public')->allFiles('products/icon'))->toBeEmpty();
});
