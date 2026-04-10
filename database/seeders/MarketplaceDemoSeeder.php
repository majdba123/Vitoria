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
        $this->seedImagePaths = $this->collectSeedImages();

        Storage::disk('public')->makeDirectory('products');

        /** @var array<int, array{name: string, phone_number: string, national_id: string, store_name: string, description: string, address: string, categories: list<string>}> $vendors */
        $vendors = [
            [
                'name' => 'Tech Hub Vendor',
                'phone_number' => '0944615776',
                'national_id' => '1000000001',
                'store_name' => 'Tech Hub',
                'description' => 'Electronics and gadgets for daily use.',
                'address' => 'Damascus - Mazzeh',
                'categories' => ['Electronics', 'Home & Kitchen'],
            ],
            [
                'name' => 'Style Corner Vendor',
                'phone_number' => '5129914',
                'national_id' => '1000000002',
                'store_name' => 'Style Corner',
                'description' => 'Modern fashion products and accessories.',
                'address' => 'Aleppo - New Aleppo',
                'categories' => ['Fashion', 'Beauty'],
            ],
            [
                'name' => 'Home Smart Vendor',
                'phone_number' => '0911000003',
                'national_id' => '1000000003',
                'store_name' => 'Home Smart',
                'description' => 'Useful products for home and kitchen.',
                'address' => 'Homs - City Center',
                'categories' => ['Home & Kitchen', 'Sports', 'Electronics'],
            ],
        ];

        foreach ($vendors as $vendorData) {
            $user = User::query()->updateOrCreate(
                ['phone_number' => $vendorData['phone_number']],
                [
                    'name' => $vendorData['name'],
                    'national_id' => $vendorData['national_id'],
                    'type' => User::TYPE_VENDOR,
                    'email' => str_replace('0', '', $vendorData['phone_number']).'@msz-demo.test',
                    'password' => 'password',
                ],
            );

            $vendor = Vendor::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $vendorData['store_name'],
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

    /**
     * @return list<string>
     */
    protected function collectSeedImages(): array
    {
        $files = Storage::disk('public')->files('seed');

        return array_values(array_filter($files, fn (string $f) => str_starts_with(basename($f), 'seed-image-')));
    }
}
