<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

function actingAsVendorWithRealToken(Vendor $vendor, string $tokenName = 'vendor-token'): string
{
    $vendor->user->tokens()->delete();

    return $vendor->user->createToken($tokenName)->plainTextToken;
}

it('rate limits repeated login attempts', function (): void {
    $user = User::factory()->create([
        'phone_number' => '0999999999',
        'password' => Hash::make('secret-123'),
    ]);

    foreach (range(1, 5) as $attempt) {
        $response = $this->postJson('/api/auth/login', [
            'phone_number' => $user->phone_number,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    $this->postJson('/api/auth/login', [
        'phone_number' => $user->phone_number,
        'password' => 'wrong-password',
    ])->assertStatus(429)
        ->assertJsonPath('status', 429);
});

it('prevents vendors from accessing another vendors product', function (): void {
    $signedInVendor = Vendor::factory()->create();
    $otherProduct = Product::factory()->create([
        'vendor_id' => Vendor::factory()->create()->id,
    ]);

    Sanctum::actingAs($signedInVendor->user);

    $this->getJson("/api/vendor/products/{$otherProduct->id}")
        ->assertForbidden();
});

it('prevents normal users from reaching admin api endpoints', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/admin/vendors')
        ->assertForbidden();
});

it('rejects vendor password change without current_password', function (): void {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['password' => Hash::make('old-secret-123')]);

    $token = actingAsVendorWithRealToken($vendor);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/vendor/profile', [
            'password' => 'new-secret-456',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);

    expect(Hash::check('old-secret-123', $vendor->user->fresh()->password))->toBeTrue();
});

it('rejects vendor password change with wrong current_password', function (): void {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['password' => Hash::make('old-secret-123')]);

    $token = actingAsVendorWithRealToken($vendor);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/vendor/profile', [
            'password' => 'new-secret-456',
            'current_password' => 'totally-wrong-password',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);

    expect(Hash::check('old-secret-123', $vendor->user->fresh()->password))->toBeTrue();
});

it('changes vendor password with correct current_password and revokes other tokens', function (): void {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['password' => Hash::make('old-secret-123')]);

    $oldToken = actingAsVendorWithRealToken($vendor, 'old-session');
    $newSessionToken = $vendor->user->createToken('other-device')->plainTextToken;

    // Sanity check: the "other device" token works before the password change.
    // Auth guards are cached per test by Laravel's AuthManager, so we force a
    // fresh guard resolution before every request that authenticates as a
    // different token/user.
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', 'Bearer '.$newSessionToken)
        ->getJson('/api/vendor/profile')
        ->assertOk();

    app('auth')->forgetGuards();
    $this->withHeader('Authorization', 'Bearer '.$oldToken)
        ->postJson('/api/vendor/profile', [
            'password' => 'new-secret-456',
            'current_password' => 'old-secret-123',
        ])
        ->assertOk();

    expect(Hash::check('new-secret-456', $vendor->user->fresh()->password))->toBeTrue();

    // The token used to perform the change is still valid.
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', 'Bearer '.$oldToken)
        ->getJson('/api/vendor/profile')
        ->assertOk();

    // Any other pre-existing token for the same user has been revoked.
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', 'Bearer '.$newSessionToken)
        ->getJson('/api/vendor/profile')
        ->assertStatus(401);
});
