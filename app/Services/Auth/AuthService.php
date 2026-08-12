<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Services\Vendor\VendorDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly VendorDocumentService $vendorDocumentService,
    ) {}

    public function register(array $data): array
    {
        $commercialRegisterPath = null;
        $vendor = null;
        $commercialRegisterFile = null;

        try {
            $user = DB::transaction(function () use ($data, &$commercialRegisterFile, &$commercialRegisterPath, &$vendor) {
                $accountType = $data['account_type'] ?? 'user';
                $user = User::query()->create([
                    'name' => $data['name'],
                    'phone_number' => $data['phone_number'],
                    'national_id' => $data['national_id'],
                    'age' => $data['age'],
                    'membership_number' => $data['membership_number'],
                    'city_id' => $data['city_id'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'locale' => app()->getLocale(),
                    'type' => $accountType === 'vendor' ? User::TYPE_VENDOR : User::TYPE_USER,
                    'email' => $data['email'],
                    'password' => $data['password'] ?? null,
                ]);

                if ($accountType === 'vendor') {
                    $commercialRegisterFile = $data['commercial_register_file'] ?? null;
                    $commercialRegisterPath = $commercialRegisterFile instanceof UploadedFile
                        ? Storage::disk('local')->putFile('commercial-registers', $commercialRegisterFile)
                        : null;

                    $vendor = Vendor::query()->create([
                        'user_id' => $user->id,
                        'store_name' => $data['store_name'],
                        'business_type' => $data['business_type'],
                        'description' => $data['description'] ?? null,
                        'address' => $data['address'] ?? null,
                        'city_id' => $data['city_id'],
                        'latitude' => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                        'is_active' => false,
                        'status' => Vendor::STATUS_PENDING,
                        'registration_source' => Vendor::REGISTRATION_SOURCE_SELF,
                        'commercial_register_file' => $commercialRegisterPath,
                    ]);

                    $vendor->categories()->sync(array_map('intval', $data['category_ids']));
                }

                return $user;
            });
        } catch (\Throwable $exception) {
            if ($commercialRegisterPath) {
                Storage::disk('local')->delete($commercialRegisterPath);
            }

            throw $exception;
        }

        if ($vendor instanceof Vendor && $commercialRegisterFile instanceof UploadedFile) {
            // Additive: populates the vendor_documents review queue (spec
            // §24) from day one without touching commercial_register_file,
            // which stays exactly as it was (decision D16).
            try {
                $this->vendorDocumentService->upload($vendor, VendorDocument::TYPE_COMMERCIAL_REGISTRATION, $commercialRegisterFile);
            } catch (\Throwable $exception) {
                Log::warning('Failed to create the initial vendor_documents row at registration.', [
                    'vendor_id' => $vendor->id,
                    'exception' => $exception,
                ]);
            }
        }

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
