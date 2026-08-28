<?php

use App\Models\City;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

function registrationPayload(array $overrides = []): array
{
    $city = City::query()->create(['name' => 'Damascus']);

    return array_merge([
        'name' => 'Majd Bayer',
        'phone_number' => '0991000001',
        'national_id' => '1234567890',
        'age' => 32,
        'membership_number' => 'MEM-100001',
        'city_id' => $city->id,
        'latitude' => 33.5138,
        'longitude' => 36.2765,
        'email' => 'majd@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

test('normal user registration still creates a normal user account', function () {
    $response = $this->post('/api/auth/register', registrationPayload());

    $response->assertCreated()
        ->assertJsonPath('data.user.type', User::TYPE_USER);

    $this->assertDatabaseHas('users', [
        'phone_number' => '0991000001',
        'type' => User::TYPE_USER,
    ]);
    $this->assertDatabaseCount('vendors', 0);
});

test('registration does not require map coordinates', function () {
    $payload = registrationPayload([
        'phone_number' => '0991000098',
        'national_id' => '1234567898',
        'membership_number' => 'MEM-100098',
        'email' => 'no-location@example.com',
    ]);

    unset($payload['latitude'], $payload['longitude']);

    $response = $this->post('/api/auth/register', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.user.type', User::TYPE_USER);

    $this->assertDatabaseHas('users', [
        'phone_number' => '0991000098',
        'latitude' => null,
        'longitude' => null,
    ]);
});

// Public self-registration must always create a customer account. Vendor
// accounts are created only through the admin-managed flow
// (App\Http\Controllers\Api\Admin\VendorController). A forged public
// registration request cannot choose its own role/type — the field isn't
// even part of the validated payload, and AuthService hard-codes the role,
// so extra/unexpected keys in the request body are ignored entirely.
test('a forged registration request with a vendor type creates a customer, not a vendor', function () {
    $response = $this->postJson('/api/auth/register', registrationPayload([
        'phone_number' => '0991000002',
        'national_id' => '1234567891',
        'membership_number' => 'MEM-100002',
        'email' => 'merchant@example.com',
        'type' => User::TYPE_VENDOR,
        'account_type' => 'vendor',
        'role' => 'vendor',
        'store_name' => 'Forged Store',
        'business_type' => 'agriculture',
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.user.type', User::TYPE_USER);

    $this->assertDatabaseHas('users', [
        'email' => 'merchant@example.com',
        'type' => User::TYPE_USER,
    ]);
    $this->assertDatabaseCount('vendors', 0);
});

test('a forged registration request cannot escalate to admin, employee, or syndicate roles', function (int $forgedType) {
    $response = $this->postJson('/api/auth/register', registrationPayload([
        'phone_number' => '099200'.$forgedType.'001',
        'national_id' => '99900'.$forgedType.'0001',
        'membership_number' => 'MEM-ESCALATE-'.$forgedType,
        'email' => 'escalate-'.$forgedType.'@example.com',
        'type' => $forgedType,
        'role' => $forgedType,
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.user.type', User::TYPE_USER);

    $this->assertDatabaseHas('users', [
        'email' => 'escalate-'.$forgedType.'@example.com',
        'type' => User::TYPE_USER,
    ]);
})->with([
    'admin' => [User::TYPE_ADMIN],
    'employee' => [User::TYPE_EMPLOYEE],
    'syndicate' => [User::TYPE_SYNDICATE],
]);

test('pending merchant cannot access vendor-only api features', function () {
    $user = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::factory()->for($user)->pending()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/vendor/profile')
        ->assertForbidden()
        ->assertJsonPath('message', 'Your vendor account is pending admin approval.');
});

// Vendor accounts are created only through the admin-managed flow; this
// exercises that an existing (approved) vendor account can still log in
// through the same public /api/auth/login endpoint used by customers —
// only public *registration* is restricted, not vendor authentication.
test('an existing vendor account can still log in', function () {
    $city = City::query()->create(['name' => 'Aleppo']);
    $vendorUser = User::factory()->create([
        'type' => User::TYPE_VENDOR,
        'city_id' => $city->id,
        'phone_number' => '0993000001',
        'password' => Hash::make('vendor-password'),
    ]);
    Vendor::factory()->for($vendorUser)->create([
        'status' => Vendor::STATUS_ACTIVE,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'phone_number' => '0993000001',
        'password' => 'vendor-password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.type', User::TYPE_VENDOR);
});
