<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;

test('admin product show page renders the upgraded detail layout', function () {
    $admin = User::factory()->admin()->create();
    $vendor = Vendor::factory()->create();
    $category = Category::query()->create([
        'name' => 'Agriculture Inputs',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'name' => 'Admin Detail Product',
    ]);

    $this->actingAs($admin)
        ->get("/admin/products/{$product->id}")
        ->assertOk()
        ->assertSee('Product Overview', false)
        ->assertSee('Core product parameters', false)
        ->assertSee('Agricultural Profile', false)
        ->assertSee('product-photo-modal', false);
});

test('vendor product show page renders the upgraded detail layout', function () {
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);
    $vendor->user->update(['type' => User::TYPE_VENDOR]);

    $category = Category::query()->create([
        'name' => 'Veterinary Medicine',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'name' => 'Vendor Detail Product',
    ]);

    $this->actingAs($vendor->user)
        ->get("/vendor/products/{$product->id}")
        ->assertOk()
        ->assertSee('Product gallery', false)
        ->assertSee('Shared Profile', false)
        ->assertSee('Veterinary Profile', false)
        ->assertSee('vendor-product-photo-modal', false);
});
