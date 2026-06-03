<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class ArabicCitySeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'دمشق',
            'حلب',
            'حمص',
            'حماة',
            'اللاذقية',
            'طرطوس',
        ])->each(fn (string $name): City => City::query()->firstOrCreate(['name' => $name]));
    }
}
