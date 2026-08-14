<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Admin/Vendor Products/Show pages only receive a `productId` prop and fetch
 * the rest (labels, photos, per-type detail fields) client-side via API, so
 * this now verifies the backend's actual contract — right component, right
 * product, right role, right locale context — rather than rendered section
 * headings/DOM markers that React (not Blade) owns post-migration.
 */
test('admin product show page renders for the correct product and locale', function () {
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
        ->withSession(['locale' => 'en'])
        ->get("/admin/products/{$product->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Products/Show')
            ->where('productId', $product->id)
            ->where('locale', 'en')
        );
});

test('vendor product show page renders for the correct product and locale', function () {
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
        ->withSession(['locale' => 'en'])
        ->get("/vendor/products/{$product->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vendor/Products/Show')
            ->where('productId', (string) $product->id)
            ->where('locale', 'en')
        );
});
