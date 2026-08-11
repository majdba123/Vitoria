<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refunds (spec §13), linked to the order, optionally to the return that
     * justified them and the payment they draw against.
     *
     * The invariant this table has to support: cumulative completed refunds
     * for an order never exceed what was actually paid. That is enforced in
     * RefundService by a conditional UPDATE against payments.refunded_amount,
     * not by application-side arithmetic alone.
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_return_id')->nullable()->constrained('order_returns')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 30)->default('pending')->index();
            $table->string('reason', 40)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->string('provider_reference')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            // At most one refund per return, which is what makes "no duplicate
            // refunds" a database guarantee rather than a code convention.
            $table->unique('order_return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
