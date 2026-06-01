<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CategorySubcategorySeeder extends Seeder
{
    /**
     * Font Awesome 6 classes (requires CDN in layouts — see layouts.app).
     *
     * @var array<string, array{commission: float, icon_class: string, subcategories: list<array{name: string, icon_class: string}>}>
     */
    protected array $catalog = [
        'المنتجات البيطرية والحيوانية' => [
            'commission' => 11.00,
            'icon_class' => 'fa-solid fa-heart-pulse',
            'subcategories' => [
                ['name' => 'اللقاحات والأدوية البيطرية', 'icon_class' => 'fa-solid fa-syringe'],
                ['name' => 'الأعلاف والمكملات الغذائية', 'icon_class' => 'fa-solid fa-bone'],
                ['name' => 'العناية والجروح والنظافة', 'icon_class' => 'fa-solid fa-pump-soap'],
                ['name' => 'مستلزمات الأسنان والجراحة', 'icon_class' => 'fa-solid fa-tooth'],
            ],
        ],
        'المنتجات الزراعية' => [
            'commission' => 9.00,
            'icon_class' => 'fa-solid fa-seedling',
            'subcategories' => [
                ['name' => 'البذور والشتلات', 'icon_class' => 'fa-solid fa-leaf'],
                ['name' => 'الأسمدة ومحسنات التربة', 'icon_class' => 'fa-solid fa-flask'],
                ['name' => 'وقاية المحاصيل والمبيدات', 'icon_class' => 'fa-solid fa-bug-slash'],
                ['name' => 'مستلزمات الحصاد وما بعد الحصاد', 'icon_class' => 'fa-solid fa-wheat-awn'],
            ],
        ],
        'المعدات الزراعية' => [
            'commission' => 8.00,
            'icon_class' => 'fa-solid fa-tractor',
            'subcategories' => [
                ['name' => 'الجرارات وآلات الحراثة', 'icon_class' => 'fa-solid fa-tractor'],
                ['name' => 'الري والمضخات', 'icon_class' => 'fa-solid fa-faucet-drip'],
                ['name' => 'الحصاد والمعالجة', 'icon_class' => 'fa-solid fa-gears'],
                ['name' => 'التخزين والمناولة', 'icon_class' => 'fa-solid fa-warehouse'],
            ],
        ],
        'معدات تربية الحيوانات' => [
            'commission' => 10.00,
            'icon_class' => 'fa-solid fa-cow',
            'subcategories' => [
                ['name' => 'الحظائر والأسوار', 'icon_class' => 'fa-solid fa-border-all'],
                ['name' => 'المعالف والمشارب', 'icon_class' => 'fa-solid fa-droplet'],
                ['name' => 'معدات الحلب والألبان', 'icon_class' => 'fa-solid fa-bottle-droplet'],
                ['name' => 'النقل والعناية والتعامل', 'icon_class' => 'fa-solid fa-truck-pickup'],
            ],
        ],
    ];

    public function run(): void
    {
        $allowedNames = array_keys($this->catalog);

        $stale = Category::query()->whereNotIn('name', $allowedNames)->get();
        foreach ($stale as $category) {
            if ($category->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($category->logo);
            }
            if ($category->icon) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($category->icon);
            }
            $category->delete();
        }

        foreach ($this->catalog as $categoryName => $config) {
            $category = Category::query()->updateOrCreate(
                ['name' => $categoryName],
                [
                    'logo' => null,
                    'icon' => null,
                    'icon_class' => $config['icon_class'],
                    'commission' => $config['commission'],
                ],
            );

            foreach ($config['subcategories'] as $sub) {
                Subcategory::query()->updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $sub['name'],
                    ],
                    [
                        'image' => null,
                        'icon_class' => $sub['icon_class'],
                    ],
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
