<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new public (customer) account. Public self-registration
     * always creates a customer — the role is never taken from request
     * input, so a forged payload cannot escalate to vendor/admin/employee/
     * syndicate. Vendor accounts are created only through the admin-managed
     * flow (App\Http\Controllers\Api\Admin\VendorController).
     */
    public function register(array $data): array
    {
        $user = DB::transaction(fn () => User::query()->create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'national_id' => $data['national_id'],
            'age' => $data['age'],
            'membership_number' => $data['membership_number'],
            'city_id' => $data['city_id'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'locale' => app()->getLocale(),
            'type' => User::TYPE_USER,
            'email' => $data['email'],
            'password' => $data['password'] ?? null,
        ]));

        Cache::forget(\App\Services\ApplicationCacheService::DASHBOARD_ADMIN_STATS);
        Cache::forget(\App\Services\ApplicationCacheService::ADMIN_DASHBOARD_LEGACY);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate a user by phone_number and password.
     *
     * @param  array{phone_number: string, password: string}  $credentials
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = User::query()
            ->where('phone_number', $credentials['phone_number'])
            ->first();

        if (! $user) {
            Log::warning('Failed login attempt: unknown phone number.');

            throw ValidationException::withMessages([
                'phone_number' => [__('The provided credentials are incorrect.')],
            ]);
        }

        if (! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            Log::warning('Failed login attempt: invalid password.', [
                'user_id' => $user->id,
            ]);

            throw ValidationException::withMessages([
                'password' => [__('The provided password is incorrect.')],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoke the current user's access token (logout).
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }
    }
}
