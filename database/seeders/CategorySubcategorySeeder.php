<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategorySubcategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ArabicCategorySeeder::class);
    }
}
