<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        if ($user->isVendor()) {
            $vendorId = $user->managedVendor()?->id;

            return $vendorId !== null && (int) $invoice->vendor_id === (int) $vendorId;
        }

        return $invoice->user_id === $user->id;
    }
}
