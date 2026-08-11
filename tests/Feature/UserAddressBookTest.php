<?php

use App\Models\User;
use App\Models\UserAddress;
use Laravel\Sanctum\Sanctum;

/**
 * Customer address book (spec §6).
 */
function addressPayload(array $overrides = []): array
{
    return array_merge([
        'label' => UserAddress::LABEL_FARM,
        'recipient_name' => 'Nour Al-Ahmad',
        'phone' => '0991234567',
        'governorate' => 'Rif Dimashq',
        'city' => 'Douma',
        'district' => 'Al-Sahel',
        'street' => 'Main Road 12',
    ], $overrides);
}

it('makes the first saved address the default automatically', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/addresses', addressPayload())
        ->assertCreated()
        ->assertJsonPath('data.is_default', true)
        ->assertJsonPath('data.label', 'farm');
});

it('moves the default flag to exactly one address', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/addresses', addressPayload())->assertCreated();
    $this->postJson('/api/addresses', addressPayload(['city' => 'Harasta', 'is_default' => true]))->assertCreated();

    expect(UserAddress::query()->where('user_id', $user->id)->where('is_default', true)->count())->toBe(1)
        ->and(UserAddress::query()->where('user_id', $user->id)->where('is_default', true)->first()->city)->toBe('Harasta');
});

it('promotes another address when the default is deleted', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $first = $this->postJson('/api/addresses', addressPayload())->json('data.id');
    $this->postJson('/api/addresses', addressPayload(['city' => 'Harasta']))->assertCreated();

    $this->deleteJson("/api/addresses/{$first}")->assertOk();

    expect(UserAddress::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(UserAddress::query()->where('user_id', $user->id)->first()->is_default)->toBeTrue();
});

it('rejects a label the business does not use', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/addresses', addressPayload(['label' => 'spaceship']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['label']);
});

it('never exposes or mutates another customer\'s address', function () {
    $stranger = UserAddress::factory()->create(['user_id' => User::factory()->create()->id]);

    Sanctum::actingAs(User::factory()->create());

    $this->patchJson("/api/addresses/{$stranger->id}", addressPayload())->assertForbidden();
    $this->deleteJson("/api/addresses/{$stranger->id}")->assertForbidden();
    $this->patchJson("/api/addresses/{$stranger->id}/default")->assertForbidden();

    expect($stranger->refresh()->recipient_name)->not->toBe('Nour Al-Ahmad');
});

it('lists only the signed-in customer\'s addresses, default first', function () {
    $user = User::factory()->create();
    UserAddress::factory()->create(['user_id' => $user->id, 'city' => 'Latakia']);
    UserAddress::factory()->default()->create(['user_id' => $user->id, 'city' => 'Tartus']);
    UserAddress::factory()->create(['user_id' => User::factory()->create()->id]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/addresses')->assertOk();

    expect($response->json('data'))->toHaveCount(2)
        ->and($response->json('data.0.city'))->toBe('Tartus');
});
