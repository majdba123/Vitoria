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
        foreach (self::categories() as $item) {
            $logo = $this->storeCategoryAsset($item['image']);

            Category::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'type' => $item['type'],
                    'logo' => $logo,
                    'icon' => $logo,
                    'icon_class' => $item['icon_class'],
                    'commission' => $item['commission'],
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, type: string, icon_class: string, commission: float, image: string}>
     */
    public static function categories(): array
    {
        return [
            ['name' => 'البذور', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-seedling', 'commission' => 4.5, 'image' => 'seeds.webp'],
            ['name' => 'الأسمدة', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-leaf', 'commission' => 5.0, 'image' => 'fertilizer.webp'],
            ['name' => 'أنظمة الري', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-droplet', 'commission' => 5.5, 'image' => 'soil_compost.webp'],
            ['name' => 'البيوت البلاستيكية',  'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-warehouse', 'commission' => 6.0, 'image' => 'greenhouse.webp'],
            ['name' => 'المعدات الزراعية', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-tractor', 'commission' => 6.5, 'image' => 'farm_equipment.webp'],
            ['name' => 'المبيدات الزراعية',  'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-spray-can-sparkles', 'commission' => 5.25, 'image' => 'pesticide_spray.webp'],
            ['name' => 'التربة والسماد العضوي', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-mound', 'commission' => 4.75, 'image' => 'soil_compost.webp'],
            ['name' => 'أدوات الحصاد', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-wheat-awn', 'commission' => 5.75, 'image' => 'tractor_field.webp'],
            ['name' => 'الأدوية البيطرية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-prescription-bottle-medical', 'commission' => 6.0, 'image' => 'vaccine_vial.webp'],
            ['name' => 'اللقاحات', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-syringe', 'commission' => 6.25, 'image' => 'vet_injection.webp'],
            ['name' => 'معدات العيادات البيطرية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-stethoscope', 'commission' => 6.5, 'image' => 'vet_exam.webp'],
            ['name' => 'مكملات الأعلاف', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-bowl-food', 'commission' => 5.5, 'image' => 'feed_supplements.webp'],
            ['name' => 'أدوات رعاية الحيوانات',  'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-hand-holding-medical', 'commission' => 5.75, 'image' => 'livestock_sheep.webp'],
            ['name' => 'مستلزمات المواشي', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-cow', 'commission' => 5.25, 'image' => 'livestock_sheep.webp'],
            ['name' => 'المطهرات البيطرية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-pump-medical', 'commission' => 5.0, 'image' => 'vaccine_vial.webp'],
            ['name' => 'خدمات بيطرية', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-user-doctor', 'commission' => 7.0, 'image' => 'vet_exam.webp'],
        ];
    }

    private function storeCategoryAsset(string $filename): string
    {
        $path = 'demo/categories/'.$filename;
        Storage::disk('public')->makeDirectory('demo/categories');

        $fixture = database_path('seeders/fixtures/categories/'.$filename);
        $destination = Storage::disk('public')->path($path);

        if (File::exists($fixture) && ! File::exists($destination)) {
            File::copy($fixture, $destination);
        }

        return $path;
    }
}
