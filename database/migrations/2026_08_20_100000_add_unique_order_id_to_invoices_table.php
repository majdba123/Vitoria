<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
 *
 * PREFLIGHT: if any pre-existing production data already has two or more
 * invoices for the same order_id (e.g. from before InvoiceService's
 * single-call-site guarantee existed, or from a since-fixed bug), applying
 * `unique('order_id')` directly would abort mid-migration with a raw DB
 * integrity-constraint error — on MySQL/Postgres that can leave the
 * migration in a half-applied state. This checks for duplicates first and
 * fails fast with an actionable message identifying exactly which order_ids
 * are affected, instead of a bare SQL exception. It never deletes or merges
 * data itself — reconciling which duplicate invoice is authoritative is a
 * business decision for a human, not something this migration should guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicateOrderIds = DB::table('invoices')
            ->select('order_id')
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('order_id');

        if ($duplicateOrderIds->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot apply unique(order_id) to invoices: duplicate invoices already exist for order_id(s) '
                .$duplicateOrderIds->implode(', ').'. Reconcile these manually (decide which invoice per '
                .'order_id is authoritative, then delete or re-point the others) and re-run this migration. '
                .'No rows were modified.'
            );
        }

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
