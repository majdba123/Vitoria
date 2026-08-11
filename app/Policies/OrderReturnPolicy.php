<?php

namespace App\Policies;

use App\Models\OrderReturn;
use App\Models\User;

/**
 * Per-record authorization for return requests (decision D3).
 *
 * Vendor isolation mirrors OrderPolicy: a vendor may only see or review
 * returns raised against their own orders (spec §54).
 */
class OrderReturnPolicy
{
    public function view(User $user, OrderReturn $return): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        if ($user->isVendor()) {
            return $this->ownsVendor($user, $return);
        }

        return $return->user_id === $user->id;
    }

    /**
     * Move the return through review (under_review, approved, rejected,
     * received, completed). Customers never can — they may only cancel.
     */
    public function review(User $user, OrderReturn $return): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isVendor() && $this->ownsVendor($user, $return);
    }

    public function cancel(User $user, OrderReturn $return): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $this->ownsVendor($user, $return);
        }

        return $return->user_id === $user->id;
    }

    /**
     * Request a refund for this return. Only an admin, or the vendor the
     * return was raised against, may start money moving (spec §13, §54).
     */
    public function initiateRefund(User $user, OrderReturn $return): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isVendor() && $this->ownsVendor($user, $return);
    }

    private function ownsVendor(User $user, OrderReturn $return): bool
    {
        $vendorId = $user->vendor?->id;

        return $vendorId !== null && (int) $return->vendor_id === (int) $vendorId;
    }
}
