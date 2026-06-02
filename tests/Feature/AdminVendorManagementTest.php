<?php

use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function actingAsAdmin(): User
{
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    return $admin;
}

test('existing admin add vendor flow still creates an active admin vendor', function () {
    actingAsAdmin();
    $city = City::query()->create(['name' => 'Aleppo']);
    $category = Category::query()->create([
        'name' => 'Admin Created Category',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $response = $this->postJson('/api/admin/vendors', [
        'name' => 'Admin Created Vendor',
        'email' => 'admin-vendor@example.com',
        'password' => 'password',
        'phone_number' => '0992000001',
        'national_id' => '2234567890',
        'store_name' => 'Admin Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'city_id' => $city->id,
        'latitude' => 36.2021,
        'longitude' => 37.1343,
        'category_ids' => [$category->id],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', Vendor::STATUS_ACTIVE)
        ->assertJsonPath('data.registration_source', Vendor::REGISTRATION_SOURCE_ADMIN)
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('vendors', [
        'store_name' => 'Admin Store',
        'is_active' => true,
        'status' => Vendor::STATUS_ACTIVE,
        'registration_source' => Vendor::REGISTRATION_SOURCE_ADMIN,
    ]);
});

test('admin category creation requires a valid type', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/categories', [
        'name' => 'Missing Type Category',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);

    $this->postJson('/api/admin/categories', [
        'name' => 'Typed Category',
        'type' => Category::TYPE_VETERINARY,
        'commission' => 7.5,
    ])
        ->assertCreated()
        ->assertJsonPath('data.type', Category::TYPE_VETERINARY);
});

test('category endpoints filter categories by type', function () {
    actingAsAdmin();
    $agricultureCategory = Category::query()->create([
        'name' => 'Filter Seeds',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $veterinaryCategory = Category::query()->create([
        'name' => 'Filter Vaccines',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $response = $this->getJson('/api/admin/categories?type='.Category::TYPE_AGRICULTURE);

    $response->assertOk();

    $categoryIds = collect($response->json('data'))->pluck('id');

    expect($categoryIds)->toContain($agricultureCategory->id)
        ->and($categoryIds)->not->toContain($veterinaryCategory->id);
});

test('admin vendor creation rejects categories outside business type', function () {
    actingAsAdmin();
    $city = City::query()->create(['name' => 'Hama']);
    $veterinaryCategory = Category::query()->create([
        'name' => 'Admin Veterinary Category',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $this->postJson('/api/admin/vendors', [
        'name' => 'Mismatch Vendor',
        'email' => 'mismatch-vendor@example.com',
        'password' => 'password',
        'phone_number' => '0992111000',
        'national_id' => '2234111000',
        'store_name' => 'Mismatch Vendor Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'city_id' => $city->id,
        'latitude' => 35.1318,
        'longitude' => 36.7578,
        'category_ids' => [$veterinaryCategory->id],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_ids']);
});

test('admin can filter vendors by business type category type and selected category', function () {
    actingAsAdmin();
    $agricultureCategory = Category::query()->create([
        'name' => 'Agriculture Filter Category',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $veterinaryCategory = Category::query()->create([
        'name' => 'Veterinary Filter Category',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $agricultureVendor = Vendor::factory()->create([
        'store_name' => 'Agriculture Filter Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $agricultureVendor->categories()->sync([$agricultureCategory->id]);

    $veterinaryVendor = Vendor::factory()->create([
        'store_name' => 'Veterinary Filter Store',
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);
    $veterinaryVendor->categories()->sync([$veterinaryCategory->id]);

    $this->getJson('/api/admin/vendors?business_type='.Vendor::BUSINESS_TYPE_AGRICULTURE.'&category_type='.Category::TYPE_AGRICULTURE.'&category_id='.$agricultureCategory->id)
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $agricultureVendor->id)
        ->assertJsonPath('data.0.business_type', Vendor::BUSINESS_TYPE_AGRICULTURE)
        ->assertJsonPath('data.0.categories.0.type', Category::TYPE_AGRICULTURE);
});

test('admin can view pending merchants and filter by status name and email together', function () {
    actingAsAdmin();

    $matchingUser = User::factory()->create([
        'type' => User::TYPE_VENDOR,
        'name' => 'Filtered Owner',
        'email' => 'filtered@example.com',
    ]);
    $matchingVendor = Vendor::factory()->for($matchingUser)->pending()->create([
        'store_name' => 'Filtered Store',
    ]);

    $otherUser = User::factory()->create([
        'type' => User::TYPE_VENDOR,
        'name' => 'Other Owner',
        'email' => 'other@example.com',
    ]);
    Vendor::factory()->for($otherUser)->create([
        'store_name' => 'Other Store',
    ]);

    $response = $this->getJson('/api/admin/vendors?status=pending&name=Filtered&email=filtered@example.com');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $matchingVendor->id)
        ->assertJsonPath('data.0.status', Vendor::STATUS_PENDING);
});

test('admin vendor list includes category data and supports category filters with other filters', function () {
    actingAsAdmin();
    $category = Category::query()->create(['name' => 'Filtered Category']);
    $otherCategory = Category::query()->create(['name' => 'Other Category']);

    $matchingUser = User::factory()->create([
        'type' => User::TYPE_VENDOR,
        'name' => 'Category Owner',
        'email' => 'category-owner@example.com',
    ]);
    $matchingVendor = Vendor::factory()->for($matchingUser)->pending()->create([
        'store_name' => 'Category Store',
    ]);
    $matchingVendor->categories()->sync([$category->id]);

    $otherUser = User::factory()->create([
        'type' => User::TYPE_VENDOR,
        'name' => 'Category Other',
        'email' => 'category-other@example.com',
    ]);
    $otherVendor = Vendor::factory()->for($otherUser)->pending()->create([
        'store_name' => 'Other Category Store',
    ]);
    $otherVendor->categories()->sync([$otherCategory->id]);

    $response = $this->getJson('/api/admin/vendors?category_id='.$category->id.'&status=pending&name=Category&email=category-owner@example.com');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $matchingVendor->id)
        ->assertJsonPath('data.0.categories.0.id', $category->id)
        ->assertJsonPath('data.0.categories.0.name', 'Filtered Category');
});

test('admin vendor details includes category data and old vendors without categories still load', function () {
    actingAsAdmin();
    $category = Category::query()->create(['name' => 'Detail Category']);

    $vendor = Vendor::factory()->create();
    $vendor->categories()->sync([$category->id]);

    $this->getJson('/api/admin/vendors/'.$vendor->id)
        ->assertOk()
        ->assertJsonPath('data.categories.0.id', $category->id)
        ->assertJsonPath('data.categories.0.name', 'Detail Category');

    $oldVendor = Vendor::factory()->create();

    $this->getJson('/api/admin/vendors/'.$oldVendor->id)
        ->assertOk()
        ->assertJsonPath('data.categories', []);
});

test('admin dashboard vendor category statistics are grouped by category', function () {
    actingAsAdmin();
    $category = Category::query()->create(['name' => 'Dashboard Category']);
    $otherCategory = Category::query()->create(['name' => 'Other Dashboard Category']);

    $activeVendor = Vendor::factory()->create([
        'is_active' => true,
        'status' => Vendor::STATUS_ACTIVE,
    ]);
    $activeVendor->categories()->sync([$category->id]);

    $pendingVendor = Vendor::factory()->pending()->create();
    $pendingVendor->categories()->sync([$category->id]);

    $inactiveVendor = Vendor::factory()->inactive()->create();
    $inactiveVendor->categories()->sync([$otherCategory->id]);

    Vendor::factory()->create([
        'store_name' => 'Unassigned Vendor',
        'is_active' => true,
        'status' => Vendor::STATUS_ACTIVE,
    ]);

    $response = $this->getJson('/api/admin/dashboard/vendor-category-stats');

    $response->assertOk();

    $stats = collect($response->json('data'));
    $categoryStats = $stats->firstWhere('id', $category->id);
    $otherStats = $stats->firstWhere('id', $otherCategory->id);
    $unassignedStats = $stats->firstWhere('id', null);

    expect($categoryStats)->not->toBeNull()
        ->and($categoryStats['total_vendors'])->toBe(2)
        ->and($categoryStats['active_vendors'])->toBe(1)
        ->and($categoryStats['pending_vendors'])->toBe(1)
        ->and($otherStats['inactive_vendors'])->toBe(1)
        ->and($unassignedStats['name'])->toBe('Not assigned')
        ->and($unassignedStats['total_vendors'])->toBe(1);
});

test('admin dashboard overview includes vendor type category type and recent vendor statistics', function () {
    Cache::forget('admin_dashboard_overview');
    actingAsAdmin();
    $agricultureCategory = Category::query()->create([
        'name' => 'Overview Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $veterinaryCategory = Category::query()->create([
        'name' => 'Overview Veterinary',
        'type' => Category::TYPE_VETERINARY,
    ]);
    $agricultureSubcategory = Subcategory::query()->create([
        'category_id' => $agricultureCategory->id,
        'name' => 'Overview Seeds',
    ]);
    $veterinarySubcategory = Subcategory::query()->create([
        'category_id' => $veterinaryCategory->id,
        'name' => 'Overview Vaccines',
    ]);

    $agricultureVendor = Vendor::factory()->create([
        'store_name' => 'Overview Agriculture Vendor',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $agricultureVendor->categories()->sync([$agricultureCategory->id]);

    $bothVendor = Vendor::factory()->create([
        'store_name' => 'Overview Both Vendor',
        'business_type' => Vendor::BUSINESS_TYPE_BOTH,
    ]);
    $bothVendor->categories()->sync([$agricultureCategory->id, $veterinaryCategory->id]);

    Product::factory()->for($agricultureVendor)->create([
        'name' => 'Overview Agriculture Product',
        'subcategory_id' => $agricultureSubcategory->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
    ]);
    Product::factory()->for($bothVendor)->inactive()->create([
        'name' => 'Overview Veterinary Product',
        'subcategory_id' => $veterinarySubcategory->id,
        'status' => Product::STATUS_PENDING,
    ]);

    $response = $this->getJson('/api/admin/dashboard/overview');

    $response->assertOk()
        ->assertJsonPath('data.total_vendors', 2)
        ->assertJsonPath('data.total_products', 2)
        ->assertJsonPath('data.active_products', 1)
        ->assertJsonPath('data.inactive_products', 1)
        ->assertJsonPath('data.type_stats.vendors_in_both', 1)
        ->assertJsonPath('data.type_stats.products_in_agriculture', 1)
        ->assertJsonPath('data.type_stats.products_in_veterinary', 1);

    $vendorsByType = collect($response->json('data.vendors_by_type'));
    $categoriesByType = collect($response->json('data.categories_by_type'));
    $mostSelected = collect($response->json('data.most_selected_categories'));
    $productsByCategoryType = collect($response->json('data.products_by_category_type'));
    $topVendors = collect($response->json('data.top_vendors_by_product_count'));

    expect($vendorsByType->firstWhere('type', Vendor::BUSINESS_TYPE_AGRICULTURE)['total'])->toBe(1)
        ->and($vendorsByType->firstWhere('type', Vendor::BUSINESS_TYPE_BOTH)['total'])->toBe(1)
        ->and($categoriesByType->firstWhere('type', Category::TYPE_AGRICULTURE)['total'])->toBe(1)
        ->and($categoriesByType->firstWhere('type', Category::TYPE_VETERINARY)['total'])->toBe(1)
        ->and($mostSelected->firstWhere('id', $agricultureCategory->id)['vendors_count'])->toBe(2)
        ->and($productsByCategoryType->firstWhere('type', Category::TYPE_AGRICULTURE)['total'])->toBe(1)
        ->and($productsByCategoryType->firstWhere('type', Category::TYPE_VETERINARY)['total'])->toBe(1)
        ->and($topVendors->pluck('products_count')->max())->toBe(1)
        ->and($response->json('data.recent_vendor_registrations.0.store_name'))->toBe('Overview Both Vendor')
        ->and($response->json('data.recent_products.0.name'))->toBe('Overview Veterinary Product');
});

test('admin can download a merchant commercial register document', function () {
    Storage::fake('local');
    actingAsAdmin();

    Storage::disk('local')->put('commercial-registers/register.pdf', 'pdf-content');
    $vendor = Vendor::factory()->pending()->create([
        'commercial_register_file' => 'commercial-registers/register.pdf',
    ]);

    $this->get('/api/admin/vendors/'.$vendor->id.'/commercial-register')
        ->assertOk()
        ->assertDownload('register.pdf');
});

test('admin can approve a pending merchant and make it active', function () {
    actingAsAdmin();
    $vendor = Vendor::factory()->pending()->create();

    $response = $this->patchJson('/api/admin/vendors/'.$vendor->id.'/approve');

    $response->assertOk()
        ->assertJsonPath('data.status', Vendor::STATUS_ACTIVE)
        ->assertJsonPath('data.is_active', true);

    $vendor->refresh();

    expect($vendor->status)->toBe(Vendor::STATUS_ACTIVE)
        ->and($vendor->is_active)->toBeTrue();
});
