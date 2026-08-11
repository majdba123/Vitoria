<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit R3: `coupons.used_count` is a bare counter with no record of who
     * redeemed what, so "one per customer" is not expressible and the cap is
     * not enforceable under concurrency. This table makes redemption a fact.
     */
    public function up(): void
    {
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamps();

            // Per-user limit checks read this exact slice.
            $table->index(['coupon_id', 'user_id']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('min_order_subtotal', 12, 2)->nullable()->after('discount_value');
            $table->decimal('max_discount_amount', 12, 2)->nullable()->after('min_order_subtotal');
            $table->unsignedInteger('per_user_limit')->nullable()->after('usage_limit');
            $table->boolean('first_order_only')->default(false)->after('per_user_limit');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn([
                'min_order_subtotal', 'max_discount_amount',
                'per_user_limit', 'first_order_only',
            ]);
        });

        Schema::dropIfExists('coupon_redemptions');
    }
};
