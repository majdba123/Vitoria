<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\VendorMember;
use App\Services\Vendor\VendorStaffException;
use App\Services\Vendor\VendorStaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Vendor staff management (spec §22). Listing is open to any active staff —
 * seeing who else works here isn't sensitive. Adding, re-roling, and
 * removing requires `staff.manage`, which only Owner/Manager hold.
 */
class StaffController extends Controller
{
    public function __construct(
        private readonly VendorStaffService $vendorStaffService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }

        $members = VendorMember::query()
            ->with(['user:id,name,email,phone_number', 'role'])
            ->where('vendor_id', $vendor->id)
            ->where('status', VendorMember::STATUS_ACTIVE)
            ->get()
            ->map(fn (VendorMember $member) => $this->present($member))
            ->values();

        $owner = $vendor->user()->select('id', 'name', 'email', 'phone_number')->first();

        return response()->json([
            'message' => __('api.staff_retrieved'),
            'data' => [
                'owner' => $owner ? [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'phone_number' => $owner->phone_number,
                    'role' => Role::KEY_OWNER,
                    'role_name' => __('vendor_staff.role.owner'),
                ] : null,
                'members' => $members,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }
        if (! $user->hasVendorPermission($vendor, 'staff.manage')) {
            abort(403, __('You are not allowed to manage staff for this vendor.'));
        }

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(Role::INVITABLE_KEYS)],
        ]);

        try {
            $member = $this->vendorStaffService->addMember($vendor, $user, $validated['identifier'], $validated['role']);
        } catch (VendorStaffException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('vendor_staff.added_success'),
            'data' => $this->present($member->load(['user', 'role'])),
        ], 201);
    }

    public function update(Request $request, int $memberId): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }
        if (! $user->hasVendorPermission($vendor, 'staff.manage')) {
            abort(403, __('You are not allowed to manage staff for this vendor.'));
        }

        $member = VendorMember::query()->where('vendor_id', $vendor->id)->findOrFail($memberId);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(Role::INVITABLE_KEYS)],
        ]);

        try {
            $member = $this->vendorStaffService->updateRole($member, $validated['role']);
        } catch (VendorStaffException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('vendor_staff.role_updated_success'),
            'data' => $this->present($member->load(['user', 'role'])),
        ]);
    }

    public function destroy(Request $request, int $memberId): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }
        if (! $user->hasVendorPermission($vendor, 'staff.manage')) {
            abort(403, __('You are not allowed to manage staff for this vendor.'));
        }

        $member = VendorMember::query()->where('vendor_id', $vendor->id)->findOrFail($memberId);
        $this->vendorStaffService->remove($member);

        return response()->json(['message' => __('vendor_staff.removed_success')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(VendorMember $member): array
    {
        return [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => $member->user?->name,
            'email' => $member->user?->email,
            'phone_number' => $member->user?->phone_number,
            'role' => $member->role?->key,
            'role_name' => __("vendor_staff.role.{$member->role?->key}"),
            'status' => $member->status,
            'joined_at' => $member->joined_at,
        ];
    }
}
