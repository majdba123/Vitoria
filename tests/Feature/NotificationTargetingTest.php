<?php

use App\Events\AdminNotificationSent;
use App\Models\AdminNotification;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\NotificationService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

/**
 * @return array<string, mixed>
 */
function notificationCast(): array
{
    $vendorA = Vendor::factory()->create();
    $vendorB = Vendor::factory()->create();

    return [
        'admin' => User::factory()->admin()->create(),
        'customerA' => User::factory()->create(),
        'customerB' => User::factory()->create(),
        'vendorA' => $vendorA->user,
        'vendorB' => $vendorB->user,
        'syndicate' => User::factory()->syndicate()->create(),
        'employee' => User::factory()->employee()->create(),
        '_vendorAModel' => $vendorA,
    ];
}

/**
 * @param  array<string, mixed>  $cast
 * @return list<string> cast keys that can see the notification through the API
 */
function notificationViewers(array $cast, int $notificationId): array
{
    $viewers = [];
    foreach ($cast as $key => $user) {
        if (! $user instanceof User) {
            continue;
        }
        Sanctum::actingAs($user);
        $ids = collect(test()->getJson('/api/notifications?per_page=20')->assertOk()->json('data'))->pluck('id')->all();
        if (in_array($notificationId, $ids, true)) {
            $viewers[] = $key;
        }
    }

    return $viewers;
}

it('delivers marketing (new product / discount) notices to every user who has not opted out', function () {
    Event::fake([AdminNotificationSent::class]);
    $cast = notificationCast();
    $product = Product::factory()->create(['vendor_id' => $cast['_vendorAModel']->id]);

    app(NotificationService::class)->notifyNewProductApproved($product);
    app(NotificationService::class)->notifyProductDiscountAdded($product);

    expect(AdminNotification::count())->toBe(2);
    foreach (AdminNotification::all() as $notification) {
        expect(notificationViewers($cast, $notification->id))->toEqualCanonicalizing(['admin', 'customerA', 'customerB', 'vendorA', 'vendorB', 'syndicate', 'employee']);
    }

    // Guests never see it.
    auth()->guard('sanctum')->forgetUser();
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/notifications')->assertUnauthorized();
});

it('never broadcasts on a public channel and only targets recipient private channels', function () {
    Event::fake([AdminNotificationSent::class]);
    $cast = notificationCast();
    app(NotificationService::class)->notifyNewProductApproved(Product::factory()->create());

    Event::assertDispatched(AdminNotificationSent::class, function (AdminNotificationSent $event) use ($cast): bool {
        $channels = $event->broadcastOn();
        foreach ($channels as $channel) {
            expect($channel)->toBeInstanceOf(PrivateChannel::class);
        }
        $names = array_map(fn ($c) => $c->name, $channels);

        return $names !== []
            && in_array('private-App.Models.User.'.$cast['syndicate']->id, $names, true)
            && in_array('private-App.Models.User.'.$cast['customerA']->id, $names, true);
    });
});

it('marketing opt-out removes any user (not only customers) from the audience', function () {
    Event::fake([AdminNotificationSent::class]);
    $cast = notificationCast();
    foreach (['customerB', 'syndicate', 'vendorB'] as $optedOut) {
        NotificationPreference::factory()->create(['user_id' => $cast[$optedOut]->id, 'category' => NotificationPreference::CATEGORY_MARKETING, 'enabled' => false]);
    }
    app(NotificationService::class)->notifyNewProductApproved(Product::factory()->create());

    expect(notificationViewers($cast, AdminNotification::firstOrFail()->id))->toEqualCanonicalizing(['admin', 'customerA', 'vendorA', 'employee']);
});

it('order notices reach only admin, the order owner and the order vendor', function () {
    Event::fake([AdminNotificationSent::class]);
    $cast = notificationCast();
    $order = Order::factory()->create(['user_id' => $cast['customerA']->id, 'vendor_id' => $cast['_vendorAModel']->id]);

    app(NotificationService::class)->notifyOrderStatusUpdated($order, Order::STATUS_CONFIRMED);

    $viewers = [];
    foreach (AdminNotification::all() as $notification) {
        $viewers = [...$viewers, ...notificationViewers($cast, $notification->id)];
    }
    expect(array_values(array_unique($viewers)))->toEqualCanonicalizing(['admin', 'customerA', 'vendorA']);
});

it('admin can target a single audience member and nobody else sees it', function () {
    Event::fake([AdminNotificationSent::class]);
    $cast = notificationCast();
    foreach (['syndicate', 'vendorA', 'employee', 'customerA'] as $target) {
        Sanctum::actingAs($cast['admin']);
        $id = $this->postJson('/api/admin/notifications/send', [
            'title' => "For {$target}", 'body' => 'Private', 'type' => 'private', 'user_ids' => [$cast[$target]->id],
        ])->assertCreated()->json('data.id');

        expect(notificationViewers($cast, $id))->toBe([$target]);
    }
});

it('non-admin roles cannot send notifications', function () {
    $cast = notificationCast();
    foreach (['customerA', 'vendorA', 'syndicate', 'employee'] as $key) {
        Sanctum::actingAs($cast[$key]);
        $this->postJson('/api/admin/notifications/send', ['title' => 'x', 'body' => 'y', 'type' => 'private', 'user_ids' => [$cast['customerB']->id]])->assertForbidden();
    }
    expect(AdminNotification::count())->toBe(0);
});

it('read state is isolated per user for mark-one and mark-all', function () {
    Event::fake([AdminNotificationSent::class]);
    $cast = notificationCast();
    Sanctum::actingAs($cast['admin']);
    $both = [$cast['customerA']->id, $cast['customerB']->id];
    $shared = $this->postJson('/api/admin/notifications/send', ['title' => 'Shared', 'body' => 'b', 'type' => 'private', 'user_ids' => $both])->json('data.id');
    $second = $this->postJson('/api/admin/notifications/send', ['title' => 'Second', 'body' => 'b', 'type' => 'private', 'user_ids' => $both])->json('data.id');
    $onlyB = $this->postJson('/api/admin/notifications/send', ['title' => 'OnlyB', 'body' => 'b', 'type' => 'private', 'user_ids' => [$cast['customerB']->id]])->json('data.id');

    Sanctum::actingAs($cast['customerA']);
    $this->patchJson("/api/notifications/{$shared}/read")->assertOk();
    $this->patchJson("/api/notifications/{$onlyB}/read")->assertNotFound();
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(1);

    Sanctum::actingAs($cast['customerB']);
    $b = $this->getJson('/api/notifications');
    expect($b->json('unread_count'))->toBe(3);
    expect(collect($b->json('data'))->firstWhere('id', $shared)['read_at'])->toBeNull();

    Sanctum::actingAs($cast['customerA']);
    $this->postJson('/api/notifications/mark-all-read')->assertOk();
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(0);

    Sanctum::actingAs($cast['customerB']);
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(3);

    Sanctum::actingAs($cast['syndicate']);
    $this->patchJson("/api/notifications/{$second}/read")->assertNotFound();
    $this->postJson('/api/notifications/mark-all-read')->assertOk();
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(0);

    Sanctum::actingAs($cast['customerB']);
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(3);
});

it('guests cannot list notifications', function () {
    $this->getJson('/api/notifications')->assertUnauthorized();
});
