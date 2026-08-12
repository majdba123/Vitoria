<?php

namespace App\Services\Vendor;

use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMember;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

/**
 * Vendor staff (spec §22): adding, re-roling, and removing users other than
 * the owner. Isolation and permission enforcement live in
 * User::hasVendorPermission() and the policies that call it — this service
 * only encodes the business rules around *who* can become staff and *how*.
 */
class VendorStaffService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Add — or reactivate — a staff member by their email or phone number.
     * The target must already have an account; there is no invite-by-email
     * flow for a non-existent user (not required by spec §22, and it would
     * add a token/acceptance state machine this feature does not need).
     *
     * @throws VendorStaffException
     */
    public function addMember(Vendor $vendor, User $actor, string $identifier, string $roleKey): VendorMember
    {
        if (! in_array($roleKey, Role::INVITABLE_KEYS, true)) {
            throw new VendorStaffException(__('vendor_staff.role_invalid'));
        }

        $role = Role::query()->where('key', $roleKey)->firstOrFail();

        $target = User::query()
            ->where('email', $identifier)
            ->orWhere('phone_number', $identifier)
            ->first();

        if (! $target) {
            throw new VendorStaffException(__('vendor_staff.user_not_found'));
        }

        if ((int) $vendor->user_id === (int) $target->id) {
            throw new VendorStaffException(__('vendor_staff.already_owner'));
        }

        if (Vendor::query()->where('user_id', $target->id)->exists()) {
            throw new VendorStaffException(__('vendor_staff.owns_another_vendor'));
        }

        $existing = VendorMember::query()->where('vendor_id', $vendor->id)->where('user_id', $target->id)->first();

        if ($existing && $existing->status === VendorMember::STATUS_ACTIVE) {
            throw new VendorStaffException(__('vendor_staff.already_member'));
        }

        $activeElsewhere = VendorMember::query()
            ->where('user_id', $target->id)
            ->where('status', VendorMember::STATUS_ACTIVE)
            ->where('vendor_id', '!=', $vendor->id)
            ->exists();

        if ($activeElsewhere) {
            throw new VendorStaffException(__('vendor_staff.staff_elsewhere'));
        }

        $member = DB::transaction(function () use ($actor, $existing, $role, $target, $vendor) {
            if ($target->type !== User::TYPE_VENDOR) {
                // Required for EnsureUserIsVendor to admit them at all — a
                // customer becoming staff gains vendor-side route access,
                // scoped by managedVendor()/hasVendorPermission() exactly
                // like it already does for an owner.
                $target->update(['type' => User::TYPE_VENDOR]);
            }

            if ($existing) {
                $existing->update([
                    'role_id' => $role->id,
                    'status' => VendorMember::STATUS_ACTIVE,
                    'invited_by_user_id' => $actor->id,
                    'joined_at' => now(),
                ]);

                return $existing->fresh();
            }

            return VendorMember::create([
                'vendor_id' => $vendor->id,
                'user_id' => $target->id,
                'role_id' => $role->id,
                'status' => VendorMember::STATUS_ACTIVE,
                'invited_by_user_id' => $actor->id,
                'joined_at' => now(),
            ]);
        });

        $this->notificationService->notifyVendorStaffAdded($member->fresh(['vendor', 'user']));

        return $member;
    }

    /**
     * @throws VendorStaffException
     */
    public function updateRole(VendorMember $member, string $roleKey): VendorMember
    {
        if (! in_array($roleKey, Role::INVITABLE_KEYS, true)) {
            throw new VendorStaffException(__('vendor_staff.role_invalid'));
        }

        $role = Role::query()->where('key', $roleKey)->firstOrFail();
        $member->update(['role_id' => $role->id]);

        return $member->refresh();
    }

    public function remove(VendorMember $member): VendorMember
    {
        $member->update(['status' => VendorMember::STATUS_REMOVED]);

        return $member->refresh();
    }
}
