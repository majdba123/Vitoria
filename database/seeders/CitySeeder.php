<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            'Damascus',
            'Aleppo',
            'Homs',
            'Hama',
            'Latakia',
            'Tartus',
            'Idlib',
            'Raqqa',
            'Deir ez-Zor',
            'Al-Hasakah',
            'Daraa',
            'As-Suwayda',
            'Quneitra',
            'Palmyra',
            'Jableh',
            'Manbij',
            'Afrin',
            'Al-Bab',
            'Al-Qamishli',
            'Salamiyah',
            'Masyaf',
            'Safita',
            'Baniyas',
            'Al-Mayadin',
            'Abu Kamal',
            'Al-Safira',
            'Maarat al-Numan',
            'Ariha',
            'Jisr al-Shughur',
            'Kobani',
            'Qamishli',
        ];

        foreach ($cities as $name) {
            City::query()->firstOrCreate(['name' => $name]);
        }
    }
}
