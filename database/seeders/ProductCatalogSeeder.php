<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ArabicCitySeeder::class,
            ArabicCategorySeeder::class,
            ArabicSubcategorySeeder::class,
            ArabicVendorSeeder::class,
            ArabicProductSeeder::class,
        ]);
    }
}
