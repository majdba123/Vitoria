<?php

namespace Database\Seeders;

use App\Models\Port;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Port::DEFAULT_NAMES as $name) {
            Port::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
