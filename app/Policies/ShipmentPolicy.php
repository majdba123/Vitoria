<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        $shipment->loadMissing('order');

        if ($user->isVendor()) {
            $vendorId = $user->managedVendor()?->id;

            return $vendorId !== null && (int) $shipment->order->vendor_id === (int) $vendorId;
        }

        return $shipment->order->user_id === $user->id;
    }

    public function manage(User $user, Shipment $shipment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $shipment->loadMissing('order.vendor');
        $vendorId = $user->managedVendor()?->id;

        return $user->isVendor()
            && $vendorId !== null
            && (int) $shipment->order->vendor_id === (int) $vendorId
            && $user->hasVendorPermission($shipment->order->vendor, 'shipments.manage');
    }
}
