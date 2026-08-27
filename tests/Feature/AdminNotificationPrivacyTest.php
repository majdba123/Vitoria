<?php

use App\Events\AdminNotificationSent;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

it('sends an admin notification only to selected users', function () {
    Event::fake([AdminNotificationSent::class]);
    $admin = User::factory()->admin()->create();
    $recipient = User::factory()->create();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/admin/notifications/send', [
        'title' => 'Private account update',
        'body' => 'This message belongs to one account.',
        'type' => AdminNotification::TYPE_PRIVATE,
        'user_ids' => [$recipient->id],
    ])->assertCreated()
        ->assertJsonPath('data.type', AdminNotification::TYPE_PRIVATE)
        ->assertJsonPath('data.recipient_count', 1);

    $notification = AdminNotification::query()->findOrFail($response->json('data.id'));

    expect($notification->recipients()->pluck('users.id')->all())->toBe([$recipient->id]);

    Event::assertDispatched(AdminNotificationSent::class, fn (AdminNotificationSent $event): bool => $event->recipientUserIds === [$recipient->id]);

    Sanctum::actingAs($otherUser);
    $this->getJson('/api/notifications')->assertOk()
        ->assertJsonMissing(['id' => $notification->id]);

    Sanctum::actingAs($recipient);
    $this->getJson('/api/notifications')->assertOk()
        ->assertJsonPath('data.0.id', $notification->id);
});

it('rejects public admin notification broadcasts', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/admin/notifications/send', [
        'title' => 'Unsafe broadcast',
        'body' => 'This must not be sent to every user.',
        'type' => AdminNotification::TYPE_PUBLIC,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'user_ids']);

    expect(AdminNotification::query()->count())->toBe(0);
});
