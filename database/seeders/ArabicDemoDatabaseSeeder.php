<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\ApplicationCacheService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArabicDemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->pruneLegacyDemoCategories();

            $this->call([
                ArabicCitySeeder::class,
                ArabicCategorySeeder::class,
                ArabicSyndicateSeeder::class,
                ArabicVendorSeeder::class,
                ArabicProductSeeder::class,
            ]);
        });

        $cache = app(ApplicationCacheService::class);
        $cache->flushCategories();
        $cache->flushVendors();
        $cache->flushProducts();
        $cache->flushSyndicates();
        $cache->flushDashboard();
    }

    private function pruneLegacyDemoCategories(): void
    {
        Category::query()
            ->whereIn('name', [
                'Seeds',
                'Fertilizers',
                'Irrigation',
                'Greenhouses',
                'Agricultural Equipment',
                'Animal Medicine',
                'Vaccines',
                'Livestock Equipment',
                'Feed Supplements',
                'Veterinary Services',
            ])
            ->delete();
    }
}
