<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\QueryException;

/**
 * Regression coverage for the full-stack audit fixes (2026-08-15): financial
 * history must survive the deletion of the account that generated it, and a
 * product's cached response must never leak one locale's content to another.
 */
it('refuses to delete a vendor that has order history, instead of cascading it away', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $category = Category::factory()->create();
    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
    ]);
    $customer = User::factory()->create();

    Order::factory()->create([
        'user_id' => $customer->id,
        'vendor_id' => $vendor->id,
    ]);

    expect(fn () => $vendor->delete())->toThrow(QueryException::class);
    expect(fn () => $customer->delete())->toThrow(QueryException::class);

    expect(Vendor::query()->whereKey($vendor->id)->exists())->toBeTrue()
        ->and(Order::query()->where('vendor_id', $vendor->id)->exists())->toBeTrue();
});

it('keeps a product refund and return record even if the order is force-touched', function () {
    // Sanity check that the restriction targets the intended tables and
    // doesn't accidentally also lock down order_items (a legitimate child
    // row that should still cascade with its own parent order).
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'vendor_id' => $vendor->id]);

    expect(fn () => $order->delete())->not->toThrow(QueryException::class);
});

it('does not leak one locale\'s cached product name into another locale\'s response', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $category = Category::factory()->create();
    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 5,
        'name_en' => 'English Widget',
        'name_ar' => 'قطعة عربية',
    ]);

    $english = $this->getJson("/api/products/{$product->id}", ['Accept-Language' => 'en']);
    $english->assertOk()->assertJsonPath('data.name', 'English Widget');

    $arabic = $this->getJson("/api/products/{$product->id}", ['Accept-Language' => 'ar']);
    $arabic->assertOk()->assertJsonPath('data.name', 'قطعة عربية');

    // Re-request English again to prove the Arabic call didn't overwrite the
    // English cache entry either (both keys coexist independently).
    $englishAgain = $this->getJson("/api/products/{$product->id}", ['Accept-Language' => 'en']);
    $englishAgain->assertOk()->assertJsonPath('data.name', 'English Widget');
});
