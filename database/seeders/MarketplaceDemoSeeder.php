<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ArabicVendorSeeder::class,
            ArabicProductSeeder::class,
        ]);
    }
}
