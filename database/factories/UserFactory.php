<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone_number' => fake()->unique()->numerify('09########'),
            'national_id' => fake()->unique()->numerify('##########'),
            'age' => fake()->numberBetween(18, 70),
            'membership_number' => fake()->unique()->bothify('MEM-########'),
            'type' => User::TYPE_USER,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Admin-type fixtures model the platform's existing, fully trusted
     * administrators, so each one receives the `super_admin` role — exactly
     * what the RBAC migration backfills for every pre-existing admin. Use
     * `delegatedAdmin()` for an administrator limited to explicit roles.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->type !== User::TYPE_ADMIN) {
                return;
            }

            $superAdminRoleId = Role::query()->where('key', Role::KEY_SUPER_ADMIN)->value('id');

            if ($superAdminRoleId !== null) {
                $user->adminRoles()->syncWithoutDetaching([$superAdminRoleId]);
            }
        });
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => User::TYPE_ADMIN,
        ]);
    }

    /**
     * An admin-type account with no back-office permissions until roles are
     * explicitly assigned (the default for admins created after RBAC).
     */
    public function delegatedAdmin(): static
    {
        return $this->admin()->afterCreating(fn (User $user) => $user->adminRoles()->detach());
    }

    /**
     * Indicate that the user is a syndicate agent.
     */
    public function syndicate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => User::TYPE_SYNDICATE,
        ]);
    }

    /**
     * Indicate that the user is an employee.
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => User::TYPE_EMPLOYEE,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
