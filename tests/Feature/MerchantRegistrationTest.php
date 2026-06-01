<?php

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function registrationPayload(array $overrides = []): array
{
    $city = City::query()->create(['name' => 'Damascus']);

    return array_merge([
        'account_type' => 'user',
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

test('merchant registration requires merchant fields and commercial register upload', function () {
    $response = $this->postJson('/api/auth/register', registrationPayload([
        'account_type' => 'vendor',
    ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'store_name',
            'business_type',
            'category_ids',
            'commercial_register_file',
        ]);
});

test('merchant self registration creates a pending inactive vendor with stored commercial register and selected categories', function () {
    Storage::fake('local');
    $category = Category::query()->create(['name' => 'Agricultural Products']);
    $otherCategory = Category::query()->create(['name' => 'Agricultural Equipment']);

    $response = $this->post('/api/auth/register', registrationPayload([
        'account_type' => 'vendor',
        'store_name' => 'Bayer Market',
        'business_type' => Vendor::BUSINESS_TYPE_BOTH,
        'category_ids' => [$category->id, $otherCategory->id],
        'description' => 'Local merchant store.',
        'address' => 'Damascus',
        'commercial_register_file' => UploadedFile::fake()->create('commercial-register.pdf', 120, 'application/pdf'),
        'phone_number' => '0991000002',
        'national_id' => '1234567891',
        'membership_number' => 'MEM-100002',
        'email' => 'merchant@example.com',
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.user.type', User::TYPE_VENDOR);

    $vendor = Vendor::query()->firstOrFail();

    expect($vendor->store_name)->toBe('Bayer Market')
        ->and($vendor->status)->toBe(Vendor::STATUS_PENDING)
        ->and($vendor->is_active)->toBeFalse()
        ->and($vendor->registration_source)->toBe(Vendor::REGISTRATION_SOURCE_SELF)
        ->and((float) $vendor->latitude)->toBe(33.5138)
        ->and((float) $vendor->longitude)->toBe(36.2765)
        ->and($vendor->address)->toBe('Damascus')
        ->and($vendor->commercial_register_file)->not->toBeNull();

    Storage::disk('local')->assertExists($vendor->commercial_register_file);
    expect($vendor->categories()->pluck('categories.id')->sort()->values()->all())->toBe([
        $category->id,
        $otherCategory->id,
    ]);
});

test('merchant registration fails with an invalid category', function () {
    Storage::fake('local');

    $response = $this->postJson('/api/auth/register', registrationPayload([
        'account_type' => 'vendor',
        'store_name' => 'Invalid Category Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'category_ids' => [999999],
        'commercial_register_file' => UploadedFile::fake()->create('commercial-register.pdf', 120, 'application/pdf'),
        'phone_number' => '0992000099',
        'national_id' => '2234567899',
        'membership_number' => 'MEM-INVALID-CATEGORY',
        'email' => 'invalid-category@example.com',
    ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['category_ids.0']);
});

test('merchant registration rejects categories outside selected business type', function () {
    Storage::fake('local');
    $veterinaryCategory = Category::query()->create([
        'name' => 'Veterinary Only Category',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $response = $this->postJson('/api/auth/register', registrationPayload([
        'account_type' => 'vendor',
        'store_name' => 'Mismatched Category Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'category_ids' => [$veterinaryCategory->id],
        'commercial_register_file' => UploadedFile::fake()->create('commercial-register.pdf', 120, 'application/pdf'),
        'phone_number' => '0992000088',
        'national_id' => '2234567888',
        'membership_number' => 'MEM-MISMATCH-CATEGORY',
        'email' => 'mismatch-category@example.com',
    ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['category_ids']);
});

test('merchant registration accepts both business type with agriculture and veterinary categories', function () {
    Storage::fake('local');
    $agricultureCategory = Category::query()->create([
        'name' => 'Both Agriculture Category',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $veterinaryCategory = Category::query()->create([
        'name' => 'Both Veterinary Category',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $response = $this->post('/api/auth/register', registrationPayload([
        'account_type' => 'vendor',
        'store_name' => 'Both Type Store',
        'business_type' => Vendor::BUSINESS_TYPE_BOTH,
        'category_ids' => [$agricultureCategory->id, $veterinaryCategory->id],
        'commercial_register_file' => UploadedFile::fake()->create('commercial-register.pdf', 120, 'application/pdf'),
        'phone_number' => '0992000089',
        'national_id' => '2234567889',
        'membership_number' => 'MEM-BOTH-CATEGORY',
        'email' => 'both-category@example.com',
    ]));

    $response->assertCreated();

    $vendor = Vendor::query()->where('store_name', 'Both Type Store')->firstOrFail();

    expect($vendor->business_type)->toBe(Vendor::BUSINESS_TYPE_BOTH)
        ->and($vendor->categories()->pluck('categories.id')->sort()->values()->all())->toBe([
            $agricultureCategory->id,
            $veterinaryCategory->id,
        ]);
});

test('merchant registration accepts document and image commercial register files', function (
    string $filename,
    string $mimeType,
    int $index,
) {
    Storage::fake('local');
    $category = Category::query()->create(['name' => 'Document Category '.$index]);

    $response = $this->post('/api/auth/register', registrationPayload([
        'account_type' => 'vendor',
        'store_name' => 'Document Store '.$index,
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'category_id' => $category->id,
        'commercial_register_file' => UploadedFile::fake()->create($filename, 120, $mimeType),
        'phone_number' => '099300000'.$index,
        'national_id' => '323456789'.$index,
        'membership_number' => 'MEM-DOC-100'.$index,
        'email' => 'merchant-doc-'.$index.'@example.com',
    ]));

    $response->assertCreated();

    $vendor = Vendor::query()->where('store_name', 'Document Store '.$index)->firstOrFail();

    expect($vendor->commercial_register_file)->not->toBeNull();
    Storage::disk('local')->assertExists($vendor->commercial_register_file);
})->with([
    ['commercial-register.pdf', 'application/pdf', 1],
    ['commercial-register.doc', 'application/msword', 2],
    ['commercial-register.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 3],
    ['commercial-register.jpg', 'image/jpeg', 4],
    ['commercial-register.jpeg', 'image/jpeg', 5],
    ['commercial-register.png', 'image/png', 6],
]);

test('merchant registration rejects unsafe commercial register files', function () {
    Storage::fake('local');
    $category = Category::query()->create(['name' => 'Unsafe Category']);

    $response = $this->postJson('/api/auth/register', registrationPayload([
        'account_type' => 'vendor',
        'store_name' => 'Unsafe Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'category_id' => $category->id,
        'commercial_register_file' => UploadedFile::fake()->create('commercial-register.php', 10, 'application/x-php'),
        'phone_number' => '0994000001',
        'national_id' => '4234567890',
        'membership_number' => 'MEM-UNSAFE-100',
        'email' => 'unsafe-merchant@example.com',
    ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['commercial_register_file']);

    $this->assertDatabaseMissing('vendors', [
        'store_name' => 'Unsafe Store',
    ]);
});

test('pending merchant cannot access vendor-only api features', function () {
    $user = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::factory()->for($user)->pending()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/vendor/profile')
        ->assertForbidden()
        ->assertJsonPath('message', 'Your vendor account is pending admin approval.');
});
