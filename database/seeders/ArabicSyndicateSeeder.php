<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Syndicate;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArabicSyndicateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->syndicates() as $item) {
            $user = User::query()->updateOrCreate(
                ['phone_number' => $item['phone']],
                [
                    'name' => $item['name'],
                    'national_id' => $item['national_id'],
                    'age' => 36,
                    'membership_number' => $item['membership_number'],
                    'type' => User::TYPE_SYNDICATE,
                    'email' => $item['email'],
                    'timezone' => 'Asia/Damascus',
                    'password' => 'password',
                ],
            );

            Syndicate::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'phone' => $item['phone'],
                    'email' => $item['email'],
                    'status' => Syndicate::STATUS_ACTIVE,
                    'logo' => $item['logo'],
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, email: string, phone: string, national_id: string, membership_number: string, type: string, logo: string}>
     */
    private function syndicates(): array
    {
        return [
            [
                'name' => 'النقابة الزراعية',
                'email' => 'agriculture.syndicate@vetora.test',
                'phone' => '0935001001',
                'national_id' => '3000000001',
                'membership_number' => 'SYN-AGRI-001',
                'type' => Category::TYPE_AGRICULTURE,
                'logo' => 'demo/categories/agriculture.png',
            ],
            [
                'name' => 'النقابة البيطرية',
                'email' => 'veterinary.syndicate@vetora.test',
                'phone' => '0935001002',
                'national_id' => '3000000002',
                'membership_number' => 'SYN-VET-001',
                'type' => Category::TYPE_VETERINARY,
                'logo' => 'demo/categories/veterinary.png',
            ],
        ];
    }
}
