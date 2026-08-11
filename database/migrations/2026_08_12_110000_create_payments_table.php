<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment record per order (spec §11).
     *
     * COD is the only provider the platform configures, so this table exists to
     * make payment state explicit and auditable rather than to integrate a
     * gateway. No third-party provider is stubbed (decision D9).
     *
     * No card data, token, or PAN is stored here — only a provider reference
     * string that a real gateway would supply later.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('cod');
            $table->string('method', 30)->default('cash');
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('provider_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            // An order has exactly one payment in the current model. The unique
            // index is what prevents a duplicate payment row under concurrency.
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
