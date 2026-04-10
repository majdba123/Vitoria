<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_way', 20)->default('cash')->after('status');
            $table->foreignId('coupon_id')->nullable()->after('vendor_id')->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->string('coupon_type', 20)->nullable()->after('coupon_code');
            $table->decimal('coupon_value', 10, 2)->nullable()->after('coupon_type');
            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('items_count');
            $table->decimal('coupon_discount_amount', 12, 2)->default(0)->after('subtotal_amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('original_unit_price', 12, 2)->default(0)->after('product_name');
            $table->boolean('has_discount')->default(false)->after('original_unit_price');
            $table->decimal('applied_discount_percentage', 8, 2)->nullable()->after('has_discount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('line_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'original_unit_price',
                'has_discount',
                'applied_discount_percentage',
                'discount_amount',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn([
                'payment_way',
                'coupon_id',
                'coupon_code',
                'coupon_type',
                'coupon_value',
                'subtotal_amount',
                'coupon_discount_amount',
            ]);
        });
    }
};
