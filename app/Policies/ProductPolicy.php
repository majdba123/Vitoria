<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Per-record authorization for products (decision D3, stakeholder review #26).
 *
 * Consolidates the ownership + vendor-staff-permission checks that were
 * previously duplicated inline across ProductController::update()/destroy()/
 * setPrimaryPhoto(), and makes the "a vendor cannot approve their own
 * product" rule a single, testable statement rather than an implicit side
 * effect of which FormRequest happens to strip the `status` field.
 */
class ProductPolicy
{
    /**
     * Edit the product's own fields (name, price, quantity, photos, ...).
     */
    public function update(User $user, Product $product): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) {
            return true;
        }

        return $user->isVendor() && $this->ownsVendorProduct($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isVendor() && $this->ownsVendorProduct($user, $product);
    }

    /**
     * Move the product through the pending/approved/rejected review state
     * machine. Vendors — including the product's own owner — never qualify:
     * approval must come from an admin, or an employee whose assigned
     * role(s) grant `products.moderate` (stakeholder review #24 — employee
     * capability now depends on their assigned role, not blanket employee
     * type).
     */
    public function manageStatus(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEmployee() && $user->hasEmployeePermission('products.moderate');
    }

    /**
     * The vendor account behind this user owns the product, and (for
     * vendor-staff members) has been granted the products.manage permission.
     */
    private function ownsVendorProduct(User $user, Product $product): bool
    {
        $vendor = $user->managedVendor();

        return $vendor !== null
            && (int) $product->vendor_id === (int) $vendor->id
            && $user->hasVendorPermission($vendor, 'products.manage');
    }
}
