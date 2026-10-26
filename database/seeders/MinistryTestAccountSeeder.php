<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Idempotent Ministry account for the test server. The deploy workflow runs
 * this seeder on every deployment, so it must stay safe to re-run.
 */
class MinistryTestAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['phone_number' => '0935000555'],
            [
                'name' => 'وزارة الزراعة',
                'national_id' => '5000000001',
                'type' => User::TYPE_MINISTRY,
                'email' => 'ministry@vetora.test',
                'timezone' => 'Asia/Damascus',
                'password' => 'password',
            ],
        );
    }
}
