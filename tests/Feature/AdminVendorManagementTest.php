<?php

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use App\Models\Vendor;
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

    $response = $this->postJson('/api/admin/vendors', [
        'name' => 'Admin Created Vendor',
        'email' => 'admin-vendor@example.com',
        'password' => 'password',
        'phone_number' => '0992000001',
        'national_id' => '2234567890',
        'store_name' => 'Admin Store',
        'city_id' => $city->id,
        'latitude' => 36.2021,
        'longitude' => 37.1343,
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
