<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive only. No existing column is dropped or renamed, so historical
     * orders stay valid (spec §60). Existing rows are backfilled so that
     * grand_total equals what total_amount already held.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // --- Immutable delivery address snapshot (decision D5) ---
            $table->string('ship_recipient_name')->nullable()->after('payment_way');
            $table->string('ship_phone', 30)->nullable()->after('ship_recipient_name');
            $table->string('ship_alternate_phone', 30)->nullable()->after('ship_phone');
            $table->string('ship_governorate')->nullable()->after('ship_alternate_phone');
            $table->string('ship_city')->nullable()->after('ship_governorate');
            $table->string('ship_district')->nullable()->after('ship_city');
            $table->string('ship_street')->nullable()->after('ship_district');
            $table->string('ship_building')->nullable()->after('ship_street');
            $table->string('ship_floor', 50)->nullable()->after('ship_building');
            $table->string('ship_landmark')->nullable()->after('ship_floor');
            $table->string('ship_postal_code', 20)->nullable()->after('ship_landmark');
            $table->text('ship_notes')->nullable()->after('ship_postal_code');

            // --- Money breakdown (decision D7). tax_total stays 0 until a
            // business rule supplies a rate; no VAT rate is invented here. ---
            $table->decimal('shipping_total', 12, 2)->default(0)->after('coupon_discount_amount');
            $table->decimal('tax_total', 12, 2)->default(0)->after('shipping_total');
            $table->decimal('grand_total', 12, 2)->default(0)->after('tax_total');
            $table->string('currency', 3)->default('SYP')->after('grand_total');

            // --- Cancellation metadata (spec §10) ---
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 40)->nullable();
            $table->text('cancellation_notes')->nullable();

            // --- Idempotency guard for stock restoration (decision D1 / audit R1).
            // Claimed with a conditional UPDATE ... WHERE stock_restored_at IS NULL,
            // which is what actually makes restoration exactly-once. ---
            $table->timestamp('stock_restored_at')->nullable();
        });

        // Backfill: grand_total must equal the historical total for existing orders.
        DB::table('orders')->update([
            'grand_total' => DB::raw('total_amount'),
        ]);

        // Orders already cancelled before this migration had their stock restored by
        // the old code path. Mark them restored so a future cancel/restore call
        // cannot double-count them.
        DB::table('orders')
            ->where('status', 'cancelled')
            ->update(['stock_restored_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by_user_id']);
            $table->dropColumn([
                'ship_recipient_name', 'ship_phone', 'ship_alternate_phone',
                'ship_governorate', 'ship_city', 'ship_district', 'ship_street',
                'ship_building', 'ship_floor', 'ship_landmark', 'ship_postal_code',
                'ship_notes',
                'shipping_total', 'tax_total', 'grand_total', 'currency',
                'cancelled_at', 'cancelled_by_user_id', 'cancellation_reason',
                'cancellation_notes', 'stock_restored_at',
            ]);
        });
    }
};
