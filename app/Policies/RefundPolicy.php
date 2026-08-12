<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

/**
 * Per-record authorization for refunds (decision D3).
 *
 * Settling money is admin-only: a vendor may request a refund for a return
 * it owns, but only an admin can actually complete or cancel one (spec §13,
 * §54).
 */
class RefundPolicy
{
    public function view(User $user, Refund $refund): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        $refund->loadMissing('order');

        if ($user->isVendor()) {
            $vendorId = $user->managedVendor()?->id;

            return $vendorId !== null && (int) $refund->order->vendor_id === (int) $vendorId;
        }

        return $refund->order->user_id === $user->id;
    }

    public function manage(User $user, Refund $refund): bool
    {
        return $user->isAdmin();
    }
}
