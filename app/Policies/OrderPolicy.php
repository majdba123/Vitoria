<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Per-record authorization for orders (decision D3).
 *
 * Vendor isolation is enforced here rather than being re-implemented in each
 * controller, so a vendor cannot reach another vendor's order by id (spec §54).
 */
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        if ($user->isVendor()) {
            return $this->ownsVendor($user, $order);
        }

        return $order->user_id === $user->id;
    }

    /**
     * Drive the order forward through fulfilment. Customers never can — they
     * may only cancel, which is a separate ability.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        return $user->isVendor() && $this->ownsVendor($user, $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $this->ownsVendor($user, $order);
        }

        return $order->user_id === $user->id;
    }

    /**
     * Request a return against this order (spec §12). Only the customer who
     * placed it — vendors and admins review returns, they do not raise them.
     */
    public function requestReturn(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    /**
     * The vendor account behind this user owns the order.
     */
    private function ownsVendor(User $user, Order $order): bool
    {
        $vendorId = $user->vendor?->id;

        return $vendorId !== null && (int) $order->vendor_id === (int) $vendorId;
    }
}
