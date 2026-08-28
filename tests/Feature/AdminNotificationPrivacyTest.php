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

it('isolates notifications, unread counts, and markRead/markAllRead between users (IDOR)', function () {
    Event::fake([AdminNotificationSent::class]);
    $admin = User::factory()->admin()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    Sanctum::actingAs($admin);

    // Two single-recipient notifications, one per user.
    $respA = $this->postJson('/api/admin/notifications/send', [
        'title' => 'For A',
        'body' => 'Only A should see this.',
        'type' => AdminNotification::TYPE_PRIVATE,
        'user_ids' => [$userA->id],
    ])->assertCreated();
    $notifA = AdminNotification::query()->findOrFail($respA->json('data.id'));

    $respB = $this->postJson('/api/admin/notifications/send', [
        'title' => 'For B',
        'body' => 'Only B should see this.',
        'type' => AdminNotification::TYPE_PRIVATE,
        'user_ids' => [$userB->id],
    ])->assertCreated();
    $notifB = AdminNotification::query()->findOrFail($respB->json('data.id'));

    // A multi-recipient announcement reaching exactly those two users.
    $respBoth = $this->postJson('/api/admin/notifications/send', [
        'title' => 'For A and B',
        'body' => 'Both should see this.',
        'type' => AdminNotification::TYPE_PRIVATE,
        'user_ids' => [$userA->id, $userB->id],
    ])->assertCreated();
    $notifBoth = AdminNotification::query()->findOrFail($respBoth->json('data.id'));

    expect($notifBoth->recipients()->pluck('users.id')->sort()->values()->all())
        ->toBe(collect([$userA->id, $userB->id])->sort()->values()->all());

    // A sees only A's + the shared announcement; never B's.
    Sanctum::actingAs($userA);
    $listA = $this->getJson('/api/notifications')->assertOk();
    $idsA = collect($listA->json('data'))->pluck('id')->all();
    expect($idsA)->toContain($notifA->id, $notifBoth->id)
        ->not->toContain($notifB->id);
    expect($listA->json('unread_count'))->toBe(2);

    // B sees only B's + the shared announcement; never A's.
    Sanctum::actingAs($userB);
    $listB = $this->getJson('/api/notifications')->assertOk();
    $idsB = collect($listB->json('data'))->pluck('id')->all();
    expect($idsB)->toContain($notifB->id, $notifBoth->id)
        ->not->toContain($notifA->id);
    expect($listB->json('unread_count'))->toBe(2);

    // A cannot mark B's notification as read: 404, indistinguishable from a
    // nonexistent id, and B's read state is untouched.
    Sanctum::actingAs($userA);
    $this->patchJson("/api/notifications/{$notifB->id}/read")->assertNotFound();
    expect($userB->notificationReads()->where('admin_notification_id', $notifB->id)->exists())->toBeFalse();

    // markAllRead for A only affects A's own notifications.
    $this->postJson('/api/notifications/mark-all-read')->assertOk();
    expect($userA->notificationReads()->pluck('admin_notification_id')->sort()->values()->all())
        ->toBe(collect([$notifA->id, $notifBoth->id])->sort()->values()->all());
    expect($userB->notificationReads()->count())->toBe(0);

    Sanctum::actingAs($userB);
    $unreadBAfter = $this->getJson('/api/notifications')->json('unread_count');
    expect($unreadBAfter)->toBe(2);
});

it('paginates notifications independently per user', function () {
    Event::fake([AdminNotificationSent::class]);
    $admin = User::factory()->admin()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    Sanctum::actingAs($admin);

    foreach (range(1, 3) as $i) {
        $this->postJson('/api/admin/notifications/send', [
            'title' => "A #{$i}",
            'body' => 'body',
            'type' => AdminNotification::TYPE_PRIVATE,
            'user_ids' => [$userA->id],
        ])->assertCreated();
    }

    $this->postJson('/api/admin/notifications/send', [
        'title' => 'B #1',
        'body' => 'body',
        'type' => AdminNotification::TYPE_PRIVATE,
        'user_ids' => [$userB->id],
    ])->assertCreated();

    Sanctum::actingAs($userA);
    $this->getJson('/api/notifications?per_page=15')
        ->assertOk()
        ->assertJsonPath('meta.total', 3);

    Sanctum::actingAs($userB);
    $this->getJson('/api/notifications?per_page=15')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});
