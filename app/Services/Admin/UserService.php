<?php

namespace App\Services\Admin;

use App\Models\User;

class UserService
{
    /**
     * Create a new user.
     *
     * @param  array{name: string, phone_number: string, national_id: string, email?: string, password: string, type?: int}  $data
     */
    public function create(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'national_id' => $data['national_id'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'type' => $data['type'] ?? User::TYPE_USER,
        ]);
    }

    /**
     * Update an existing user.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update(array_filter($data, fn ($value) => $value !== null));

        return $user->fresh();
    }

    /**
     * Delete a user and revoke all tokens.
     */
    public function delete(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }
}
