<?php

use App\Models\AdminNotification;
use App\Models\NotificationPreference;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Services\NotificationPreferenceService;
use App\Services\NotificationService;
use Laravel\Sanctum\Sanctum;

/**
 * Notification preferences (spec §33).
 *
 * The load-bearing properties: critical categories can never be disabled —
 * not through the API, and not even if a stale row somehow says otherwise —
 * and a disabled category actually stops a notification from reaching the
 * user, not just from being labeled correctly.
 */
it('lists default preferences with the critical categories locked', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/notification-preferences')->assertOk();
    $data = collect($response->json('data'))->keyBy('category');

    expect($data[NotificationPreference::CATEGORY_ORDER_UPDATES]['enabled'])->toBeTrue()
        ->and($data[NotificationPreference::CATEGORY_ORDER_UPDATES]['editable'])->toBeFalse()
        ->and($data[NotificationPreference::CATEGORY_ACCOUNT_SECURITY]['editable'])->toBeFalse()
        ->and($data[NotificationPreference::CATEGORY_MARKETING]['editable'])->toBeTrue()
        ->and($data[NotificationPreference::CATEGORY_VENDOR_COMPLIANCE]['editable'])->toBeTrue();
});

it('lets a user disable a mutable category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/notification-preferences', [
        'category' => NotificationPreference::CATEGORY_MARKETING,
        'enabled' => false,
    ])->assertOk();

    $response = $this->getJson('/api/notification-preferences')->assertOk();
    $data = collect($response->json('data'))->keyBy('category');
    expect($data[NotificationPreference::CATEGORY_MARKETING]['enabled'])->toBeFalse();
});

it('rejects disabling a critical category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/notification-preferences', [
        'category' => NotificationPreference::CATEGORY_ORDER_UPDATES,
        'enabled' => false,
    ])->assertStatus(422);

    expect(NotificationPreference::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('rejects an invalid category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/notification-preferences', ['category' => 'not_real', 'enabled' => false])
        ->assertStatus(422);
});

it('never disables a critical category even from a directly-written row', function () {
    $user = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationPreference::CATEGORY_ORDER_UPDATES,
        'enabled' => false,
    ]);

    expect(app(NotificationPreferenceService::class)->isEnabled($user->id, NotificationPreference::CATEGORY_ORDER_UPDATES))->toBeTrue();
});

it('hides a marketing notification from a user who disabled it, and only reaches recipients who did not', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $product = Product::factory()->for($vendor)->create();

    $optedOut = User::factory()->create();
    $optedIn = User::factory()->create();

    Sanctum::actingAs($optedOut);
    $this->patchJson('/api/notification-preferences', [
        'category' => NotificationPreference::CATEGORY_MARKETING,
        'enabled' => false,
    ])->assertOk();

    app(NotificationService::class)->notifyProductDiscountAdded($product);

    // Visibility is recipient-scoped: notifyProductDiscountAdded() creates a
    // broadcast notification with no recipient rows, so it explicitly syncs
    // both users as recipients here to exercise the opt-out filter on top
    // of that recipient scoping — see NotificationController::index().
    $notification = AdminNotification::query()->latest('id')->firstOrFail();
    $notification->recipients()->sync([$optedOut->id, $optedIn->id]);

    Sanctum::actingAs($optedOut);
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(0);

    Sanctum::actingAs($optedIn);
    expect($this->getJson('/api/notifications')->json('unread_count'))->toBe(1);
});

it('stops a vendor from receiving a document-review notification once they opt out', function () {
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $owner->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $document = VendorDocument::factory()->create(['vendor_id' => $vendor->id]);

    Sanctum::actingAs($owner);
    $this->patchJson('/api/notification-preferences', [
        'category' => NotificationPreference::CATEGORY_VENDOR_COMPLIANCE,
        'enabled' => false,
    ])->assertOk();

    $document->update(['status' => VendorDocument::STATUS_VERIFIED]);
    app(NotificationService::class)->notifyVendorDocumentReviewed($document->fresh());

    expect(
        AdminNotification::query()
            ->where('category', NotificationPreference::CATEGORY_VENDOR_COMPLIANCE)
            ->whereHas('recipients', fn ($q) => $q->where('users.id', $owner->id))
            ->exists()
    )->toBeFalse();
});

it('snapshots system notifications in each recipients preferred language', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $product = Product::factory()->for($vendor)->create([
        'name_ar' => 'منتج عربي',
        'name_en' => 'English product',
    ]);
    $arabicUser = User::factory()->create(['locale' => 'ar']);
    $englishUser = User::factory()->create(['locale' => 'en']);

    app(NotificationService::class)->notifyNewProductApproved($product);

    $arabicNotification = AdminNotification::query()
        ->whereHas('recipients', fn ($query) => $query->where('users.id', $arabicUser->id))
        ->latest('id')
        ->firstOrFail();
    $englishNotification = AdminNotification::query()
        ->whereHas('recipients', fn ($query) => $query->where('users.id', $englishUser->id))
        ->latest('id')
        ->firstOrFail();

    expect($arabicNotification->title)->toBe('منتج جديد')
        ->and($arabicNotification->body)->toContain('منتج عربي')
        ->and($englishNotification->title)->toBe('New product')
        ->and($englishNotification->body)->toContain('English product')
        ->and($arabicNotification->id)->not->toBe($englishNotification->id);
});
