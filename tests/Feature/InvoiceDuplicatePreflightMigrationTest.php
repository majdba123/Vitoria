<?php

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Full-stack audit / financial-closure round: migration safety item.
 *
 * `2026_08_20_100000_add_unique_order_id_to_invoices_table` adds
 * unique('order_id') to invoices. Applying that directly against a database
 * that already has duplicate invoices for the same order_id (representative
 * of legacy/pre-existing production data) would previously abort mid-way
 * with a bare DB integrity-constraint violation. This proves the migration's
 * preflight check instead fails fast with an actionable error identifying
 * the affected order_id, and modifies no schema when duplicates exist.
 */
test('the invoices unique(order_id) migration refuses to run against pre-existing duplicate order_id rows', function () {
    // The migration already ran as part of RefreshDatabase; drop its
    // constraint to simulate the "not yet applied" state the real deploy
    // will be in when this migration is first run against legacy data.
    Schema::table('invoices', fn ($table) => $table->dropUnique(['order_id']));

    $order = Order::factory()->create();
    Invoice::factory()->for($order)->create();
    Invoice::factory()->for($order)->create(); // legacy duplicate for the same order_id

    expect(Invoice::query()->where('order_id', $order->id)->count())->toBe(2);

    $migration = require database_path('migrations/2026_08_20_100000_add_unique_order_id_to_invoices_table.php');

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, (string) $order->id);

    // No schema change was applied — a second duplicate invoice for a
    // different order_id can still be inserted without violating anything,
    // proving the unique constraint was never added.
    $otherOrder = Order::factory()->create();
    Invoice::factory()->for($otherOrder)->create();
    Invoice::factory()->for($otherOrder)->create();
    expect(Invoice::query()->where('order_id', $otherOrder->id)->count())->toBe(2);
});

test('the invoices unique(order_id) migration applies cleanly when there are no duplicates', function () {
    Schema::table('invoices', fn ($table) => $table->dropUnique(['order_id']));

    $order = Order::factory()->create();
    Invoice::factory()->for($order)->create();

    $migration = require database_path('migrations/2026_08_20_100000_add_unique_order_id_to_invoices_table.php');
    $migration->up();

    $otherOrder = Order::factory()->create();
    Invoice::factory()->for($otherOrder)->create();

    expect(fn () => Invoice::factory()->for($otherOrder)->create())->toThrow(\Illuminate\Database\QueryException::class);
});
