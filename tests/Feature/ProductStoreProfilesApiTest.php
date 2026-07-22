<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

test('admin can store agriculture product through dedicated agriculture endpoint', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Dedicated Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $this->postJson('/api/admin/products/store-agriculture', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name_ar' => 'سماد مخصص',
        'name_en' => 'Special Fertilizer',
        'price' => 99,
        'quantity' => 20,
        'shared_detail' => [
            'commercial_name' => 'Special Grow',
            'barcodes' => ['AG-SP-1'],
        ],
        'agricultural_detail' => [
            'agricultural_product_type' => 'fertilizer',
            'fertilizer_type' => 'organic',
        ],
    ])->assertCreated()
        ->assertJsonPath('data.name_en', 'Special Fertilizer')
        ->assertJsonPath('data.product_type', 'fertilizer');

    expect(Product::query()->count())->toBe(1);
});

test('agriculture endpoint rejects request without agricultural detail', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Agriculture Only',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $this->postJson('/api/admin/products/store-agriculture', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name_ar' => 'منتج ناقص',
        'name_en' => 'Incomplete Product',
        'price' => 50,
        'quantity' => 10,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['agricultural_detail']);
});

test('veterinary endpoint rejects agriculture category', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Wrong Category',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $this->postJson('/api/admin/products/store-veterinary', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name_ar' => 'منتج خاطئ',
        'name_en' => 'Wrong Product',
        'price' => 60,
        'quantity' => 5,
        'veterinary_detail' => [
            'concentration' => '10%',
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});
