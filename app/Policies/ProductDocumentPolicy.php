<?php

namespace App\Policies;

use App\Models\ProductDocument;
use App\Models\User;

class ProductDocumentPolicy
{
    public function view(User $user, ProductDocument $document): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        return $user->isVendor() && $this->ownsVendor($user, $document);
    }

    /**
     * Upload or disable — gated by `products.manage`, the same permission
     * that gates catalog changes elsewhere (spec §22, §25).
     */
    public function manage(User $user, ProductDocument $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isVendor() && $this->ownsVendor($user, $document) && $user->hasVendorPermission($document->vendor, 'products.manage');
    }

    private function ownsVendor(User $user, ProductDocument $document): bool
    {
        $vendorId = $user->managedVendor()?->id;

        return $vendorId !== null && (int) $document->vendor_id === (int) $vendorId;
    }
}
