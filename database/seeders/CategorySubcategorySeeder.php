<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CategorySubcategorySeeder extends Seeder
{
    /**
     * @var array<string, array{commission: float, subcategories: list<string>}>
     */
    protected array $catalog = [
        'Electronics' => [
            'commission' => 8.00,
            'subcategories' => ['Smartphones', 'Laptops', 'Headphones', 'Accessories'],
        ],
        'Fashion' => [
            'commission' => 12.00,
            'subcategories' => ['Men Clothing', 'Women Clothing', 'Shoes', 'Bags'],
        ],
        'Home & Kitchen' => [
            'commission' => 10.00,
            'subcategories' => ['Kitchen Tools', 'Furniture', 'Decor', 'Storage'],
        ],
        'Beauty' => [
            'commission' => 15.00,
            'subcategories' => ['Skincare', 'Hair Care', 'Makeup', 'Perfume'],
        ],
        'Sports' => [
            'commission' => 9.50,
            'subcategories' => ['Fitness', 'Outdoor', 'Cycling', 'Team Sports'],
        ],
    ];

    /**
     * @var list<string>
     */
    protected array $imageUrls = [
        'https://png.pngtree.com/png-vector/20210602/ourmid/pngtree-3d-beauty-cosmetics-product-design-png-image_3350323.jpg',
        'https://cdn.prod.website-files.com/68943e66eaa53340cd489406/68be85d9f1c5d4f164d720b0_6735ebbc0a7dec8625bf45ff_8_Creative_Product_Photography_Ideas_You_Need_to_Try.webp',
    ];

    public function run(): void
    {
        Storage::disk('public')->makeDirectory('categories');
        Storage::disk('public')->makeDirectory('subcategories');

        $downloadedImages = $this->downloadImages();

        $index = 0;
        foreach ($this->catalog as $categoryName => $config) {
            $logoPath = count($downloadedImages) > 0
                ? $downloadedImages[$index % count($downloadedImages)]
                : null;
            $categoryLogoPath = null;

            if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
                $categoryLogoPath = 'categories/'.str()->slug($categoryName).'.'.$ext;
                Storage::disk('public')->copy($logoPath, $categoryLogoPath);
            }

            $category = Category::query()->updateOrCreate(
                ['name' => $categoryName],
                [
                    'logo' => $categoryLogoPath,
                    'commission' => $config['commission'],
                ],
            );

            foreach ($config['subcategories'] as $subcategoryName) {
                $subImagePath = null;
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $subImagePath = 'subcategories/'.str()->slug($subcategoryName).'.'.$ext;
                    Storage::disk('public')->copy($logoPath, $subImagePath);
                }

                Subcategory::query()->updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $subcategoryName,
                    ],
                    ['image' => $subImagePath],
                );
            }

            $index++;
        }
    }

    /**
     * @return list<string>
     */
    protected function downloadImages(): array
    {
        $paths = [];
        foreach ($this->imageUrls as $i => $url) {
            try {
                $response = Http::withoutVerifying()->timeout(15)->get($url);
                if ($response->successful()) {
                    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = "seed/seed-image-{$i}.{$ext}";
                    Storage::disk('public')->put($filename, $response->body());
                    $paths[] = $filename;
                }
            } catch (\Throwable $e) {
                $this->command?->warn("Could not download image {$i}: {$e->getMessage()}");
            }
        }

        return $paths;
    }
}
