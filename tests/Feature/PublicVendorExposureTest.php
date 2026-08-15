<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Stakeholder review #15/#20: Product Details must show public vendor
 * context, but never leak the vendor account's private contact/business data.
 */
test('anonymous shoppers see a public-safe vendor block on product details, never private fields', function () {
    $category = Category::query()->create(['name' => 'Crop Protection', 'type' => Category::TYPE_AGRICULTURE]);

    $vendor = Vendor::factory()->create([
        'is_active' => true,
        'status' => Vendor::STATUS_ACTIVE,
        'store_name' => 'Green Fields Co-op',
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 10,
    ]);

    $this->getJson('/api/products/'.$product->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertOk()
        ->assertJsonPath('data.vendor.id', $vendor->id)
        ->assertJsonPath('data.vendor.store_name', 'Green Fields Co-op')
        ->assertJsonMissingPath('data.vendor.user')
        ->assertJsonMissingPath('data.vendor.commercial_register_file')
        ->assertJsonMissingPath('data.vendor.paid_amount');
});

test('admin viewers still see the full vendor resource on product details', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create(['name' => 'Crop Protection 2', 'type' => Category::TYPE_AGRICULTURE]);
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 10,
    ]);

    $this->getJson('/api/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.vendor.id', $vendor->id)
        // 'commercial_register_url' only appears on the full VendorResource,
        // never on the public-safe subset anonymous/customer viewers get.
        ->assertJsonStructure(['data' => ['vendor' => ['commercial_register_url']]]);
});

test('a public vendor storefront exposes only public-safe fields for an active vendor', function () {
    $vendor = Vendor::factory()->create([
        'is_active' => true,
        'status' => Vendor::STATUS_ACTIVE,
        'store_name' => 'Highland Vets',
    ]);

    $this->getJson('/api/vendors/'.$vendor->id)
        ->assertOk()
        ->assertJsonPath('data.id', $vendor->id)
        ->assertJsonPath('data.store_name', 'Highland Vets')
        ->assertJsonMissingPath('data.user')
        ->assertJsonMissingPath('data.commercial_register_file')
        ->assertJsonMissingPath('data.paid_amount');
});

test('a public vendor storefront 404s for an inactive or pending vendor', function () {
    $inactive = Vendor::factory()->create(['is_active' => false]);
    $pending = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_PENDING]);

    $this->getJson('/api/vendors/'.$inactive->id)->assertNotFound();
    $this->getJson('/api/vendors/'.$pending->id)->assertNotFound();
});

test('customer order details surface the fulfilling vendor\'s public name', function () {
    $user = User::factory()->create();
    $vendor = Vendor::factory()->create(['store_name' => 'Riverside Supplies']);

    $order = \App\Models\Order::factory()->for($user)->for($vendor)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/orders/'.$order->id)
        ->assertOk()
        ->assertJsonPath('data.vendor.store_name', 'Riverside Supplies');
});
