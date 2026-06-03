<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SyndicateSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ArabicSyndicateSeeder::class);
    }
}
