<?php

use App\Events\AdminNotificationSent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Services\Commerce\OrderCancellationService;
use App\Services\Commerce\OrderStatusService;
use App\Services\Commerce\ReturnService;
use App\Services\Commerce\VendorLedgerService;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\DB;

test('order completion rolls back status and payment when ledger recognition fails', function () {
    $vendor = Vendor::factory()->create();
    $customer = User::factory()->create();
    $actor = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $order = Order::factory()->for($vendor)->for($customer)->create([
        'status' => Order::STATUS_OUT_FOR_DELIVERY,
        'subtotal_amount' => 100,
        'grand_total' => 100,
        'total_amount' => 100,
    ]);
    Payment::factory()->for($order)->for($customer)->create(['amount' => 100]);

    $this->mock(VendorLedgerService::class)
        ->shouldReceive('recordSale')
        ->once()
        ->andThrow(new RuntimeException('simulated ledger failure'));

    expect(fn () => app(OrderStatusService::class)->transition(
        $order,
        Order::STATUS_COMPLETED,
        $actor,
        'admin',
    ))->toThrow(RuntimeException::class, 'simulated ledger failure');

    expect($order->refresh()->status)->toBe(Order::STATUS_OUT_FOR_DELIVERY)
        ->and($order->payment->refresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and(OrderStatusHistory::query()->where('order_id', $order->id)->where('new_status', Order::STATUS_COMPLETED)->exists())->toBeFalse()
        ->and(VendorLedgerEntry::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('cancellation rolls back its restoration claim and status when stock restoration fails', function () {
    $vendor = Vendor::factory()->create();
    $customer = User::factory()->create();
    $product = Product::factory()->for($vendor)->create(['quantity' => 5]);
    $order = Order::factory()->for($vendor)->for($customer)->create(['status' => Order::STATUS_PENDING]);
    OrderItem::factory()->for($order)->for($product)->create(['quantity' => 2]);
    Payment::factory()->for($order)->for($customer)->create();

    DB::statement("CREATE TRIGGER fail_product_restore BEFORE UPDATE OF quantity ON products BEGIN SELECT RAISE(ABORT, 'simulated stock failure'); END");

    expect(fn () => app(OrderCancellationService::class)->cancel(
        $order,
        $customer,
        'customer',
        'customer_changed_mind',
    ))->toThrow(\Illuminate\Database\QueryException::class);

    expect($order->refresh()->status)->toBe(Order::STATUS_PENDING)
        ->and($order->stock_restored_at)->toBeNull()
        ->and($order->payment->refresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($product->refresh()->quantity)->toBe(5);
});

test('return receipt rolls back its restoration claim and status when stock restoration fails', function () {
    $vendor = Vendor::factory()->create();
    $customer = User::factory()->create();
    $actor = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $product = Product::factory()->for($vendor)->create(['quantity' => 5]);
    $order = Order::factory()->for($vendor)->for($customer)->create(['status' => Order::STATUS_COMPLETED]);
    $orderItem = OrderItem::factory()->for($order)->for($product)->create(['quantity' => 2]);
    $return = OrderReturn::factory()->for($order)->for($customer)->for($vendor)->create([
        'status' => OrderReturn::STATUS_APPROVED,
    ]);
    ReturnItem::factory()->for($return, 'orderReturn')->for($orderItem)->for($product)->create(['quantity' => 1]);

    DB::statement("CREATE TRIGGER fail_return_restore BEFORE UPDATE OF quantity ON products BEGIN SELECT RAISE(ABORT, 'simulated stock failure'); END");

    expect(fn () => app(ReturnService::class)->transition(
        $return,
        OrderReturn::STATUS_RECEIVED,
        $actor,
        'admin',
    ))->toThrow(\Illuminate\Database\QueryException::class);

    expect($return->refresh()->status)->toBe(OrderReturn::STATUS_APPROVED)
        ->and($return->stock_restored_at)->toBeNull()
        ->and($product->refresh()->quantity)->toBe(5);
});

test('broadcast notifications are deferred until their surrounding transaction commits', function () {
    $event = new AdminNotificationSent(1, 'Title', 'Body', 'private', [1]);

    expect($event)->toBeInstanceOf(ShouldDispatchAfterCommit::class);
});

test('stateful API routes are not globally excluded from CSRF verification', function () {
    $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)->not->toContain("validateCsrfTokens(except: ['api/*'])");
});
