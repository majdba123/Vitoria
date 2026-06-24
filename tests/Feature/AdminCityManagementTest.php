<?php

use App\Models\City;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

function actingAsCityAdmin(): User
{
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    return $admin;
}

test('admin can create update list and view cities', function () {
    actingAsCityAdmin();

    $createResponse = $this->postJson('/api/admin/cities', [
        'name' => 'Damascus',
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Damascus')
        ->assertJsonPath('data.vendors_count', 0);

    $cityId = $createResponse->json('data.id');

    $this->getJson('/api/admin/cities?search=Dama')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'Damascus');

    $this->getJson('/api/admin/cities/'.$cityId)
        ->assertOk()
        ->assertJsonPath('data.name', 'Damascus');

    $this->putJson('/api/admin/cities/'.$cityId, [
        'name' => 'Damascus Updated',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Damascus Updated');
});

test('admin cannot create duplicate city names', function () {
    actingAsCityAdmin();
    City::query()->create(['name' => 'Homs']);

    $this->postJson('/api/admin/cities', [
        'name' => 'Homs',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('admin can delete a city with no vendors and cannot delete one in use', function () {
    actingAsCityAdmin();

    $deletableCity = City::query()->create(['name' => 'Latakia']);
    $usedCity = City::query()->create(['name' => 'Tartus']);
    Vendor::factory()->create([
        'city_id' => $usedCity->id,
    ]);

    $this->deleteJson('/api/admin/cities/'.$deletableCity->id)
        ->assertOk();

    $this->assertDatabaseMissing('cities', [
        'id' => $deletableCity->id,
    ]);

    $this->deleteJson('/api/admin/cities/'.$usedCity->id)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This city cannot be deleted while vendors are assigned to it.');
});
