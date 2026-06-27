<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ArabicProductSeeder extends Seeder
{
    public function run(): void
    {
        $agricultureImage = $this->storeDemoAsset('demo/products/agriculture-product.png');
        $veterinaryImage = $this->storeDemoAsset('demo/products/veterinary-product.png');
        $agricultureIcon = $this->storeDemoAsset('demo/products/icons/agriculture-icon.png');
        $veterinaryIcon = $this->storeDemoAsset('demo/products/icons/veterinary-icon.png');

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
            $icon = $item['type'] === Category::TYPE_AGRICULTURE ? $agricultureIcon : $veterinaryIcon;

            $product = Product::query()->updateOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'name' => $item['name'],
                ],
                [
                    'category_id' => $category->id,
                    'description' => $item['description'],
                    'icon' => $icon,
                    'image' => $image,
                    'price' => $item['price'],
                    'discount_percentage' => $item['discount_percentage'],
                    'quantity' => $item['quantity'],
                    'is_active' => true,
                    'discount_is_active' => $item['discount_percentage'] > 0,
                    'status' => Product::STATUS_APPROVED,
                ],
            );

            ProductPhoto::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sort_order' => 0,
                ],
                [
                    'path' => $image,
                    'is_primary' => true,
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, category: string, type: string, vendor_email: string, description: string, price: float, discount_percentage: float, quantity: int}>
     */
    private function products(): array
    {
               return [
            ['name' => 'بذور قمح عالية الجودة', 'category' => 'البذور', 'type' => Category::TYPE_AGRICULTURE, 'vendor_email' => 'agriculture.vendor@vetora.test', 'description' => 'بذور قمح منتقاة للموسم الزراعي، مناسبة لتحسين الإنتاجية وجودة المحصول.', 'price' => 42.50, 'discount_percentage' => 5.0, 'quantity' => 120],
            ['name' => 'سماد عضوي طبيعي', 'category' => 'الأسمدة', 'type' => Category::TYPE_AGRICULTURE, 'vendor_email' => 'agriculture.vendor@vetora.test', 'description' => 'سماد عضوي غني بالعناصر الأساسية لدعم نمو النباتات وتحسين خصوبة التربة.', 'price' => 28.00, 'discount_percentage' => 0.0, 'quantity' => 90],
            ['name' => 'نظام ري بالتنقيط', 'category' => 'أنظمة الري', 'type' => Category::TYPE_AGRICULTURE, 'vendor_email' => 'agriculture.vendor@vetora.test', 'description' => 'نظام ري عملي يوفر المياه ويوزعها بدقة على المحاصيل والمساحات الزراعية.', 'price' => 180.00, 'discount_percentage' => 7.5, 'quantity' => 35],
            ['name' => 'مضخة مياه زراعية', 'category' => 'المعدات الزراعية', 'type' => Category::TYPE_AGRICULTURE, 'vendor_email' => 'agriculture.vendor@vetora.test', 'description' => 'مضخة مياه متينة للاستخدام الزراعي اليومي في المزارع والحقول.', 'price' => 240.00, 'discount_percentage' => 4.0, 'quantity' => 18],
            ['name' => 'بيت بلاستيكي صغير', 'category' => 'البيوت البلاستيكية', 'type' => Category::TYPE_AGRICULTURE, 'vendor_email' => 'agriculture.vendor@vetora.test', 'description' => 'حل عملي لحماية الشتلات وتنظيم بيئة الزراعة ضمن المساحات الصغيرة والمتوسطة.', 'price' => 520.00, 'discount_percentage' => 6.0, 'quantity' => 10],
            ['name' => 'مبيد آمن للمحاصيل', 'category' => 'المبيدات الزراعية', 'type' => Category::TYPE_AGRICULTURE, 'vendor_email' => 'agriculture.vendor@vetora.test', 'description' => 'مبيد زراعي للاستخدام المسؤول في حماية المحاصيل من الآفات الشائعة.', 'price' => 36.00, 'discount_percentage' => 0.0, 'quantity' => 70],
            ['name' => 'تربة زراعية محسنة', 'category' => 'التربة والسماد العضوي', 'type' => Category::TYPE_AGRICULTURE, 'vendor_email' => 'agriculture.vendor@vetora.test', 'description' => 'خليط تربة محسّن لدعم الإنبات السريع ونمو الجذور بصورة صحية.', 'price' => 22.00, 'discount_percentage' => 3.0, 'quantity' => 150],
            ['name' => 'منجل حصاد احترافي', 'category' => 'أدوات الحصاد', 'type' => Category::TYPE_AGRICULTURE, 'vendor_email' => 'agriculture.vendor@vetora.test', 'description' => 'أداة حصاد يدوية بجودة عالية للاستخدام اليومي في الحقول والمزارع.', 'price' => 18.00, 'discount_percentage' => 0.0, 'quantity' => 85],
            ['name' => 'لقاح للأغنام', 'category' => 'اللقاحات', 'type' => Category::TYPE_VETERINARY, 'vendor_email' => 'veterinary.vendor@vetora.test', 'description' => 'لقاح بيطري مخصص لدعم برامج التحصين وحماية قطعان الأغنام.', 'price' => 32.00, 'discount_percentage' => 2.5, 'quantity' => 110],
            ['name' => 'مضاد حيوي بيطري', 'category' => 'الأدوية البيطرية', 'type' => Category::TYPE_VETERINARY, 'vendor_email' => 'veterinary.vendor@vetora.test', 'description' => 'منتج بيطري للاستخدام وفق إرشادات الطبيب المختص وبرامج الرعاية الصحية.', 'price' => 46.00, 'discount_percentage' => 0.0, 'quantity' => 65],
            ['name' => 'مكمل أعلاف للماشية', 'category' => 'مكملات الأعلاف', 'type' => Category::TYPE_VETERINARY, 'vendor_email' => 'veterinary.vendor@vetora.test', 'description' => 'مكمل غذائي يساعد على دعم صحة المواشي وتحسين الاستفادة من الأعلاف.', 'price' => 38.00, 'discount_percentage' => 4.0, 'quantity' => 95],
            ['name' => 'قفازات طبية بيطرية', 'category' => 'أدوات رعاية الحيوانات', 'type' => Category::TYPE_VETERINARY, 'vendor_email' => 'veterinary.vendor@vetora.test', 'description' => 'قفازات طبية مناسبة للعيادات البيطرية وأعمال الفحص والرعاية اليومية.', 'price' => 14.00, 'discount_percentage' => 0.0, 'quantity' => 200],
            ['name' => 'مطهر عيادات بيطرية', 'category' => 'المطهرات البيطرية', 'type' => Category::TYPE_VETERINARY, 'vendor_email' => 'veterinary.vendor@vetora.test', 'description' => 'مطهر عملي للحفاظ على نظافة العيادات ومناطق رعاية الحيوانات.', 'price' => 26.00, 'discount_percentage' => 3.5, 'quantity' => 80],
            ['name' => 'جهاز قياس حرارة للحيوانات', 'category' => 'معدات العيادات البيطرية', 'type' => Category::TYPE_VETERINARY, 'vendor_email' => 'veterinary.vendor@vetora.test', 'description' => 'جهاز قياس حرارة سريع ومناسب للاستخدام البيطري الميداني والعيادي.', 'price' => 58.00, 'discount_percentage' => 0.0, 'quantity' => 45],
            ['name' => 'أدوات فحص بيطرية', 'category' => 'معدات العيادات البيطرية', 'type' => Category::TYPE_VETERINARY, 'vendor_email' => 'veterinary.vendor@vetora.test', 'description' => 'مجموعة أدوات فحص أساسية للعيادات البيطرية وخدمات الرعاية الميدانية.', 'price' => 125.00, 'discount_percentage' => 5.0, 'quantity' => 24],
            ['name' => 'غذاء علاجي للحيوانات', 'category' => 'مستلزمات المواشي', 'type' => Category::TYPE_VETERINARY, 'vendor_email' => 'veterinary.vendor@vetora.test', 'description' => 'غذاء علاجي داعم للحيوانات ضمن برامج التغذية والرعاية البيطرية.', 'price' => 44.00, 'discount_percentage' => 2.0, 'quantity' => 75],
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
