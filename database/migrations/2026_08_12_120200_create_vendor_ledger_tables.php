<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An auditable vendor financial ledger (spec §20), replacing
     * `vendors.paid_amount` as the only financial history. `paid_amount`
     * itself is left in place — untouched, not migrated — because rewriting
     * it would be exactly the destructive migration §60 forbids; it simply
     * stops being read once VendorLedgerService is the source of truth.
     *
     * Rows are never updated or deleted once written — a correction is a new
     * `adjustment` entry, not an edit.
     */
    public function up(): void
    {
        Schema::create('vendor_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            // Null for a vendor-level adjustment or settlement that is not
            // tied to a single order.
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('direction', 10);
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'type']);
            $table->index(['vendor_id', 'created_at']);
        });

        Schema::create('vendor_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_entry_id')->constrained('vendor_ledger_entries')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('settled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at');
            $table->timestamps();

            $table->unique('ledger_entry_id');
            $table->index(['vendor_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_settlements');
        Schema::dropIfExists('vendor_ledger_entries');
    }
};
