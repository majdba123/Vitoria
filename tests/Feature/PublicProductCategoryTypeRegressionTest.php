<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Vendor;
use Illuminate\Support\Facades\Cache;

function categoryTypeProduct(string $categoryType, string $name, ?Subcategory $subcategory = null): Product
{
    $category = $subcategory?->category ?? Category::factory()->create(['type' => $categoryType]);
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    return Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'subcategory_id' => $subcategory?->id,
        'name' => $name,
        'name_ar' => $name,
        'name_en' => $name,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 10,
    ]);
}

it('returns only visible veterinary products for the canonical category type', function () {
    $veterinary = categoryTypeProduct(Category::TYPE_VETERINARY, 'Veterinary filter target');
    $agriculture = categoryTypeProduct(Category::TYPE_AGRICULTURE, 'Agriculture filter exclusion');

    $ids = collect($this->getJson('/api/products?category_type=veterinary')->assertOk()->json('data'))->pluck('id');

    expect($ids)->toContain($veterinary->id)->not->toContain($agriculture->id);
});

it('combines veterinary type with search category subcategory and pagination', function () {
    $category = Category::factory()->create(['type' => Category::TYPE_VETERINARY]);
    $subcategory = Subcategory::query()->create(['category_id' => $category->id, 'name_ar' => 'اختبار', 'name_en' => 'Test']);
    $matching = categoryTypeProduct(Category::TYPE_VETERINARY, 'Needle combination match', $subcategory);
    categoryTypeProduct(Category::TYPE_VETERINARY, 'Different veterinary product');
    categoryTypeProduct(Category::TYPE_AGRICULTURE, 'Needle agriculture product');

    $response = $this->getJson('/api/products?category_type=veterinary&category_id='.$category->id.'&subcategory_id='.$subcategory->id.'&search=Needle&per_page=1&page=1')->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('meta.per_page'))->toBe(1)
        ->and($response->json('data.0.id'))->toBe($matching->id);
});

it('isolates cached public product results by category type', function () {
    Cache::flush();
    $veterinary = categoryTypeProduct(Category::TYPE_VETERINARY, 'Cached veterinary');
    $agriculture = categoryTypeProduct(Category::TYPE_AGRICULTURE, 'Cached agriculture');

    $agricultureIds = collect($this->getJson('/api/products?category_type=agriculture')->assertOk()->json('data'))->pluck('id');
    $veterinaryIds = collect($this->getJson('/api/products?category_type=veterinary')->assertOk()->json('data'))->pluck('id');

    expect($agricultureIds)->toContain($agriculture->id)->not->toContain($veterinary->id)
        ->and($veterinaryIds)->toContain($veterinary->id)->not->toContain($agriculture->id);
});
