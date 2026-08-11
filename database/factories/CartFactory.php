<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_token' => null,
            'last_activity_at' => now(),
        ];
    }

    public function guest(?string $token = null): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'session_token' => $token ?? (string) \Illuminate\Support\Str::uuid(),
        ]);
    }
}
