<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full-stack audit finding: unlike `payments` (which enforces `unique('order_id')`
 * at the DB level as its documented concurrency backstop), `invoices` had no
 * equivalent constraint — only a single application call site
 * (InvoiceService::createForOrder(), invoked once per order inside the
 * checkout transaction) prevented a duplicate invoice for the same order.
 * This is a defensive, zero-behavior-change addition: no code path currently
 * creates two invoices for one order, so this constraint should never fire
 * in normal operation — it exists to make that invariant a DB-enforced fact
 * rather than an assumption, matching the `payments` table's own pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });
    }
};
