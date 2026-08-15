<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;

/**
 * Bug report: a category page showed "0 products" in its header while the
 * product listing directly below it rendered real products. Root cause:
 * Api\Admin\CategoryController::show() never computed `products_count` at
 * all — `Category::toArray()` has no such attribute — so the frontend's
 * `category.products_count || 0` always fell back to 0 regardless of how
 * many products actually existed.
 */
test('category show endpoint reports a real products_count matching what the product listing actually returns', function () {
    $category = Category::query()->create(['name' => 'Veterinary Services', 'type' => Category::TYPE_VETERINARY]);
    $vendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_VETERINARY, 'is_active' => true]);

    Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 5,
    ]);
    Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 3,
    ]);

    $categoryResponse = $this->getJson('/api/categories/'.$category->id.'?type='.Category::TYPE_VETERINARY)
        ->assertOk();

    expect($categoryResponse->json('data.products_count'))->toBe(2);

    $listingResponse = $this->getJson('/api/products?category_id='.$category->id)->assertOk();
    expect($listingResponse->json('meta.total'))->toBe(2);
});

test('category products_count excludes products that are pending, inactive, or out of stock', function () {
    $category = Category::query()->create(['name' => 'Mixed Visibility Category', 'type' => Category::TYPE_AGRICULTURE]);
    $vendor = Vendor::factory()->create(['is_active' => true]);

    $visible = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 4,
    ]);
    Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_PENDING,
        'is_active' => true,
        'quantity' => 4,
    ]);
    Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => false,
        'quantity' => 4,
    ]);
    Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 0,
    ]);

    $response = $this->getJson('/api/categories/'.$category->id.'?type='.Category::TYPE_AGRICULTURE)->assertOk();

    expect($response->json('data.products_count'))->toBe(1);
});

test('approving a pending product immediately updates the category\'s cached products_count', function () {
    $category = Category::query()->create(['name' => 'Cache Invalidation Category', 'type' => Category::TYPE_AGRICULTURE]);
    $vendor = Vendor::factory()->create(['is_active' => true]);
    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_PENDING,
        'is_active' => true,
        'quantity' => 4,
    ]);

    // Warm the cache with the "0 visible products" state, matching a
    // shopper loading the category page before the product is approved.
    $this->getJson('/api/categories/'.$category->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertJsonPath('data.products_count', 0);

    $employee = \App\Models\User::factory()->employee()->create();
    $roleId = \App\Models\Role::query()->where('key', \App\Models\Role::KEY_CATALOG_MODERATOR)->value('id');
    $employee->employeeRoles()->attach($roleId);
    \Laravel\Sanctum\Sanctum::actingAs($employee);

    $this->putJson("/api/employee/products/{$product->id}", ['status' => 'approved'])->assertOk();

    $this->getJson('/api/categories/'.$category->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertJsonPath('data.products_count', 1);
});
