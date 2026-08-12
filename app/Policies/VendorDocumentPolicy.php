<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorDocument;

/**
 * Documents are compliance/profile material, so they're gated by the same
 * `profile.manage` permission as VendorProfileController rather than a new
 * permission of their own (spec §22, §24).
 */
class VendorDocumentPolicy
{
    public function view(User $user, VendorDocument $document): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        return $user->isVendor() && $this->managesVendor($user, $document);
    }

    public function manage(User $user, VendorDocument $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isVendor() && $this->managesVendor($user, $document) && $user->hasVendorPermission($document->vendor, 'documents.manage');
    }

    private function managesVendor(User $user, VendorDocument $document): bool
    {
        $vendorId = $user->managedVendor()?->id;

        return $vendorId !== null && (int) $document->vendor_id === (int) $vendorId;
    }
}
