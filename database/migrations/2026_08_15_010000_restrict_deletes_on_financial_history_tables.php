<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * These foreign keys were originally cascadeOnDelete, meaning a hard-deleted
 * User or Vendor silently took every order, payment, invoice, refund, return,
 * and ledger/settlement record tied to them along with it - directly
 * contradicting VendorLedgerService's own invariant that ledger entries are
 * "never updated or deleted." Financial/audit history must survive the
 * deletion of the account that generated it; restrictOnDelete forces that
 * deletion to be handled deliberately (e.g. deactivate the account) instead
 * of silently erasing the trail.
 */
return new class extends Migration
{
    protected array $restrictions = [
        'orders' => ['user_id', 'vendor_id'],
        'payments' => ['order_id', 'user_id'],
        'invoices' => ['order_id', 'vendor_id', 'user_id'],
        'vendor_ledger_entries' => ['vendor_id'],
        'vendor_settlements' => ['vendor_id', 'ledger_entry_id'],
        'order_returns' => ['order_id', 'user_id', 'vendor_id'],
        'refunds' => ['order_id'],
    ];

    public function up(): void
    {
        foreach ($this->restrictions as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->dropForeign([$column]);
                }
                foreach ($columns as $column) {
                    $blueprint->foreign($column)
                        ->references('id')
                        ->on($this->referencedTable($column))
                        ->restrictOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->restrictions as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->dropForeign([$column]);
                }
                foreach ($columns as $column) {
                    $blueprint->foreign($column)
                        ->references('id')
                        ->on($this->referencedTable($column))
                        ->cascadeOnDelete();
                }
            });
        }
    }

    protected function referencedTable(string $column): string
    {
        return match ($column) {
            'user_id' => 'users',
            'vendor_id' => 'vendors',
            'order_id' => 'orders',
            'ledger_entry_id' => 'vendor_ledger_entries',
            default => throw new \InvalidArgumentException("Unknown referenced table for column [{$column}]."),
        };
    }
};
