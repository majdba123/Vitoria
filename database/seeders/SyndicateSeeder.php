<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Syndicate;
use App\Models\User;
use Illuminate\Database\Seeder;

class SyndicateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createSyndicateAgent(
            name: 'Agriculture Syndicate',
            type: Category::TYPE_AGRICULTURE,
            phone: '0935001001',
            email: 'agriculture-syndicate@msz-demo.test',
        );

        $this->createSyndicateAgent(
            name: 'Veterinary Syndicate',
            type: Category::TYPE_VETERINARY,
            phone: '0935001002',
            email: 'veterinary-syndicate@msz-demo.test',
        );
    }

    protected function createSyndicateAgent(string $name, string $type, string $phone, string $email): void
    {
        $user = User::query()->updateOrCreate(
            ['phone_number' => $phone],
            [
                'name' => $name,
                'national_id' => $type === Category::TYPE_AGRICULTURE ? '1000000001' : '1000000002',
                'age' => 35,
                'membership_number' => strtoupper(substr($type, 0, 3)).'-SYNDICATE-001',
                'type' => User::TYPE_SYNDICATE,
                'email' => $email,
                'password' => 'password',
            ],
        );

        Syndicate::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $name,
                'type' => $type,
                'phone' => $phone,
                'email' => $email,
                'status' => Syndicate::STATUS_ACTIVE,
                'logo' => null,
            ],
        );
    }
}
