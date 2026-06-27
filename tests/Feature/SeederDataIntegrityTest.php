<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Database\Seeders\CategorySubcategorySeeder;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Support\Facades\Storage;

test('marketplace seeders create valid category vendor and product type data', function () {
    Storage::fake('public');

    $this->seed(CategorySubcategorySeeder::class);
    $this->seed(MarketplaceDemoSeeder::class);

    expect(Category::query()->where('type', Category::TYPE_AGRICULTURE)->count())->toBe(8)
        ->and(Category::query()->where('type', Category::TYPE_VETERINARY)->count())->toBe(8)
        ->and(Vendor::query()->where('business_type', Vendor::BUSINESS_TYPE_AGRICULTURE)->count())->toBe(1)
        ->and(Vendor::query()->where('business_type', Vendor::BUSINESS_TYPE_VETERINARY)->count())->toBe(1)
        ->and(Vendor::query()->where('business_type', Vendor::BUSINESS_TYPE_BOTH)->count())->toBe(0)
        ->and(Product::query()->count())->toBe(16);

    Vendor::query()
        ->with('categories:id,type')
        ->get()
        ->each(function (Vendor $vendor): void {
            $types = $vendor->categories->pluck('type')->unique()->values();

            expect($vendor->store_name)->toMatch('/\p{Arabic}/u');

            if ($vendor->business_type === Vendor::BUSINESS_TYPE_AGRICULTURE) {
                expect($types->all())->toBe([Category::TYPE_AGRICULTURE]);
            }

            if ($vendor->business_type === Vendor::BUSINESS_TYPE_VETERINARY) {
                expect($types->all())->toBe([Category::TYPE_VETERINARY]);
            }

            if ($vendor->business_type === Vendor::BUSINESS_TYPE_BOTH) {
                expect($types->sort()->values()->all())->toBe([
                    Category::TYPE_AGRICULTURE,
                    Category::TYPE_VETERINARY,
                ]);
            }
        });

    Product::query()
        ->with(['vendor.categories:id,type', 'subcategory.category:id,type'])
        ->get()
        ->each(function (Product $product): void {
            expect($product->image)->not->toBeNull()
                ->and($product->icon)->not->toBeNull()
                ->and($product->subcategory?->category)->not->toBeNull();

            Storage::disk('public')->assertExists($product->image);
            Storage::disk('public')->assertExists($product->icon);

            $productCategoryType = $product->subcategory->category->type;
            $vendorTypes = $product->vendor->categories->pluck('type')->unique();

            expect($vendorTypes)->toContain($productCategoryType);
        });
});
