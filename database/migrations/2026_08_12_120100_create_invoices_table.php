<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An immutable accounting snapshot of an order (spec §19).
     *
     * Orders already carry every figure this needs and their money columns
     * do not change after creation, so this is not a second source of truth
     * for the amounts — it exists for a stable, sequential invoice_number
     * distinct from order_number, and an explicit issued_at moment, both of
     * which accounting treats differently from an operational order id.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('currency', 3)->default('SYP');
            $table->string('payment_method', 30);
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
