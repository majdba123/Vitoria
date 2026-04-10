<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['phone_number' => '0935027218'],
            [
                'name' => 'Admin',
                'national_id' => '0000000001',
                'type' => User::TYPE_ADMIN,
                'email' => 'admin@msz-demo.test',
                'password' => 'password',
            ],
        );

        $this->call([
            CitySeeder::class,
            CategorySubcategorySeeder::class,
            MarketplaceDemoSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
