<?php

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Refund;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Models\VendorSettlement;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/**
 * Archiving policy (docs/architecture/DATA_RETENTION_AND_ARCHIVING.md): orders,
 * invoices, refunds, returns, vendor ledger entries, vendor settlements, and
 * audit logs are financial/legal records that must never be hard-deleted.
 * There is deliberately no destroy route for any of them — this proves that
 * stays true rather than trusting a comment.
 */
it('exposes no delete route for any financial or audit record', function () {
    $protectedRouteNames = [
        'api.admin.orders.destroy',
        'api.admin.invoices.destroy',
        'api.admin.refunds.destroy',
        'api.admin.returns.destroy',
        'api.admin.vendors.ledger.destroy',
        'api.admin.vendors.settlements.destroy',
        'api.admin.audit-logs.destroy',
    ];

    foreach ($protectedRouteNames as $name) {
        expect(Route::has($name))->toBeFalse("Route [$name] must not exist: financial/audit records are immutable.");
    }
});

it('rejects a direct DELETE attempt against every protected financial endpoint', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $vendor = Vendor::factory()->create();
    $order = Order::factory()->for($vendor)->create();
    $invoice = Invoice::factory()->for($order)->create();
    $refund = Refund::factory()->for($order)->create();
    $return = OrderReturn::factory()->for($order)->create();
    $ledgerEntry = VendorLedgerEntry::factory()->create(['vendor_id' => $vendor->id]);
    $settlement = VendorSettlement::factory()->create(['vendor_id' => $vendor->id]);

    Sanctum::actingAs($admin);

    // Laravel returns 405 when the URI matches a registered route pattern
    // under a different HTTP method, and 404 when nothing matches at all —
    // either way, no destroy handler ever runs and nothing is deleted.
    $notDeletable = fn ($response) => expect($response->status())->toBeIn([404, 405]);

    $notDeletable($this->deleteJson("/api/admin/orders/{$order->id}"));
    $notDeletable($this->deleteJson("/api/admin/invoices/{$invoice->id}"));
    $notDeletable($this->deleteJson("/api/admin/refunds/{$refund->id}"));
    $notDeletable($this->deleteJson("/api/admin/returns/{$return->id}"));
    $notDeletable($this->deleteJson("/api/admin/vendors/{$vendor->id}/ledger/{$ledgerEntry->id}"));
    $notDeletable($this->deleteJson("/api/admin/vendors/{$vendor->id}/settlements/{$settlement->id}"));
    $notDeletable($this->deleteJson('/api/admin/audit-logs/1'));

    expect(Order::query()->whereKey($order->id)->exists())->toBeTrue()
        ->and(Invoice::query()->whereKey($invoice->id)->exists())->toBeTrue()
        ->and(Refund::query()->whereKey($refund->id)->exists())->toBeTrue()
        ->and(OrderReturn::query()->whereKey($return->id)->exists())->toBeTrue()
        ->and(VendorLedgerEntry::query()->whereKey($ledgerEntry->id)->exists())->toBeTrue()
        ->and(VendorSettlement::query()->whereKey($settlement->id)->exists())->toBeTrue();
});

it('never lets a vendor with attached order or ledger history be deleted', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $vendor = Vendor::factory()->create();
    Order::factory()->for($vendor)->create();

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/admin/vendors/{$vendor->id}")
        ->assertStatus(422);

    expect(Vendor::query()->whereKey($vendor->id)->exists())->toBeTrue();
});

it('never lets a customer with order history be deleted', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    $vendor = Vendor::factory()->create();
    Order::factory()->for($customer)->for($vendor)->create();

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/admin/users/{$customer->id}")
        ->assertStatus(422);

    expect(User::query()->whereKey($customer->id)->exists())->toBeTrue();
});

it('writes audit log entries that are never updated by application code', function () {
    expect(AuditLog::query()->getModel()->usesTimestamps())->toBeTrue();

    $log = AuditLog::query()->create([
        'actor_type' => 'admin',
        'action' => 'test.action',
        'entity_type' => 'Test',
        'entity_id' => 1,
    ]);

    expect($log->exists)->toBeTrue();
    expect(Route::has('api.admin.audit-logs.update'))->toBeFalse();
});
