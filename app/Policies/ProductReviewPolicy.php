<?php

namespace App\Policies;

use App\Models\ProductReview;
use App\Models\User;

class ProductReviewPolicy
{
    /**
     * Admin can delete any review; vendor can delete reviews on their products; user can delete only their own.
     */
    public function delete(User $user, ProductReview $productReview): bool
    {
        if ($user->type === User::TYPE_ADMIN) {
            return true;
        }

        if ($user->type === User::TYPE_VENDOR && $user->vendor) {
            return $productReview->product && $productReview->product->vendor_id === $user->vendor->id;
        }

        return $productReview->user_id === $user->id;
    }
}
