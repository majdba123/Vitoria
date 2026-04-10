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
use Illuminate\Support\Facades\Storage;

class VendorProfileController extends Controller
{
    /**
     * Get the authenticated vendor's profile (user + vendor data).
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = Vendor::query()
            ->where('user_id', $user->id)
            ->with('categories')
            ->first();

        return response()->json([
            'message' => __('Profile retrieved successfully.'),
            'data' => [
                'user' => new UserResource($user),
                'vendor' => $vendor ? [
                    'id' => $vendor->id,
                    'store_name' => $vendor->store_name,
                    'description' => $vendor->description,
                    'address' => $vendor->address,
                    'logo' => $vendor->logo,
                    'logo_url' => $vendor->logo ? asset('storage/'.$vendor->logo) : null,
                    'is_active' => $vendor->is_active,
                    'categories' => $vendor->categories->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
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
        $vendor = Vendor::query()->where('user_id', $user->id)->first();
        $data = $request->validated();

        return DB::transaction(function () use ($user, $vendor, $data, $request) {
            $userFields = [];

            foreach (['name', 'email', 'phone_number', 'national_id'] as $field) {
                if (isset($data[$field])) {
                    $userFields[$field] = $data[$field];
                }
            }

            if (! empty($data['password'])) {
                $userFields['password'] = $data['password'];
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

            if ($vendor) {
                $vendorFields = [];
                foreach (['store_name', 'description', 'address'] as $field) {
                    if (isset($data[$field])) {
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
                        'description' => $vendor->description,
                        'address' => $vendor->address,
                        'logo' => $vendor->logo,
                        'logo_url' => $vendor->logo ? asset('storage/'.$vendor->logo) : null,
                        'is_active' => $vendor->is_active,
                        'categories' => $vendor->categories->map(fn ($c) => [
                            'id' => $c->id,
                            'name' => $c->name,
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
