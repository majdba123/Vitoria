<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

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
