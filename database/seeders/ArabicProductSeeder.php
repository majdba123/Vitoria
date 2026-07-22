<?php

namespace Database\Seeders;

use App\Models\AgriculturalProductDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\SharedProductDetail;
use App\Models\Subcategory;
use App\Models\Vendor;
use App\Models\VeterinaryProductDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ArabicProductSeeder extends Seeder
{
    public function run(): void
    {
        $agricultureImage = $this->storeDemoAsset('demo/products/agriculture-product.png');
        $veterinaryImage = $this->storeDemoAsset('demo/products/veterinary-product.png');

        foreach ($this->products() as $item) {
            $category = Category::query()
                ->where('name', $item['category'])
                ->where('type', $item['type'])
                ->firstOrFail();

            $vendor = Vendor::query()
                ->where('business_type', $item['type'])
                ->whereHas('user', fn ($query) => $query->where('email', $item['vendor_email']))
                ->firstOrFail();
            $image = $item['type'] === Category::TYPE_AGRICULTURE ? $agricultureImage : $veterinaryImage;

            $product = Product::query()->updateOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'name' => $item['name'],
                ],
                [
                    'category_id' => $category->id,
                    'subcategory_id' => $this->resolveSubcategoryId($category->id, $item['subcategory'] ?? null),
                    'name_ar' => $item['name_ar'] ?? $item['name'],
                    'name_en' => $item['name_en'] ?? $item['name'],
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'discount_percentage' => $item['discount_percentage'],
                    'quantity' => $item['quantity'],
                    'is_active' => true,
                    'discount_is_active' => $item['discount_percentage'] > 0,
                    'status' => Product::STATUS_APPROVED,
                ],
            );

            $sharedDetail = SharedProductDetail::query()->updateOrCreate(
                ['product_id' => $product->id],
                $item['shared_detail'],
            );

            if ($item['type'] === Category::TYPE_AGRICULTURE) {
                AgriculturalProductDetail::query()->updateOrCreate(
                    ['shared_product_detail_id' => $sharedDetail->id],
                    $item['agricultural_detail'],
                );

                VeterinaryProductDetail::query()
                    ->where('shared_product_detail_id', $sharedDetail->id)
                    ->delete();
            }

            if ($item['type'] === Category::TYPE_VETERINARY) {
                VeterinaryProductDetail::query()->updateOrCreate(
                    ['shared_product_detail_id' => $sharedDetail->id],
                    $item['veterinary_detail'],
                );

                AgriculturalProductDetail::query()
                    ->where('shared_product_detail_id', $sharedDetail->id)
                    ->delete();
            }

            ProductPhoto::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sort_order' => 1,
                ],
                [
                    'path' => $image,
                    'image_type' => ProductPhoto::TYPE_FRONT,
                    'is_primary' => true,
                ],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'name' => 'بذور قمح عالية الجودة',
                'name_ar' => 'بذور قمح عالية الجودة',
                'name_en' => 'Premium Wheat Seeds',
                'category' => 'البذور',
                'subcategory' => 'بذور قمح',
                'type' => Category::TYPE_AGRICULTURE,
                'vendor_email' => 'agriculture.vendor@vetora.test',
                'description' => 'بذور قمح منتقاة للموسم الزراعي ومناسبة للإنتاجية العالية.',
                'price' => 42.50,
                'discount_percentage' => 5.0,
                'quantity' => 120,
                'shared_detail' => [
                    'commercial_name' => 'Golden Wheat Pro',
                    'barcodes' => ['SEED-WHEAT-001'],
                    'manufacturer_name_ar' => 'الشركة السورية للبذور',
                    'manufacturer_name_en' => 'Syrian Seeds Co.',
                    'brand_name_ar' => 'حصاد',
                    'brand_name_en' => 'Hasad',
                    'country_of_origin' => 'Syria',
                    'registration_number' => 'AG-SE-1001',
                    'registration_status' => 'registered',
                    'package_size' => 25,
                    'package_unit' => 'kg',
                    'short_description' => 'بذور قمح معتمدة للزراعة الحقلية.',
                    'approved_description' => 'مناسبة للزراعة الحقلية في المناطق المعتدلة.',
                    'keywords' => ['قمح', 'بذور', 'زراعة'],
                ],
                'agricultural_detail' => [
                    'agricultural_product_type' => 'seed',
                    'crop_name_ar' => 'قمح',
                    'crop_name_en' => 'Wheat',
                    'variety_name' => 'شام 8',
                    'variety_type' => 'Field Crop',
                    'germination_percent' => 96,
                    'purity_percent' => 99,
                    'seed_treatment' => ['معالجة فطرية أساسية'],
                    'disease_resistance' => ['تحمل متوسط للصدأ الأصفر'],
                    'planting_windows' => ['منتصف تشرين الثاني - منتصف كانون الأول'],
                    'seeding_rate' => ['180 كغ/هكتار'],
                    'planting_depth' => ['3-5 سم'],
                    'plant_spacing' => ['سطور متقاربة'],
                    'maturity_days' => '145',
                    'expected_yield' => ['4.5 طن/هكتار'],
                ],
            ],
            [
                'name' => 'سماد عضوي طبيعي',
                'name_ar' => 'سماد عضوي طبيعي',
                'name_en' => 'Natural Organic Fertilizer',
                'category' => 'الأسمدة',
                'subcategory' => 'أسمدة عضوية',
                'type' => Category::TYPE_AGRICULTURE,
                'vendor_email' => 'agriculture.vendor@vetora.test',
                'description' => 'سماد عضوي غني بالعناصر الأساسية لتحسين خصوبة التربة.',
                'price' => 28.00,
                'discount_percentage' => 0.0,
                'quantity' => 90,
                'shared_detail' => [
                    'commercial_name' => 'Bio Soil Boost',
                    'barcodes' => ['FERT-ORG-001'],
                    'manufacturer_name_ar' => 'شركة التربة الخضراء',
                    'manufacturer_name_en' => 'Green Soil Company',
                    'brand_name_ar' => 'نمو',
                    'brand_name_en' => 'Nomo',
                    'country_of_origin' => 'Syria',
                    'registration_number' => 'AG-FE-2040',
                    'registration_status' => 'registered',
                    'package_size' => 50,
                    'package_unit' => 'kg',
                    'short_description' => 'سماد عضوي للتربة والخضار.',
                    'approved_description' => 'يرفع المادة العضوية ويحسن النشاط الحيوي في التربة.',
                    'keywords' => ['سماد', 'عضوي', 'تربة'],
                ],
                'agricultural_detail' => [
                    'agricultural_product_type' => 'fertilizer',
                    'fertilizer_type' => 'organic',
                    'nutrient_n_percent' => 4,
                    'nutrient_p_percent' => 3,
                    'nutrient_k_percent' => 2,
                    'organic_matter_percent' => 65,
                    'application_methods' => ['نثري', 'مع مياه الري'],
                    'growth_stages' => ['قبل الزراعة', 'مرحلة النمو الخضري'],
                    'fertilization_methods' => ['أرضي'],
                    'micronutrients' => ['حديد', 'زنك'],
                ],
            ],
            [
                'name' => 'مبيد آمن للمحاصيل',
                'name_ar' => 'مبيد آمن للمحاصيل',
                'name_en' => 'Safe Crop Insecticide',
                'category' => 'المبيدات الزراعية',
                'subcategory' => 'مبيدات حشرية',
                'type' => Category::TYPE_AGRICULTURE,
                'vendor_email' => 'agriculture.vendor@vetora.test',
                'description' => 'مبيد زراعي لحماية المحاصيل من الآفات الشائعة.',
                'price' => 36.00,
                'discount_percentage' => 0.0,
                'quantity' => 70,
                'shared_detail' => [
                    'commercial_name' => 'Crop Shield 25 EC',
                    'barcodes' => ['PEST-INS-001'],
                    'manufacturer_name_ar' => 'شركة الحماية الزراعية',
                    'manufacturer_name_en' => 'Crop Protection Co.',
                    'brand_name_ar' => 'درع',
                    'brand_name_en' => 'Dir3',
                    'country_of_origin' => 'Jordan',
                    'registration_number' => 'AG-PE-3010',
                    'registration_status' => 'registered',
                    'package_size' => 1,
                    'package_unit' => 'L',
                    'short_description' => 'مبيد حشري جهازي للمحاصيل الحقلية.',
                    'approved_description' => 'يستخدم ضمن برنامج المكافحة المتكاملة.',
                    'keywords' => ['مبيد', 'حشري', 'محاصيل'],
                ],
                'agricultural_detail' => [
                    'agricultural_product_type' => 'pesticide',
                    'formulation' => 'EC',
                    'pesticide_type' => 'Insecticide',
                    'chemical_group' => 'Pyrethroid',
                    'active_ingredients' => ['Lambda-cyhalothrin'],
                    'target_crops' => ['قطن', 'بندورة'],
                    'target_pests' => ['ذبابة بيضاء', 'حشرات ماصة'],
                    'application_methods' => ['رش ورقي'],
                    'application_rates' => ['150 مل/100 لتر'],
                    'warnings' => ['يحفظ بعيدًا عن الأطفال'],
                    'pre_harvest_intervals' => ['7 أيام'],
                    'toxicity_class' => 'II',
                ],
            ],
            [
                'name' => 'لقاح للأغنام',
                'name_ar' => 'لقاح للأغنام',
                'name_en' => 'Sheep Vaccine',
                'category' => 'اللقاحات',
                'subcategory' => 'لقاحات أغنام',
                'type' => Category::TYPE_VETERINARY,
                'vendor_email' => 'veterinary.vendor@vetora.test',
                'description' => 'لقاح بيطري مخصص لدعم برامج التحصين للأغنام.',
                'price' => 32.00,
                'discount_percentage' => 2.5,
                'quantity' => 110,
                'shared_detail' => [
                    'commercial_name' => 'Sheep Guard Vaccine',
                    'barcodes' => ['VET-VAC-001'],
                    'manufacturer_name_ar' => 'مختبرات اللقاح البيطري',
                    'manufacturer_name_en' => 'Vet Vaccine Labs',
                    'brand_name_ar' => 'مناعة',
                    'brand_name_en' => 'Manaa',
                    'country_of_origin' => 'Turkey',
                    'registration_number' => 'VT-VA-1120',
                    'registration_status' => 'registered',
                    'package_size' => 100,
                    'package_unit' => 'ml',
                    'short_description' => 'لقاح بيطري للأغنام.',
                    'approved_description' => 'يعطى ضمن برنامج التحصين الدوري المعتمد.',
                    'keywords' => ['لقاح', 'أغنام', 'بيطري'],
                ],
                'veterinary_detail' => [
                    'concentration' => '10^6',
                    'dosage_form' => 'injection',
                    'routes_of_administration' => ['subcutaneous'],
                    'target_species' => ['Sheep'],
                    'indications' => ['التحصين الوقائي الموسمي'],
                    'storage_conditions' => ['2-8°C'],
                    'warnings' => ['للاستخدام البيطري فقط'],
                ],
            ],
            [
                'name' => 'مضاد حيوي بيطري',
                'name_ar' => 'مضاد حيوي بيطري',
                'name_en' => 'Veterinary Antibiotic',
                'category' => 'الأدوية البيطرية',
                'subcategory' => 'مضادات حيوية',
                'type' => Category::TYPE_VETERINARY,
                'vendor_email' => 'veterinary.vendor@vetora.test',
                'description' => 'منتج بيطري واسع الطيف للاستخدام تحت إشراف الطبيب.',
                'price' => 46.00,
                'discount_percentage' => 0.0,
                'quantity' => 65,
                'shared_detail' => [
                    'commercial_name' => 'Vet Cure 20%',
                    'barcodes' => ['VET-ANT-001'],
                    'manufacturer_name_ar' => 'شركة الأدوية البيطرية المتحدة',
                    'manufacturer_name_en' => 'United Vet Pharma',
                    'brand_name_ar' => 'فيت كيور',
                    'brand_name_en' => 'Vet Cure',
                    'country_of_origin' => 'Syria',
                    'registration_number' => 'VT-MD-2201',
                    'registration_status' => 'registered',
                    'package_size' => 100,
                    'package_unit' => 'ml',
                    'short_description' => 'مضاد حيوي بيطري واسع الطيف.',
                    'approved_description' => 'يستخدم تبعًا لتشخيص الطبيب البيطري.',
                    'keywords' => ['مضاد حيوي', 'أبقار', 'بيطري'],
                ],
                'veterinary_detail' => [
                    'concentration' => '20%',
                    'dosage_form' => 'solution',
                    'routes_of_administration' => ['intramuscular'],
                    'target_species' => ['Cattle', 'Sheep'],
                    'dosage_instructions' => ['1 ml / 10 kg'],
                    'contraindications' => ['لا يستخدم عند الحساسية للمادة الفعالة'],
                    'withdrawal_meat_days' => 7,
                    'withdrawal_milk_days' => 3,
                    'storage_conditions' => ['يحفظ في مكان بارد وجاف'],
                ],
            ],
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

    private function resolveSubcategoryId(int $categoryId, ?string $subcategoryName): ?int
    {
        if (! $subcategoryName) {
            return null;
        }

        return Subcategory::query()
            ->where('category_id', $categoryId)
            ->where('name_ar', $subcategoryName)
            ->value('id');
    }
}
