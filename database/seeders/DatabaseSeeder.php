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
                'name' => 'مدير النظام',
                'national_id' => '0000000001',
                'age' => 30,
                'membership_number' => 'MEM-ADMIN-001',
                'type' => User::TYPE_ADMIN,
                'email' => 'admin@vetora.test',
                'timezone' => 'Asia/Damascus',
                'password' => 'password',
            ],
        );

      /*  $this->call([
            ArabicDemoDatabaseSeeder::class,
        ]);*/
    }
}
