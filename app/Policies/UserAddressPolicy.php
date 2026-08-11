<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;

/**
 * Per-record ownership for the address book (decision D3).
 *
 * Every address route authorizes through this, so a customer cannot read,
 * edit, delete or check out against another customer's address by supplying
 * its id (IDOR, spec §54).
 */
class UserAddressPolicy
{
    public function view(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function update(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function delete(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }
}
