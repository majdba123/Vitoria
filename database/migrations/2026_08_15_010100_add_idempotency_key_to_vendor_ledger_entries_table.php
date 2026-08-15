<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VendorLedgerService::recordSale() guards against double-posting a sale with
 * a check-then-insert (exists() then create()), which has a TOCTOU race under
 * concurrent calls with no database-level backstop. A plain unique index on
 * (order_id, type) won't work here: legitimate partial refunds create
 * multiple `refund`-type entries for the same order, and adjustment/
 * settlement entries have no order_id at all. This nullable key is only ever
 * populated for the two types that must be exactly-once per order (sale,
 * commission) - every other entry leaves it null, and both MySQL and SQLite
 * allow unlimited NULLs in a unique index, so refunds/adjustments/
 * settlements are entirely unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_ledger_entries', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->unique()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_ledger_entries', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
