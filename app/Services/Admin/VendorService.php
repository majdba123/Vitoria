<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VendorService
{
    /**
     * Create a vendor along with its user account.
     *
     * @param  array{name: string, email?: string, password: string, phone_number: string, national_id: string, store_name: string, description?: string, address?: string, logo?: string}  $data
     */
    public function create(array $data): Vendor
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'phone_number' => $data['phone_number'],
                'national_id' => $data['national_id'],
                'type' => User::TYPE_VENDOR,
            ]);

            $vendor = Vendor::query()->create([
                'user_id' => $user->id,
                'store_name' => $data['store_name'],
                'business_type' => $data['business_type'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'] ?? null,
                'city_id' => $data['city_id'],
                'logo' => $data['logo'] ?? null,
                'is_active' => true,
                'status' => Vendor::STATUS_ACTIVE,
                'registration_source' => Vendor::REGISTRATION_SOURCE_ADMIN,
            ]);

            if (isset($data['category_ids'])) {
                $vendor->categories()->sync($data['category_ids']);
            }

            $this->flushVendorCache();

            return $vendor;
        });
    }

    /**
     * Update an existing vendor and its user account.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Vendor $vendor, array $data): Vendor
    {
        return DB::transaction(function () use ($vendor, $data) {
            $userFields = array_filter(
                array_intersect_key($data, array_flip(['name', 'email', 'password', 'phone_number', 'national_id'])),
                fn ($value) => $value !== null,
            );

            if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
                if ($vendor->user->avatar) {
                    Storage::disk('public')->delete($vendor->user->avatar);
                }
                $userFields['avatar'] = $data['avatar']->store('avatars', 'public');
            }

            if ($userFields) {
                $vendor->user->update($userFields);
            }

            $vendorFields = array_intersect_key($data, array_flip([
                'store_name', 'business_type', 'description', 'address', 'city_id', 'is_active',
            ]));

            if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
                if ($vendor->logo) {
                    Storage::disk('public')->delete($vendor->logo);
                }
                $vendorFields['logo'] = $data['logo']->store('vendors', 'public');
            }

            if ($vendorFields) {
                $vendor->update($vendorFields);
            }

            if (isset($data['category_ids'])) {
                $vendor->categories()->sync($data['category_ids']);
            }

            $this->flushVendorCache();
            $this->flushProductCache();

            return $vendor->fresh(['user', 'categories']);
        });
    }

    /**
     * Toggle vendor active status.
     */
    public function toggleActive(Vendor $vendor): Vendor
    {
        $isActive = ! $vendor->is_active;

        $vendor->update([
            'is_active' => $isActive,
            'status' => $isActive ? Vendor::STATUS_ACTIVE : Vendor::STATUS_INACTIVE,
        ]);

        $this->flushVendorCache();
        $this->flushProductCache();

        return $vendor->fresh('user');
    }

    /**
     * Approve a pending self-registered vendor.
     */
    public function approve(Vendor $vendor): Vendor
    {
        $vendor->update([
            'is_active' => true,
            'status' => Vendor::STATUS_ACTIVE,
        ]);

        $this->flushVendorCache();
        $this->flushProductCache();

        return $vendor->fresh(['user', 'categories']);
    }

    /**
     * Delete a vendor and its user account.
     */
    public function delete(Vendor $vendor): void
    {
        DB::transaction(function () use ($vendor) {
            $vendorId = $vendor->id;
            $vendor->user->tokens()->delete();
            $vendor->delete();
            $vendor->user->delete();

            $this->flushVendorCache();
            $this->flushProductCache();
        });
    }

    protected function flushVendorCache(): void
    {
        Cache::forget('admin_dashboard_overview');

        try {
            Cache::tags(['vendors'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }
    }

    protected function flushProductCache(): void
    {
        Cache::forget('admin_dashboard_overview');

        try {
            Cache::tags(['products'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }
    }
}
