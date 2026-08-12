<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorMember>
 */
class VendorMemberFactory extends Factory
{
    protected $model = VendorMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'user_id' => User::factory(),
            // Roles are a small, system-seeded set (see the RBAC migration)
            // rather than something factories generate.
            'role_id' => fn () => Role::where('key', Role::KEY_VIEWER)->value('id'),
            'status' => VendorMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ];
    }
}
