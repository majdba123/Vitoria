<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ArabicCategorySeeder extends Seeder
{
    public function run(): void
    {
        $agricultureLogo = $this->storeDemoAsset('demo/categories/agriculture.png');
        $veterinaryLogo = $this->storeDemoAsset('demo/categories/veterinary.png');

        foreach (self::categories() as $item) {
            $category = Category::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'type' => $item['type'],
                    'logo' => $item['type'] === Category::TYPE_AGRICULTURE ? $agricultureLogo : $veterinaryLogo,
                    'icon' => $item['type'] === Category::TYPE_AGRICULTURE ? $agricultureLogo : $veterinaryLogo,
                    'icon_class' => $item['icon_class'],
                    'commission' => $item['commission'],
                ],
            );

            Subcategory::query()->updateOrCreate(
                [
                    'category_id' => $category->id,
                    'name' => $item['subcategory'],
                ],
                [
                    'image' => $category->logo,
                    'icon_class' => $item['icon_class'],
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, subcategory: string, type: string, icon_class: string, commission: float}>
     */
    public static function categories(): array
    {
        return [
            ['name' => 'البذور', 'subcategory' => 'بذور الحبوب', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-seedling', 'commission' => 4.5],
            ['name' => 'الأسمدة', 'subcategory' => 'أسمدة عضوية', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-leaf', 'commission' => 5.0],
            ['name' => 'أنظمة الري', 'subcategory' => 'الري بالتنقيط', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-droplet', 'commission' => 5.5],
            ['name' => 'البيوت البلاستيكية', 'subcategory' => 'مستلزمات البيوت البلاستيكية', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-warehouse', 'commission' => 6.0],
            ['name' => 'المعدات الزراعية', 'subcategory' => 'معدات المزارع', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-tractor', 'commission' => 6.5],
            ['name' => 'المبيدات الزراعية', 'subcategory' => 'مبيدات آمنة', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-spray-can-sparkles', 'commission' => 5.25],
            ['name' => 'التربة والسماد العضوي', 'subcategory' => 'محسنات التربة', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-mound', 'commission' => 4.75],
            ['name' => 'أدوات الحصاد', 'subcategory' => 'أدوات يدوية للحصاد', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-wheat-awn', 'commission' => 5.75],
            ['name' => 'الأدوية البيطرية', 'subcategory' => 'علاجات بيطرية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-prescription-bottle-medical', 'commission' => 6.0],
            ['name' => 'اللقاحات', 'subcategory' => 'لقاحات المواشي', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-syringe', 'commission' => 6.25],
            ['name' => 'معدات العيادات البيطرية', 'subcategory' => 'تجهيزات الفحص', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-stethoscope', 'commission' => 6.5],
            ['name' => 'مكملات الأعلاف', 'subcategory' => 'مكملات المواشي', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-bowl-food', 'commission' => 5.5],
            ['name' => 'أدوات رعاية الحيوانات', 'subcategory' => 'أدوات العناية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-hand-holding-medical', 'commission' => 5.75],
            ['name' => 'مستلزمات المواشي', 'subcategory' => 'مستلزمات الحظائر', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-cow', 'commission' => 5.25],
            ['name' => 'المطهرات البيطرية', 'subcategory' => 'مطهرات العيادات', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-pump-medical', 'commission' => 5.0],
            ['name' => 'خدمات بيطرية', 'subcategory' => 'خدمات الرعاية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-user-doctor', 'commission' => 7.0],
        ];
    }

    private function storeDemoAsset(string $path): string
    {
        Storage::disk('public')->makeDirectory(dirname($path));

        $fixture = database_path('seeders/fixtures/default-product.png');
        $destination = Storage::disk('public')->path($path);

        if (File::exists($fixture) && ! File::exists($destination)) {
            File::copy($fixture, $destination);
        }

        return $path;
    }
}
