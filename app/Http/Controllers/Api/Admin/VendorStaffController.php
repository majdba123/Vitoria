<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Vendor;
use App\Models\VendorMember;
use Illuminate\Http\JsonResponse;

/**
 * Read-only admin visibility into vendor staff rosters (spec §22).
 */
class VendorStaffController extends Controller
{
    public function index(Vendor $vendor): JsonResponse
    {
        $members = VendorMember::query()
            ->with(['user:id,name,email,phone_number', 'role'])
            ->where('vendor_id', $vendor->id)
            ->get()
            ->map(fn (VendorMember $member) => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
                'role' => $member->role?->key,
                'role_name' => __("vendor_staff.role.{$member->role?->key}"),
                'status' => $member->status,
                'joined_at' => $member->joined_at,
            ])
            ->values();

        $owner = $vendor->user()->select('id', 'name', 'email', 'phone_number')->first();

        return response()->json([
            'message' => 'Staff retrieved successfully.',
            'data' => [
                'owner' => $owner ? [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'role' => Role::KEY_OWNER,
                    'role_name' => __('vendor_staff.role.owner'),
                ] : null,
                'members' => $members,
            ],
        ]);
    }
}
