<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateProfileRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VendorProfileController extends Controller
{
    /**
     * Get the authenticated vendor's profile (user + vendor data).
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('city');
        $vendor = $user->managedVendor();
        $vendor?->loadMissing('categories');

        return response()->json([
            'message' => __('Profile retrieved successfully.'),
            'data' => [
                'user' => new UserResource($user),
                'vendor' => $vendor ? [
                    'id' => $vendor->id,
                    'store_name' => $vendor->store_name,
                    'business_type' => $vendor->business_type,
                    'business_type_label' => Vendor::businessTypeLabels()[$vendor->business_type] ?? $vendor->business_type,
                    'description' => $vendor->description,
                    'address' => $vendor->address,
                    'latitude' => $vendor->latitude !== null ? (float) $vendor->latitude : null,
                    'longitude' => $vendor->longitude !== null ? (float) $vendor->longitude : null,
                    'logo' => $vendor->logo,
                    'logo_url' => $vendor->logo ? asset('storage/'.$vendor->logo) : null,
                    'is_active' => $vendor->is_active,
                    'categories' => $vendor->categories->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'type' => $c->type,
                        'type_label' => \App\Models\Category::typeLabels()[$c->type] ?? $c->type,
                        'commission' => $c->commission,
                    ]),
                    'created_at' => $vendor->created_at,
                ] : null,
            ],
        ]);
    }

    /**
     * Update the authenticated vendor's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->managedVendor();
        $data = $request->validated();

        $vendorFieldKeys = ['store_name', 'description', 'address'];
        // Coordinates are checked with array_key_exists rather than isset so
        // that *clearing* the pin (both sent as null) is gated too.
        $requestsLocationChange = array_key_exists('latitude', $data) || array_key_exists('longitude', $data);
        $requestsVendorChange = $request->hasFile('logo')
            || $requestsLocationChange
            || collect($vendorFieldKeys)->some(fn ($field) => isset($data[$field]));

        if ($vendor && $requestsVendorChange && ! $user->hasVendorPermission($vendor, 'profile.manage')) {
            abort(403, __('You are not allowed to update the store profile.'));
        }

        if (! empty($data['password'])) {
            if (! $user->password || ! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => [__('The provided password is incorrect.')],
                ]);
            }
        }

        return DB::transaction(function () use ($user, $vendor, $data, $request) {
            $userFields = [];

            foreach (['name', 'email', 'phone_number', 'national_id'] as $field) {
                if (isset($data[$field])) {
                    $userFields[$field] = $data[$field];
                }
            }

            $passwordChanged = false;

            if (! empty($data['password'])) {
                $userFields['password'] = $data['password'];
                $passwordChanged = true;
            }

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $userFields['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            if ($userFields) {
                $user->update($userFields);
            }

            if ($passwordChanged) {
                $currentToken = $request->user()->currentAccessToken();

                if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
                    $user->tokens()->where('id', '!=', $currentToken->id)->delete();
                } else {
                    // Authenticated via the session-cookie guard (no bearer token to preserve) -
                    // revoke every existing bearer token since none of them belongs to this request.
                    $user->tokens()->delete();
                }
            }

            if ($vendor) {
                $vendorFields = [];
                foreach (['store_name', 'description', 'address'] as $field) {
                    if (isset($data[$field])) {
                        $vendorFields[$field] = $data[$field];
                    }
                }

                foreach (['latitude', 'longitude'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $vendorFields[$field] = $data[$field];
                    }
                }

                if ($request->hasFile('logo')) {
                    if ($vendor->logo) {
                        Storage::disk('public')->delete($vendor->logo);
                    }
                    $vendorFields['logo'] = $request->file('logo')->store('vendors', 'public');
                }

                if ($vendorFields) {
                    $vendor->update($vendorFields);
                }
            }

            $user->refresh();
            $vendor?->refresh();
            $vendor?->load('categories');

            if ($vendor) {
                $this->flushVendorCache();
                $this->flushProductCache();
            }

            return response()->json([
                'message' => __('Profile updated successfully.'),
                'data' => [
                    'user' => new UserResource($user),
                    'vendor' => $vendor ? [
                        'id' => $vendor->id,
                        'store_name' => $vendor->store_name,
                        'business_type' => $vendor->business_type,
                        'business_type_label' => Vendor::businessTypeLabels()[$vendor->business_type] ?? $vendor->business_type,
                        'description' => $vendor->description,
                        'address' => $vendor->address,
                        'latitude' => $vendor->latitude !== null ? (float) $vendor->latitude : null,
                        'longitude' => $vendor->longitude !== null ? (float) $vendor->longitude : null,
                        'logo' => $vendor->logo,
                        'logo_url' => $vendor->logo ? asset('storage/'.$vendor->logo) : null,
                        'is_active' => $vendor->is_active,
                        'categories' => $vendor->categories->map(fn ($c) => [
                            'id' => $c->id,
                            'name' => $c->name,
                            'type' => $c->type,
                            'type_label' => \App\Models\Category::typeLabels()[$c->type] ?? $c->type,
                            'commission' => $c->commission,
                        ]),
                        'created_at' => $vendor->created_at,
                    ] : null,
                ],
            ]);
        });
    }

    protected function flushVendorCache(): void
    {
        try {
            Cache::tags(['vendors'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }
    }

    protected function flushProductCache(): void
    {
        try {
            Cache::tags(['products'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }
    }
}
