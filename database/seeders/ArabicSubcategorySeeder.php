<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class ArabicSubcategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::subcategories() as $categoryName => $items) {
            $category = Category::query()->where('name', $categoryName)->first();

            if (! $category) {
                continue;
            }

            foreach ($items as $item) {
                Subcategory::query()->updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name_ar' => $item['name_ar'],
                    ],
                    [
                        'name_en' => $item['name_en'],
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, list<array{name_ar: string, name_en: string}>>
     */
    public static function subcategories(): array
    {
        return [
            'البذور' => [
                ['name_ar' => 'بذور قمح', 'name_en' => 'Wheat Seeds'],
                ['name_ar' => 'بذور شعير', 'name_en' => 'Barley Seeds'],
                ['name_ar' => 'بذور خضار', 'name_en' => 'Vegetable Seeds'],
            ],
            'الأسمدة' => [
                ['name_ar' => 'أسمدة عضوية', 'name_en' => 'Organic Fertilizers'],
                ['name_ar' => 'أسمدة مركبة', 'name_en' => 'Compound Fertilizers'],
                ['name_ar' => 'أسمدة ورقية', 'name_en' => 'Foliar Fertilizers'],
            ],
            'المبيدات الزراعية' => [
                ['name_ar' => 'مبيدات حشرية', 'name_en' => 'Insecticides'],
                ['name_ar' => 'مبيدات فطرية', 'name_en' => 'Fungicides'],
                ['name_ar' => 'مبيدات أعشاب', 'name_en' => 'Herbicides'],
            ],
            'التربة والسماد العضوي' => [
                ['name_ar' => 'محسنات تربة', 'name_en' => 'Soil Amendments'],
                ['name_ar' => 'كمبوست', 'name_en' => 'Compost'],
            ],
            'الأدوية البيطرية' => [
                ['name_ar' => 'مضادات حيوية', 'name_en' => 'Antibiotics'],
                ['name_ar' => 'مضادات التهاب', 'name_en' => 'Anti-inflammatories'],
                ['name_ar' => 'محاليل حقن', 'name_en' => 'Injectable Solutions'],
            ],
            'اللقاحات' => [
                ['name_ar' => 'لقاحات أغنام', 'name_en' => 'Sheep Vaccines'],
                ['name_ar' => 'لقاحات أبقار', 'name_en' => 'Cattle Vaccines'],
                ['name_ar' => 'لقاحات دواجن', 'name_en' => 'Poultry Vaccines'],
            ],
            'معدات العيادات البيطرية' => [
                ['name_ar' => 'أجهزة قياس', 'name_en' => 'Measuring Devices'],
                ['name_ar' => 'أدوات فحص', 'name_en' => 'Examination Tools'],
            ],
            'مستلزمات المواشي' => [
                ['name_ar' => 'أغذية علاجية', 'name_en' => 'Therapeutic Feed'],
                ['name_ar' => 'مستلزمات إسطبل', 'name_en' => 'Stable Supplies'],
            ],
        ];
    }
}
