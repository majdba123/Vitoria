<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MarketplaceDemoSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    protected array $seedImagePaths = [];

    public function run(): void
    {
        $subcategories = Subcategory::query()->with('category')->get();
        if ($subcategories->isEmpty()) {
            return;
        }

        $allCategories = Category::query()->get();
        Storage::disk('public')->makeDirectory('products');
        Storage::disk('public')->makeDirectory('seed');
        $this->seedImagePaths = $this->collectSeedImages();

        /** @var array<int, array{name: string, phone_number: string, national_id: string, age: int, membership_number: string, store_name: string, business_type: string, description: string, address: string, categories: list<string>}> $vendors */
        $vendors = [
            [
                'name' => 'Green Fields Vendor',
                'phone_number' => '0944615776',
                'national_id' => '1000000001',
                'age' => 35,
                'membership_number' => 'MEM-VENDOR-1000000001',
                'store_name' => 'Green Fields Supply',
                'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
                'description' => 'Crop inputs, seeds, and field essentials.',
                'address' => 'Damascus - Mazzeh',
                'categories' => ['Seeds', 'Fertilizers', 'Irrigation'],
            ],
            [
                'name' => 'FarmEquip Vendor',
                'phone_number' => '5129914',
                'national_id' => '1000000002',
                'age' => 42,
                'membership_number' => 'MEM-VENDOR-1000000002',
                'store_name' => 'FarmEquip Pro',
                'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
                'description' => 'Tractors, tools, and barn hardware.',
                'address' => 'Aleppo - New Aleppo',
                'categories' => ['Agricultural Equipment', 'Greenhouses'],
            ],
            [
                'name' => 'AgriVet Vendor',
                'phone_number' => '0911000003',
                'national_id' => '1000000003',
                'age' => 28,
                'membership_number' => 'MEM-VENDOR-1000000003',
                'store_name' => 'AgriVet Market',
                'business_type' => Vendor::BUSINESS_TYPE_BOTH,
                'description' => 'Mixed agricultural and animal-care products.',
                'address' => 'Homs - City Center',
                'categories' => ['Seeds', 'Animal Medicine', 'Feed Supplements'],
            ],
            [
                'name' => 'CareVet Vendor',
                'phone_number' => '0911000004',
                'national_id' => '1000000004',
                'age' => 38,
                'membership_number' => 'MEM-VENDOR-1000000004',
                'store_name' => 'CareVet Supplies',
                'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
                'description' => 'Veterinary medicine, vaccines, and livestock-care essentials.',
                'address' => 'Latakia - City Center',
                'categories' => ['Animal Medicine', 'Vaccines', 'Livestock Equipment'],
            ],
        ];

        foreach ($vendors as $vendorData) {
            $user = User::query()->updateOrCreate(
                ['phone_number' => $vendorData['phone_number']],
                [
                    'name' => $vendorData['name'],
                    'national_id' => $vendorData['national_id'],
                    'age' => $vendorData['age'],
                    'membership_number' => $vendorData['membership_number'],
                    'type' => User::TYPE_VENDOR,
                    'email' => str_replace('0', '', $vendorData['phone_number']).'@msz-demo.test',
                    'password' => 'password',
                ],
            );

            $vendor = Vendor::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $vendorData['store_name'],
                    'business_type' => $vendorData['business_type'],
                    'description' => $vendorData['description'],
                    'address' => $vendorData['address'],
                    'is_active' => true,
                ],
            );

            $categoryIds = $allCategories
                ->whereIn('name', $vendorData['categories'])
                ->pluck('id')
                ->toArray();

            $vendor->categories()->sync($categoryIds);

            $allowedCategoryIds = $vendor->categories()->pluck('categories.id')->all();
            $allowedSubcategories = $subcategories->whereIn('category_id', $allowedCategoryIds);

            $this->seedVendorProducts($vendor, $allowedSubcategories);
        }
    }

    /**
     * @param  Collection<int, Subcategory>  $subcategories
     */
    protected function seedVendorProducts(Vendor $vendor, Collection $subcategories): void
    {
        if ($subcategories->isEmpty()) {
            return;
        }

        $productNames = [
            'Premium Product',
            'Daily Product',
            'Popular Product',
            'Budget Product',
        ];

        foreach ($productNames as $suffix) {
            $subcategory = $subcategories->random();

            $product = Product::query()->updateOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'name' => "{$vendor->store_name} {$suffix}",
                ],
                [
                    'subcategory_id' => $subcategory->id,
                    'description' => fake()->sentence(14),
                    'price' => fake()->randomFloat(2, 25, 950),
                    'quantity' => fake()->numberBetween(3, 120),
                    'is_active' => true,
                    'status' => Product::STATUS_APPROVED,
                ],
            );

            $product->update([
                'icon' => $product->icon ?: $this->copyProductSeedAsset($product, 'icon'),
                'image' => $product->image ?: $this->copyProductSeedAsset($product, 'image'),
            ]);

            $this->attachProductPhotos($product);
        }
    }

    protected function attachProductPhotos(Product $product): void
    {
        if ($product->photos()->exists()) {
            return;
        }

        if (empty($this->seedImagePaths)) {
            return;
        }

        foreach ($this->seedImagePaths as $index => $sourcePath) {
            $ext = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = "products/{$product->id}-photo-{$index}.{$ext}";

            Storage::disk('public')->copy($sourcePath, $filename);

            ProductPhoto::query()->create([
                'product_id' => $product->id,
                'path' => $filename,
                'sort_order' => $index,
                'is_primary' => $index === 0,
            ]);
        }
    }

    protected function copyProductSeedAsset(Product $product, string $kind): ?string
    {
        if (empty($this->seedImagePaths)) {
            return null;
        }

        $sourcePath = $this->seedImagePaths[0];
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'png';
        $filename = "products/{$kind}/{$product->id}-{$kind}.{$ext}";

        Storage::disk('public')->copy($sourcePath, $filename);

        return $filename;
    }

    /**
     * @return list<string> Storage paths on the public disk (under seed/) used as sources for product photos.
     */
    protected function collectSeedImages(): array
    {
        $fixture = base_path('database/seeders/fixtures/default-product.png');
        if (File::isFile($fixture)) {
            $dest = 'seed/default-product.png';
            File::copy($fixture, Storage::disk('public')->path($dest));

            return [$dest];
        }

        $this->command?->warn('Missing database/seeders/fixtures/default-product.png — demo products will have no photos.');

        $files = Storage::disk('public')->files('seed');

        return array_values(array_filter(
            $files,
            fn (string $f) => (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', $f),
        ));
    }
}
