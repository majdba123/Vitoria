<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Idempotent Ministry account for the test server. The deploy workflow runs
 * this seeder on every deployment, so it must stay safe to re-run, and it
 * never takes over a phone number that already belongs to another role.
 */
class MinistryTestAccountSeeder extends Seeder
{
    public const PHONE_NUMBER = '0935000555';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existing = User::query()->where('phone_number', self::PHONE_NUMBER)->first();

        if ($existing && ! $existing->isMinistry()) {
            $this->command?->warn('Skipped Ministry test account: '.self::PHONE_NUMBER.' belongs to a non-Ministry user.');

            return;
        }

        $attributes = [
            'name' => 'وزارة الزراعة',
            'national_id' => '5000000001',
            'type' => User::TYPE_MINISTRY,
            'email' => 'ministry@vetora.test',
            'timezone' => 'Asia/Damascus',
            'password' => 'password',
        ];

        $identityTaken = User::query()
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
            ->where(fn ($query) => $query->where('national_id', $attributes['national_id'])->orWhere('email', $attributes['email']))
            ->exists();

        if ($identityTaken) {
            $this->command?->warn('Skipped Ministry test account: its national ID or email belongs to another user.');

            return;
        }

        User::query()->updateOrCreate(['phone_number' => self::PHONE_NUMBER], $attributes);
    }
}
