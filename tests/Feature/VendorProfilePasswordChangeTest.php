<?php

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

/**
 * Locks in the contract vendor/profile.blade.php now actually fulfills: the
 * form previously had no "current password" field at all, so
 * UpdateProfileRequest's required_with:password rule on current_password
 * made every password-change attempt fail with a validation error. The
 * frontend now sends it; these tests pin the backend behavior it relies on.
 */
it('changes the vendor password when the current password is correct', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['type' => User::TYPE_VENDOR, 'password' => Hash::make('old-password')]);
    Sanctum::actingAs($vendor->user);

    $this->postJson('/api/vendor/profile', [
        'password' => 'brand-new-password',
        'current_password' => 'old-password',
    ])->assertOk();

    expect(Hash::check('brand-new-password', $vendor->user->fresh()->password))->toBeTrue();
});

it('rejects a password change without the current password', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['type' => User::TYPE_VENDOR, 'password' => Hash::make('old-password')]);
    Sanctum::actingAs($vendor->user);

    $this->postJson('/api/vendor/profile', [
        'password' => 'brand-new-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

    expect(Hash::check('old-password', $vendor->user->fresh()->password))->toBeTrue();
});

it('rejects a password change with the wrong current password', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['type' => User::TYPE_VENDOR, 'password' => Hash::make('old-password')]);
    Sanctum::actingAs($vendor->user);

    $this->postJson('/api/vendor/profile', [
        'password' => 'brand-new-password',
        'current_password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

    expect(Hash::check('old-password', $vendor->user->fresh()->password))->toBeTrue();
});
