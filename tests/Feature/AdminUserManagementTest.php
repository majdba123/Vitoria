<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Admin user creation/update type restrictions.
 *
 * Creating a user with type=Vendor (or Syndicate) through the generic
 * POST /api/admin/users endpoint used to be accepted, but it only inserts a
 * bare users row — no vendors/syndicates row, which every vendor/syndicate
 * feature requires via User::managedVendor() etc. That produced an account
 * locked out of everything its type implies. Vendor/syndicate accounts must
 * go through their dedicated creation endpoints instead, which create both
 * rows atomically.
 */
it('rejects creating a user with type vendor through the generic admin user endpoint', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->postJson('/api/admin/users', [
        'name' => 'Broken Vendor',
        'phone_number' => '0955512345',
        'national_id' => 'NID-9001',
        'email' => 'broken-vendor@example.test',
        'password' => 'password123',
        'type' => User::TYPE_VENDOR,
    ])->assertUnprocessable()->assertJsonValidationErrors('type');

    expect(User::query()->where('phone_number', '0955512345')->exists())->toBeFalse();
});

it('still allows editing an existing vendor-type user through the generic admin edit endpoint', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR, 'name' => 'Original Name']);

    // The edit form always resubmits the account's current type, so the
    // update endpoint must keep accepting it even though creation no longer
    // does — otherwise saving any other field on an existing vendor account
    // through this screen would break.
    $this->putJson("/api/admin/users/{$vendorUser->id}", [
        'name' => 'Updated Name',
        'phone_number' => $vendorUser->phone_number,
        'national_id' => $vendorUser->national_id,
        'type' => User::TYPE_VENDOR,
    ])->assertOk();

    expect($vendorUser->fresh()->name)->toBe('Updated Name')
        ->and($vendorUser->fresh()->type)->toBe(User::TYPE_VENDOR);
});

it('allows editing an existing syndicate-type user without corrupting their type', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $syndicateUser = User::factory()->create(['type' => User::TYPE_SYNDICATE, 'name' => 'Original Syndicate']);

    $this->putJson("/api/admin/users/{$syndicateUser->id}", [
        'name' => 'Updated Syndicate',
        'phone_number' => $syndicateUser->phone_number,
        'national_id' => $syndicateUser->national_id,
        'type' => User::TYPE_SYNDICATE,
    ])->assertOk();

    expect($syndicateUser->fresh()->type)->toBe(User::TYPE_SYNDICATE);
});
