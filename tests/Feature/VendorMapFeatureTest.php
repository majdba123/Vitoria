<?php

use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Models\Role;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/**
 * Admin + Syndicate vendor map (Table/Map inside the existing Vendors views).
 *
 * The load-bearing properties: the map is admin/syndicate only, it is
 * governorate-level only (a vendor's exact `latitude`/`longitude` never
 * leaves the service - only a per-governorate count), it never carries
 * account credentials or financial columns, and the syndicate map can never
 * see past the canonical syndicate vendor scope no matter which filters are
 * supplied.
 */
function mapCity(string $name): City
{
    return City::query()->create(['name' => $name]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function mapVendor(string $storeName, array $attributes = []): Vendor
{
    return Vendor::factory()->create([
        'store_name' => $storeName,
        'address' => $storeName.' Street 14',
        ...$attributes,
    ]);
}

function mapSyndicateUser(string $type): User
{
    $user = User::factory()->syndicate()->create();

    Syndicate::factory()->for($user)->create([
        'type' => $type,
        'status' => Syndicate::STATUS_ACTIVE,
    ]);

    return $user;
}

const MAP_FORBIDDEN_FIELDS = [
    'user',
    'commercial_register_file',
    'commercial_register_url',
    'paid_amount',
    'national_id',
    'phone_number',
    'email',
];

/*
|--------------------------------------------------------------------------
| Access control
|--------------------------------------------------------------------------
*/

test('a guest cannot reach the admin vendor map', function () {
    $this->getJson('/api/admin/vendors/map')->assertUnauthorized();
});

test('a non-admin cannot reach the admin vendor map', function () {
    Sanctum::actingAs(User::factory()->create(['type' => User::TYPE_USER]));

    $this->getJson('/api/admin/vendors/map')->assertForbidden();
});

test('a guest cannot reach the syndicate vendor map', function () {
    $this->getJson('/api/syndicate/vendors/map')->assertUnauthorized();
});

test('a vendor cannot reach the syndicate vendor map', function () {
    Sanctum::actingAs(User::factory()->create(['type' => User::TYPE_VENDOR]));

    $this->getJson('/api/syndicate/vendors/map')->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Admin map contract
|--------------------------------------------------------------------------
*/

test('the admin map returns per-governorate counts, unassigned vendors, counts and city options', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $city = mapCity('Homs');

    mapVendor('Assigned Store', ['city_id' => $city->id]);
    $unassigned = mapVendor('Unassigned Store', ['city_id' => null]);

    $response = $this->getJson('/api/admin/vendors/map')
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => [
                'governorates' => [['key', 'name_en', 'name_ar', 'lat', 'lng', 'vendor_count']],
                'unassigned' => [['id', 'store_name', 'address', 'city_id', 'city_name', 'is_active', 'status', 'products_count', 'edit_url']],
                'counts' => ['total', 'assigned', 'unassigned'],
                'cities' => [['id', 'name']],
            ],
        ])
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.assigned', 1)
        ->assertJsonPath('data.counts.unassigned', 1)
        ->assertJsonPath('data.unassigned.0.id', $unassigned->id)
        ->assertJsonPath('data.unassigned.0.address', 'Unassigned Store Street 14');

    $payload = $response->json('data');

    expect($payload['governorates'])->toHaveCount(14);
    expect(collect($payload['governorates'])->firstWhere('key', 'homs')['vendor_count'])->toBe(1);
    expect($payload['unassigned'][0])->not->toHaveKey('latitude')->not->toHaveKey('longitude');
});

test('the admin map narrows by city, business type and status', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $homs = mapCity('Homs');
    $hama = mapCity('Hama');

    mapVendor('Homs Agriculture', [
        'city_id' => $homs->id,
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    mapVendor('Hama Veterinary', [
        'city_id' => $hama->id,
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);

    $this->getJson('/api/admin/vendors/map?city_id='.$homs->id)
        ->assertOk()
        ->assertJsonPath('data.counts.total', 1);
    expect(collect($this->getJson('/api/admin/vendors/map?city_id='.$homs->id)->json('data.governorates'))->firstWhere('key', 'homs')['vendor_count'])->toBe(1);

    $this->getJson('/api/admin/vendors/map?business_type='.Vendor::BUSINESS_TYPE_VETERINARY)
        ->assertOk()
        ->assertJsonPath('data.counts.total', 1);
    expect(collect($this->getJson('/api/admin/vendors/map?business_type='.Vendor::BUSINESS_TYPE_VETERINARY)->json('data.governorates'))->firstWhere('key', 'hama')['vendor_count'])->toBe(1);

    $this->getJson('/api/admin/vendors/map?status='.Vendor::STATUS_INACTIVE)
        ->assertOk()
        ->assertJsonPath('data.counts.total', 0);

    $this->getJson('/api/admin/vendors/map?city_id=0')->assertUnprocessable();
    $this->getJson('/api/admin/vendors/map?business_type=aquaculture')->assertUnprocessable();
});

test('the admin map never exposes credentials, owner contact details, financial columns or exact coordinates', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    mapVendor('Sensitive Store', [
        'city_id' => mapCity('Homs')->id,
        'latitude' => 33.5,
        'longitude' => 36.3,
        'commercial_register_file' => 'commercial-registers/secret.pdf',
        'paid_amount' => 4200,
    ]);
    mapVendor('Sensitive Unassigned', ['city_id' => null, 'latitude' => null, 'longitude' => null]);

    $payload = $this->getJson('/api/admin/vendors/map')->assertOk()->json('data');

    foreach ($payload['unassigned'] as $point) {
        foreach ([...MAP_FORBIDDEN_FIELDS, 'latitude', 'longitude'] as $field) {
            expect($point)->not->toHaveKey($field);
        }
    }

    // The governorate objects themselves never carry per-vendor data at all.
    foreach ($payload['governorates'] as $governorate) {
        expect($governorate)->toHaveKeys(['key', 'name_en', 'name_ar', 'lat', 'lng', 'vendor_count'])
            ->and(array_keys($governorate))->toHaveCount(6);
    }
});

test('the admin vendors/map route resolves without touching the {vendor} binding', function () {
    $route = Route::getRoutes()->getByName('api.admin.vendors.map');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('api/admin/vendors/map')
        ->and($route->parameterNames())->toBe([]);

    Sanctum::actingAs(User::factory()->admin()->create());

    // A {vendor} binding would answer 404 for the literal "map" segment.
    $this->getJson('/api/admin/vendors/map')->assertOk();
});

/*
|--------------------------------------------------------------------------
| Managing a location
|--------------------------------------------------------------------------
*/

test('an admin can create, update and clear a vendor location', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $city = mapCity('Latakia');
    $category = Category::query()->create(['name' => 'Map Feed', 'type' => Category::TYPE_AGRICULTURE]);

    $vendorId = $this->postJson('/api/admin/vendors', [
        'name' => 'Located Owner',
        'password' => 'password',
        'phone_number' => '0994000101',
        'national_id' => '3334000101',
        'store_name' => 'Located Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'city_id' => $city->id,
        'category_ids' => [$category->id],
        'latitude' => 35.5311,
        'longitude' => 35.7796,
    ])->assertCreated()->json('data.id');

    expect(Vendor::query()->findOrFail($vendorId))
        ->latitude->toEqual(35.5311)
        ->longitude->toEqual(35.7796);

    $this->putJson('/api/admin/vendors/'.$vendorId, ['latitude' => 34.8021, 'longitude' => 38.9968])
        ->assertOk();

    expect(Vendor::query()->findOrFail($vendorId))
        ->latitude->toEqual(34.8021)
        ->longitude->toEqual(38.9968);

    $this->putJson('/api/admin/vendors/'.$vendorId, ['latitude' => null, 'longitude' => null])
        ->assertOk();

    $cleared = Vendor::query()->findOrFail($vendorId);

    expect($cleared->latitude)->toBeNull()
        ->and($cleared->longitude)->toBeNull();
});

test('an admin cannot save half a coordinate pair or an out-of-range coordinate', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $vendor = mapVendor('Half Pair Store');

    $this->putJson('/api/admin/vendors/'.$vendor->id, ['latitude' => 33.5])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);

    $this->putJson('/api/admin/vendors/'.$vendor->id, ['longitude' => 36.3])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);

    $this->putJson('/api/admin/vendors/'.$vendor->id, ['latitude' => 91, 'longitude' => 36.3])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);

    $this->putJson('/api/admin/vendors/'.$vendor->id, ['latitude' => 33.5, 'longitude' => 181])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);

    $vendor->refresh();

    expect($vendor->latitude)->toBeNull()
        ->and($vendor->longitude)->toBeNull();
});

test('a vendor owner can manage their own store location', function () {
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $owner->id]);

    Sanctum::actingAs($owner);

    $this->postJson('/api/vendor/profile', ['latitude' => 36.2021, 'longitude' => 37.1343])
        ->assertOk()
        ->assertJsonPath('data.vendor.latitude', 36.2021)
        ->assertJsonPath('data.vendor.longitude', 37.1343);

    expect($vendor->fresh())
        ->latitude->toEqual(36.2021)
        ->longitude->toEqual(37.1343);

    $this->postJson('/api/vendor/profile', ['latitude' => 36.2021])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);
});

test('vendor staff without profile.manage cannot manage the store location', function () {
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $owner->id, 'latitude' => 33.5, 'longitude' => 36.3]);
    $staff = User::factory()->create(['type' => User::TYPE_VENDOR]);

    VendorMember::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $staff->id,
        'role_id' => Role::query()->where('key', Role::KEY_VIEWER)->value('id'),
        'status' => VendorMember::STATUS_ACTIVE,
    ]);

    Sanctum::actingAs($staff);

    $this->postJson('/api/vendor/profile', ['latitude' => 0, 'longitude' => 0])->assertForbidden();
    $this->postJson('/api/vendor/profile', ['latitude' => null, 'longitude' => null])->assertForbidden();

    expect($vendor->fresh())
        ->latitude->toEqual(33.5)
        ->longitude->toEqual(36.3);
});

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

test('merchant registration stores a valid coordinate pair on the vendor', function () {
    $city = mapCity('Tartus');
    $category = Category::query()->create(['name' => 'Registration Feed', 'type' => Category::TYPE_AGRICULTURE]);

    $this->postJson('/api/auth/register', [
        'account_type' => 'vendor',
        'name' => 'Pinned Merchant',
        'phone_number' => '0995000101',
        'national_id' => '4445000101',
        'age' => 34,
        'membership_number' => 'MAP-0001',
        'city_id' => $city->id,
        'email' => 'pinned-merchant@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Pinned Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'category_ids' => [$category->id],
        'latitude' => 34.8959,
        'longitude' => 35.8867,
        'commercial_register_file' => \Illuminate\Http\UploadedFile::fake()->create('register.pdf', 40, 'application/pdf'),
    ])->assertCreated();

    expect(Vendor::query()->where('store_name', 'Pinned Store')->firstOrFail())
        ->latitude->toEqual(34.8959)
        ->longitude->toEqual(35.8867);
});

test('merchant registration rejects half pairs and out-of-range coordinates', function () {
    $city = mapCity('Daraa');
    $category = Category::query()->create(['name' => 'Rejection Feed', 'type' => Category::TYPE_AGRICULTURE]);

    $base = [
        'account_type' => 'vendor',
        'name' => 'Rejected Merchant',
        'phone_number' => '0995000202',
        'national_id' => '4445000202',
        'age' => 30,
        'membership_number' => 'MAP-0002',
        'city_id' => $city->id,
        'email' => 'rejected-merchant@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Rejected Store',
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'category_ids' => [$category->id],
        'commercial_register_file' => \Illuminate\Http\UploadedFile::fake()->create('register.pdf', 40, 'application/pdf'),
    ];

    $this->postJson('/api/auth/register', [...$base, 'latitude' => 32.6189])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);

    $this->postJson('/api/auth/register', [...$base, 'longitude' => 36.1021])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);

    $this->postJson('/api/auth/register', [...$base, 'latitude' => -91, 'longitude' => 36.1021])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);

    $this->postJson('/api/auth/register', [...$base, 'latitude' => 32.6189, 'longitude' => -181])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);

    $this->assertDatabaseMissing('vendors', ['store_name' => 'Rejected Store']);
});

/*
|--------------------------------------------------------------------------
| Syndicate scope
|--------------------------------------------------------------------------
*/

/**
 * One fixture used by every syndicate scope assertion below:
 * - an agriculture-typed vendor and a veterinary-typed vendor,
 * - a `both` vendor that belongs to each syndicate,
 * - a vendor whose business_type is opposite but whose category matches, which
 *   the canonical scope includes through its categories.
 *
 * @return array<string, Vendor>
 */
function mapSyndicateFixture(): array
{
    $city = mapCity('Damascus');
    $agricultureCategory = Category::query()->create(['name' => 'Map Seeds', 'type' => Category::TYPE_AGRICULTURE]);

    $matching = mapVendor('Category Matched Store', [
        'city_id' => $city->id,
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);
    $matching->categories()->sync([$agricultureCategory->id]);

    return [
        'agriculture' => mapVendor('Agriculture Only Store', [
            'city_id' => $city->id,
            'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        ]),
        'veterinary' => mapVendor('Veterinary Only Store', [
            'city_id' => $city->id,
            'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
        ]),
        'both' => mapVendor('Both Store', [
            'city_id' => $city->id,
            'business_type' => Vendor::BUSINESS_TYPE_BOTH,
        ]),
        'matching' => $matching,
        'city' => $city,
    ];
}

/**
 * The Damascus governorate's vendor_count from a map payload - every
 * `mapSyndicateFixture()` vendor lives in the same Damascus city, so this is
 * the one number that reflects exactly which of them the current scope let
 * through, without the payload ever naming an individual vendor.
 */
function damascusCount(array $payload): int
{
    return collect($payload['governorates'])->firstWhere('key', 'damascus')['vendor_count'];
}

test('an agriculture syndicate map shows only vendors inside its canonical scope', function () {
    mapSyndicateFixture();
    Sanctum::actingAs(mapSyndicateUser(Category::TYPE_AGRICULTURE));

    $payload = $this->getJson('/api/syndicate/vendors/map')->assertOk()->json('data');

    // Agriculture Only Store, Both Store, Category Matched Store - not Veterinary Only Store.
    expect($payload['counts']['total'])->toBe(3)
        ->and(damascusCount($payload))->toBe(3);
});

test('a veterinary syndicate map shows only vendors inside its canonical scope', function () {
    mapSyndicateFixture();
    Sanctum::actingAs(mapSyndicateUser(Category::TYPE_VETERINARY));

    $payload = $this->getJson('/api/syndicate/vendors/map')->assertOk()->json('data');

    // Both Store, Category Matched Store, Veterinary Only Store - not Agriculture Only Store.
    expect($payload['counts']['total'])->toBe(3)
        ->and(damascusCount($payload))->toBe(3);
});

test('syndicate map filters can only narrow the scope, never widen it', function () {
    $fixture = mapSyndicateFixture();
    Sanctum::actingAs(mapSyndicateUser(Category::TYPE_AGRICULTURE));

    // The opposite-domain vendor stays absent even when its own business type
    // and city are asked for explicitly.
    $payload = $this->getJson('/api/syndicate/vendors/map?business_type='.Vendor::BUSINESS_TYPE_VETERINARY.'&city_id='.$fixture['city']->id)
        ->assertOk()
        ->json('data');

    expect($payload['counts']['total'])->toBe(1)
        ->and(damascusCount($payload))->toBe(1);

    // City options are drawn from the scope, so they cannot advertise a city the
    // syndicate has no vendors in.
    $foreignCity = mapCity('Deir ez-Zor');
    mapVendor('Foreign Vet Store', [
        'city_id' => $foreignCity->id,
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);

    $refreshed = $this->getJson('/api/syndicate/vendors/map')->assertOk()->json('data');

    expect(collect($refreshed['cities'])->pluck('name'))->not->toContain('Deir ez-Zor');

    $this->getJson('/api/syndicate/vendors/map?city_id='.$foreignCity->id)
        ->assertOk()
        ->assertJsonPath('data.counts.total', 0);
});

test('the syndicate map payload carries no admin URLs, credentials, financial columns or exact coordinates', function () {
    mapSyndicateFixture();
    mapVendor('Syndicate Unassigned Store', [
        'city_id' => null,
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'latitude' => null,
        'longitude' => null,
        'commercial_register_file' => 'commercial-registers/secret.pdf',
        'paid_amount' => 900,
    ]);

    Sanctum::actingAs(mapSyndicateUser(Category::TYPE_AGRICULTURE));

    $payload = $this->getJson('/api/syndicate/vendors/map')->assertOk()->json('data');

    expect($payload['unassigned'])->not->toBeEmpty();

    foreach ($payload['unassigned'] as $point) {
        foreach ([...MAP_FORBIDDEN_FIELDS, 'edit_url', 'latitude', 'longitude'] as $field) {
            expect($point)->not->toHaveKey($field);
        }
    }
});

test('syndicate map counts stay inside the syndicate scope', function () {
    mapSyndicateFixture();
    mapVendor('Scoped Unassigned Store', [
        'city_id' => null,
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    mapVendor('Out Of Scope Unassigned Store', [
        'city_id' => null,
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);

    Sanctum::actingAs(mapSyndicateUser(Category::TYPE_AGRICULTURE));

    $this->getJson('/api/syndicate/vendors/map')
        ->assertOk()
        ->assertJsonPath('data.counts.total', 4)
        ->assertJsonPath('data.counts.assigned', 3)
        ->assertJsonPath('data.counts.unassigned', 1);
});

/*
|--------------------------------------------------------------------------
| Query cost
|--------------------------------------------------------------------------
*/

test('the map query count does not grow with the number of vendors', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $city = mapCity('Idlib');
    $category = Category::query()->create(['name' => 'Map Load', 'type' => Category::TYPE_AGRICULTURE]);

    $seed = function (int $count, int $offset) use ($city, $category): void {
        for ($index = 0; $index < $count; $index++) {
            $vendor = mapVendor('Load Store '.($offset + $index), [
                'city_id' => $city->id,
                'latitude' => 35.93 + ($index / 1000),
                'longitude' => 36.63 + ($index / 1000),
            ]);
            $vendor->categories()->sync([$category->id]);
            Product::factory()->for($vendor)->create(['category_id' => $category->id]);
        }
    };

    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/admin/vendors/map')->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    $seed(2, 0);
    $baseline = $countQueries();

    $seed(8, 2);
    $grown = $countQueries();

    // The greater-than-zero guard keeps this from passing vacuously if the
    // query log ever stops recording.
    expect($baseline)->toBeGreaterThan(0)
        ->and($grown)->toBe($baseline);
});

/*
|--------------------------------------------------------------------------
| Existing contracts stay put
|--------------------------------------------------------------------------
*/

test('public vendor exposure is unchanged by the map feature', function () {
    $category = Category::query()->create(['name' => 'Public Map Category', 'type' => Category::TYPE_AGRICULTURE]);
    $vendor = mapVendor('Public Store', ['latitude' => 33.5, 'longitude' => 36.3]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 5,
    ]);

    $this->getJson('/api/products/'.$product->id)
        ->assertOk()
        ->assertJsonMissingPath('data.vendor.user')
        ->assertJsonMissingPath('data.vendor.commercial_register_file')
        ->assertJsonMissingPath('data.vendor.paid_amount');

    $this->getJson('/api/admin/vendors/map')->assertUnauthorized();
    $this->getJson('/api/syndicate/vendors/map')->assertUnauthorized();
});

test('the admin and syndicate vendor list contracts still answer as before', function () {
    $fixture = mapSyndicateFixture();

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson('/api/admin/vendors')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

    $this->getJson('/api/admin/vendors/'.$fixture['agriculture']->id)
        ->assertOk()
        ->assertJsonPath('data.id', $fixture['agriculture']->id);

    Sanctum::actingAs(mapSyndicateUser(Category::TYPE_AGRICULTURE));
    $this->getJson('/api/syndicate/vendors')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 3);
});
