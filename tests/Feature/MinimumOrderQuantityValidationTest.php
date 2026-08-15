<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

/**
 * Full-stack audit finding: nothing stopped minimum_order_quantity from
 * exceeding a product's own quantity, which silently makes the product
 * permanently unpurchasable (every cart attempt fails either the minimum
 * check or the stock check) with no warning to the vendor/admin who set it.
 */
function vendorWithCategory(): array
{
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->for($owner)->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $category = Category::query()->create(['name' => 'MOQ Test Category', 'type' => Category::TYPE_AGRICULTURE]);
    $vendor->categories()->sync([$category->id]);

    return [$owner, $vendor, $category];
}

test('a vendor cannot set a minimum order quantity above the product quantity on create', function () {
    [$owner, $vendor, $category] = vendorWithCategory();
    Sanctum::actingAs($owner);

    $this->postJson('/api/vendor/products', [
        'category_id' => $category->id,
        'name_ar' => 'منتج',
        'name_en' => 'Product',
        'price' => 50,
        'quantity' => 5,
        'minimum_order_quantity' => 100,
    ])->assertUnprocessable()->assertJsonValidationErrors(['minimum_order_quantity']);

    expect(Product::query()->count())->toBe(0);
});

test('a vendor cannot set a minimum order quantity above the product quantity on update', function () {
    [$owner, $vendor, $category] = vendorWithCategory();
    Sanctum::actingAs($owner);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'quantity' => 5,
        'minimum_order_quantity' => 1,
    ]);

    // Only minimum_order_quantity submitted — quantity must fall back to the
    // existing product's value (5), not silently pass validation.
    $this->putJson("/api/vendor/products/{$product->id}", [
        'minimum_order_quantity' => 100,
    ])->assertUnprocessable()->assertJsonValidationErrors(['minimum_order_quantity']);

    expect($product->refresh()->minimum_order_quantity)->toBe(1);
});

test('a vendor can raise both quantity and minimum_order_quantity together in one request', function () {
    [$owner, $vendor, $category] = vendorWithCategory();
    Sanctum::actingAs($owner);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'quantity' => 5,
        'minimum_order_quantity' => 1,
    ]);

    $this->putJson("/api/vendor/products/{$product->id}", [
        'quantity' => 200,
        'minimum_order_quantity' => 100,
    ])->assertOk();

    expect($product->refresh()->minimum_order_quantity)->toBe(100);
});

test('an admin cannot set a minimum order quantity above the product quantity', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    [, $vendor, $category] = vendorWithCategory();

    $this->postJson('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name_ar' => 'منتج',
        'name_en' => 'Product',
        'price' => 50,
        'quantity' => 3,
        'minimum_order_quantity' => 10,
    ])->assertUnprocessable()->assertJsonValidationErrors(['minimum_order_quantity']);
});
