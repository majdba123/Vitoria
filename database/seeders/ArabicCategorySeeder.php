<?php

namespace Database\Seeders;

use App\Models\Category;
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
            Category::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'type' => $item['type'],
                    'logo' => $item['type'] === Category::TYPE_AGRICULTURE ? $agricultureLogo : $veterinaryLogo,
                    'icon' => $item['type'] === Category::TYPE_AGRICULTURE ? $agricultureLogo : $veterinaryLogo,
                    'icon_class' => $item['icon_class'],
                    'commission' => $item['commission'],
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, type: string, icon_class: string, commission: float}>
     */
    public static function categories(): array
    {
        return [
            ['name' => 'البذور', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-seedling', 'commission' => 4.5],
            ['name' => 'الأسمدة', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-leaf', 'commission' => 5.0],
            ['name' => 'أنظمة الري', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-droplet', 'commission' => 5.5],
            ['name' => 'البيوت البلاستيكية',  'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-warehouse', 'commission' => 6.0],
            ['name' => 'المعدات الزراعية', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-tractor', 'commission' => 6.5],
            ['name' => 'المبيدات الزراعية',  'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-spray-can-sparkles', 'commission' => 5.25],
            ['name' => 'التربة والسماد العضوي','type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-mound', 'commission' => 4.75],
            ['name' => 'أدوات الحصاد', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-wheat-awn', 'commission' => 5.75],
            ['name' => 'الأدوية البيطرية','type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-prescription-bottle-medical', 'commission' => 6.0],
            ['name' => 'اللقاحات', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-syringe', 'commission' => 6.25],
            ['name' => 'معدات العيادات البيطرية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-stethoscope', 'commission' => 6.5],
            ['name' => 'مكملات الأعلاف','type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-bowl-food', 'commission' => 5.5],
            ['name' => 'أدوات رعاية الحيوانات',  'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-hand-holding-medical', 'commission' => 5.75],
            ['name' => 'مستلزمات المواشي','type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-cow', 'commission' => 5.25],
            ['name' => 'المطهرات البيطرية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-pump-medical', 'commission' => 5.0],
            ['name' => 'خدمات بيطرية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-user-doctor', 'commission' => 7.0],
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
