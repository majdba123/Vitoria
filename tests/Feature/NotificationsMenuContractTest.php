<?php

use App\Models\AdminNotification;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function publicNotification(array $attributes = []): AdminNotification
{
    return AdminNotification::query()->create(array_merge([
        'title' => 'Notification title',
        'body' => 'Notification body',
        'type' => AdminNotification::TYPE_PUBLIC,
        'action_type' => null,
        'action_id' => null,
    ], $attributes));
}

it('returns the empty notifications pagination contract', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/notifications')->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('unread_count', 0)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 1);
});

it('paginates read and unread notifications with optional product and order actions', function () {
    $user = User::factory()->create();
    $plain = publicNotification();
    publicNotification(['action_type' => AdminNotification::ACTION_PRODUCT, 'action_id' => 15]);
    publicNotification(['action_type' => AdminNotification::ACTION_ORDER, 'action_id' => 27]);
    $user->notificationReads()->attach($plain->id, ['read_at' => now()]);
    Sanctum::actingAs($user);

    $first = $this->getJson('/api/notifications?per_page=2&page=1')->assertOk();
    $second = $this->getJson('/api/notifications?per_page=2&page=2')->assertOk();

    expect($first->json('meta.total'))->toBe(3)
        ->and($first->json('meta.last_page'))->toBe(2)
        ->and($first->json('unread_count'))->toBe(2)
        ->and(collect($first->json('data'))->concat($second->json('data'))->pluck('action_type')->filter()->all())->toEqualCanonicalizing([
            AdminNotification::ACTION_PRODUCT,
            AdminNotification::ACTION_ORDER,
        ])
        ->and(collect($first->json('data'))->concat($second->json('data'))->pluck('read_at')->filter()->count())->toBe(1);
});

it('marks one notification and then all visible notifications as read', function () {
    $user = User::factory()->create();
    $first = publicNotification();
    publicNotification();
    Sanctum::actingAs($user);

    $this->patchJson('/api/notifications/'.$first->id.'/read')->assertOk();
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(1);

    $this->postJson('/api/notifications/mark-all-read')->assertOk();
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(0);
});

it('keeps private notifications visible only to their recipients', function () {
    $recipient = User::factory()->create();
    $otherUser = User::factory()->create();
    $notification = AdminNotification::query()->create([
        'title' => 'Private notification',
        'body' => 'Only the recipient should see this.',
        'type' => AdminNotification::TYPE_PRIVATE,
    ]);
    $notification->recipients()->attach($recipient->id);

    Sanctum::actingAs($otherUser);

    $this->getJson('/api/notifications')->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('unread_count', 0);

    $this->patchJson('/api/notifications/'.$notification->id.'/read')
        ->assertNotFound();

    $this->postJson('/api/notifications/mark-all-read')
        ->assertOk()
        ->assertJsonPath('data.marked', 0);

    Sanctum::actingAs($recipient);

    $this->getJson('/api/notifications')->assertOk()
        ->assertJsonPath('data.0.id', $notification->id)
        ->assertJsonPath('unread_count', 1);
});
