<?php

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogService;
use Laravel\Sanctum\Sanctum;

/**
 * Admin audit log (spec §35).
 *
 * The load-bearing properties: sensitive actions actually get logged with
 * a real actor and before/after values, and secrets are never written even
 * if a caller passes them in.
 */
it('redacts sensitive keys before ever writing them', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);

    $log = app(AuditLogService::class)->record(
        $admin,
        'user.updated',
        'User',
        99,
        ['password' => 'old-secret'],
        ['password' => 'new-secret', 'token' => 'abc123', 'name' => 'Still Visible'],
    );

    expect($log->old_values['password'])->toBe('[redacted]')
        ->and($log->new_values['password'])->toBe('[redacted]')
        ->and($log->new_values['token'])->toBe('[redacted]')
        ->and($log->new_values['name'])->toBe('Still Visible');
});

it('logs vendor approval and suspension with the acting admin', function () {
    $vendor = Vendor::factory()->create(['status' => Vendor::STATUS_PENDING, 'is_active' => false]);
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/vendors/{$vendor->id}/approve")->assertOk();

    $log = AuditLog::query()->where('action', 'vendor.approved')->where('entity_id', $vendor->id)->firstOrFail();
    expect($log->actor_user_id)->toBe($admin->id)
        ->and($log->actor_type)->toBe('admin')
        ->and($log->new_values['status'])->toBe(Vendor::STATUS_ACTIVE);

    $this->patchJson("/api/admin/vendors/{$vendor->id}/toggle-active")->assertOk();
    expect(AuditLog::query()->where('action', 'vendor.suspended')->where('entity_id', $vendor->id)->exists())->toBeTrue();
});

it('logs product approval with before and after status', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $product = Product::factory()->for($vendor)->create(['status' => Product::STATUS_PENDING]);
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/products/{$product->id}/status", ['status' => Product::STATUS_APPROVED])->assertOk();

    $log = AuditLog::query()->where('action', 'product.approved')->where('entity_id', $product->id)->firstOrFail();
    expect($log->old_values['status'])->toBe(Product::STATUS_PENDING)
        ->and($log->new_values['status'])->toBe(Product::STATUS_APPROVED);
});

it('logs a user role change but not an unrelated profile update', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $target = User::factory()->create(['type' => User::TYPE_USER]);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/users/{$target->id}", ['name' => 'Renamed Only'])->assertOk();
    expect(AuditLog::query()->where('action', 'user.role_changed')->where('entity_id', $target->id)->exists())->toBeFalse();

    $this->patchJson("/api/admin/users/{$target->id}", ['type' => User::TYPE_EMPLOYEE])->assertOk();
    $log = AuditLog::query()->where('action', 'user.role_changed')->where('entity_id', $target->id)->firstOrFail();
    expect($log->old_values['type'])->toBe(User::TYPE_USER)
        ->and($log->new_values['type'])->toBe(User::TYPE_EMPLOYEE);
});

it('logs a coupon creation, update, and deletion', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $couponId = $this->postJson('/api/admin/coupons', [
        'code' => 'AUDIT10',
        'title' => 'Audit coupon',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => true,
    ])->assertCreated()->json('data.id');

    expect(AuditLog::query()->where('action', 'coupon.created')->where('entity_id', $couponId)->exists())->toBeTrue();

    $this->patchJson("/api/admin/coupons/{$couponId}", ['discount_value' => 15])->assertOk();
    $updateLog = AuditLog::query()->where('action', 'coupon.updated')->where('entity_id', $couponId)->firstOrFail();
    expect((float) $updateLog->old_values['discount_value'])->toBe(10.0)
        ->and((float) $updateLog->new_values['discount_value'])->toBe(15.0);

    $this->deleteJson("/api/admin/coupons/{$couponId}")->assertOk();
    expect(AuditLog::query()->where('action', 'coupon.deleted')->where('entity_id', $couponId)->exists())->toBeTrue();
});

it('logs a vendor ledger adjustment and settlement', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->postJson("/api/admin/vendors/{$vendor->id}/ledger/adjustments", [
        'amount' => 50,
        'direction' => 'credit',
        'description' => 'Goodwill credit',
    ])->assertCreated();

    expect(AuditLog::query()->where('action', 'vendor_ledger.adjustment')->exists())->toBeTrue();

    $this->postJson("/api/admin/vendors/{$vendor->id}/settlements", [
        'amount' => 20,
        'method' => 'bank_transfer',
    ])->assertCreated();

    expect(AuditLog::query()->where('action', 'vendor_ledger.settlement')->exists())->toBeTrue();
});

it('lets an admin filter the audit log by entity type and action', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $vendor = Vendor::factory()->create(['status' => Vendor::STATUS_PENDING, 'is_active' => false]);
    $this->patchJson("/api/admin/vendors/{$vendor->id}/approve")->assertOk();

    $response = $this->getJson('/api/admin/audit-logs?entity_type=Vendor&action=vendor.approved')->assertOk();

    expect($response->json('data'))->not->toBeEmpty();
    foreach ($response->json('data') as $entry) {
        expect($entry['entity_type'])->toBe('Vendor')
            ->and($entry['action'])->toBe('vendor.approved');
    }
});

it('stamps a request id on every audit row written during a request', function () {
    $vendor = Vendor::factory()->create(['status' => Vendor::STATUS_PENDING, 'is_active' => false]);
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/vendors/{$vendor->id}/approve")->assertOk();

    $log = AuditLog::query()->where('action', 'vendor.approved')->where('entity_id', $vendor->id)->firstOrFail();
    expect($log->request_id)->not->toBeNull();
});
