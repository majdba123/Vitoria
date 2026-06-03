<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ArabicVendorSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->vendors() as $item) {
            $city = City::query()->firstOrCreate(['name' => $item['city']]);

            $user = User::query()->updateOrCreate(
                ['phone_number' => $item['phone']],
                [
                    'name' => $item['store_name'],
                    'national_id' => $item['national_id'],
                    'age' => 34,
                    'membership_number' => $item['membership_number'],
                    'city_id' => $city->id,
                    'timezone' => 'Asia/Damascus',
                    'type' => User::TYPE_VENDOR,
                    'email' => $item['email'],
                    'password' => 'password',
                ],
            );

            $vendor = Vendor::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $item['store_name'],
                    'business_type' => $item['business_type'],
                    'description' => $item['description'],
                    'address' => $item['address'],
                    'city_id' => $city->id,
                    'logo' => $item['logo'],
                    'is_active' => true,
                    'status' => Vendor::STATUS_ACTIVE,
                    'registration_source' => Vendor::REGISTRATION_SOURCE_ADMIN,
                    'paid_amount' => 0,
                ],
            );

            $categoryIds = Category::query()
                ->where('type', $item['business_type'])
                ->pluck('id')
                ->all();

            $vendor->categories()->sync($categoryIds);
        }
    }

    /**
     * @return list<array{store_name: string, email: string, phone: string, national_id: string, membership_number: string, business_type: string, city: string, address: string, description: string, logo: string}>
     */
    private function vendors(): array
    {
        return [
            [
                'store_name' => 'تاجر المستلزمات الزراعية',
                'email' => 'agriculture.vendor@vetora.test',
                'phone' => '0935002001',
                'national_id' => '3000001001',
                'membership_number' => 'VEN-AGRI-001',
                'business_type' => Category::TYPE_AGRICULTURE,
                'city' => 'دمشق',
                'address' => 'دمشق - سوق المستلزمات الزراعية',
                'description' => 'متجر متخصص بتوريد البذور والأسمدة وأنظمة الري والمعدات الزراعية للمزارعين.',
                'logo' => 'demo/categories/agriculture.png',
            ],
            [
                'store_name' => 'تاجر المستلزمات البيطرية',
                'email' => 'veterinary.vendor@vetora.test',
                'phone' => '0935002002',
                'national_id' => '3000001002',
                'membership_number' => 'VEN-VET-001',
                'business_type' => Category::TYPE_VETERINARY,
                'city' => 'حماة',
                'address' => 'حماة - سوق المستلزمات البيطرية',
                'description' => 'متجر متخصص بالأدوية واللقاحات ومستلزمات العيادات ورعاية الثروة الحيوانية.',
                'logo' => 'demo/categories/veterinary.png',
            ],
        ];
    }
}
