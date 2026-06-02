<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CategorySubcategorySeeder extends Seeder
{
    /**
     * @var array<string, array{type: string, commission: float, icon_class: string, subcategories: list<array{name: string, icon_class: string}>}>
     */
    protected array $catalog = [
        'Seeds' => [
            'type' => Category::TYPE_AGRICULTURE,
            'commission' => 8.00,
            'icon_class' => 'fa-solid fa-seedling',
            'subcategories' => [
                ['name' => 'Field Seeds', 'icon_class' => 'fa-solid fa-wheat-awn'],
                ['name' => 'Vegetable Seeds', 'icon_class' => 'fa-solid fa-carrot'],
                ['name' => 'Seedlings', 'icon_class' => 'fa-solid fa-leaf'],
            ],
        ],
        'Fertilizers' => [
            'type' => Category::TYPE_AGRICULTURE,
            'commission' => 9.00,
            'icon_class' => 'fa-solid fa-flask',
            'subcategories' => [
                ['name' => 'Organic Fertilizers', 'icon_class' => 'fa-solid fa-recycle'],
                ['name' => 'Soil Enhancers', 'icon_class' => 'fa-solid fa-mountain-sun'],
                ['name' => 'Liquid Nutrients', 'icon_class' => 'fa-solid fa-droplet'],
            ],
        ],
        'Irrigation' => [
            'type' => Category::TYPE_AGRICULTURE,
            'commission' => 8.50,
            'icon_class' => 'fa-solid fa-faucet-drip',
            'subcategories' => [
                ['name' => 'Drip Irrigation', 'icon_class' => 'fa-solid fa-water'],
                ['name' => 'Pumps', 'icon_class' => 'fa-solid fa-gauge-high'],
                ['name' => 'Pipes and Fittings', 'icon_class' => 'fa-solid fa-grip-lines'],
            ],
        ],
        'Greenhouses' => [
            'type' => Category::TYPE_AGRICULTURE,
            'commission' => 10.00,
            'icon_class' => 'fa-solid fa-house-chimney-window',
            'subcategories' => [
                ['name' => 'Greenhouse Covers', 'icon_class' => 'fa-solid fa-layer-group'],
                ['name' => 'Climate Control', 'icon_class' => 'fa-solid fa-temperature-half'],
                ['name' => 'Frames and Structures', 'icon_class' => 'fa-solid fa-warehouse'],
            ],
        ],
        'Agricultural Equipment' => [
            'type' => Category::TYPE_AGRICULTURE,
            'commission' => 7.50,
            'icon_class' => 'fa-solid fa-tractor',
            'subcategories' => [
                ['name' => 'Tractors', 'icon_class' => 'fa-solid fa-tractor'],
                ['name' => 'Harvesting Tools', 'icon_class' => 'fa-solid fa-gears'],
                ['name' => 'Hand Tools', 'icon_class' => 'fa-solid fa-screwdriver-wrench'],
            ],
        ],
        'Animal Medicine' => [
            'type' => Category::TYPE_VETERINARY,
            'commission' => 11.00,
            'icon_class' => 'fa-solid fa-kit-medical',
            'subcategories' => [
                ['name' => 'Antibiotics', 'icon_class' => 'fa-solid fa-capsules'],
                ['name' => 'Pain Relief', 'icon_class' => 'fa-solid fa-tablets'],
                ['name' => 'Wound Care', 'icon_class' => 'fa-solid fa-bandage'],
            ],
        ],
        'Vaccines' => [
            'type' => Category::TYPE_VETERINARY,
            'commission' => 12.00,
            'icon_class' => 'fa-solid fa-syringe',
            'subcategories' => [
                ['name' => 'Livestock Vaccines', 'icon_class' => 'fa-solid fa-cow'],
                ['name' => 'Poultry Vaccines', 'icon_class' => 'fa-solid fa-kiwi-bird'],
                ['name' => 'Cold Chain Supplies', 'icon_class' => 'fa-solid fa-snowflake'],
            ],
        ],
        'Livestock Equipment' => [
            'type' => Category::TYPE_VETERINARY,
            'commission' => 9.50,
            'icon_class' => 'fa-solid fa-cow',
            'subcategories' => [
                ['name' => 'Feeding Systems', 'icon_class' => 'fa-solid fa-bowl-food'],
                ['name' => 'Milking Equipment', 'icon_class' => 'fa-solid fa-bottle-droplet'],
                ['name' => 'Housing and Fencing', 'icon_class' => 'fa-solid fa-border-all'],
            ],
        ],
        'Feed Supplements' => [
            'type' => Category::TYPE_VETERINARY,
            'commission' => 10.00,
            'icon_class' => 'fa-solid fa-bone',
            'subcategories' => [
                ['name' => 'Minerals', 'icon_class' => 'fa-solid fa-gem'],
                ['name' => 'Vitamins', 'icon_class' => 'fa-solid fa-pills'],
                ['name' => 'Performance Supplements', 'icon_class' => 'fa-solid fa-chart-line'],
            ],
        ],
        'Veterinary Services' => [
            'type' => Category::TYPE_VETERINARY,
            'commission' => 13.00,
            'icon_class' => 'fa-solid fa-user-doctor',
            'subcategories' => [
                ['name' => 'Clinic Supplies', 'icon_class' => 'fa-solid fa-stethoscope'],
                ['name' => 'Diagnostics', 'icon_class' => 'fa-solid fa-microscope'],
                ['name' => 'Surgical Supplies', 'icon_class' => 'fa-solid fa-scissors'],
            ],
        ],
    ];

    public function run(): void
    {
        $allowedNames = array_keys($this->catalog);

        $stale = Category::query()->whereNotIn('name', $allowedNames)->get();
        foreach ($stale as $category) {
            foreach (['logo', 'icon'] as $field) {
                if ($category->{$field}) {
                    Storage::disk('public')->delete($category->{$field});
                }
            }
            $category->delete();
        }

        foreach ($this->catalog as $categoryName => $config) {
            $category = Category::query()->updateOrCreate(
                ['name' => $categoryName],
                [
                    'type' => $config['type'],
                    'logo' => null,
                    'icon' => null,
                    'icon_class' => $config['icon_class'],
                    'commission' => $config['commission'],
                ],
            );

            foreach ($config['subcategories'] as $sub) {
                Subcategory::query()->updateOrCreate(
                    ['category_id' => $category->id, 'name' => $sub['name']],
                    ['image' => null, 'icon_class' => $sub['icon_class']],
                );
            }
        }

        try {
            Cache::tags(['categories'])->flush();
        } catch (\Throwable) {
            // Cache store may not support tags.
        }
    }
}
