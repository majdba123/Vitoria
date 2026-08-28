<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id_snapshot')->nullable()->after('product_id');
            $table->string('category_type', 32)->nullable()->after('category_id_snapshot');
            $table->decimal('commission_rate_snapshot', 8, 2)->nullable()->after('category_type');
            $table->index(['category_type', 'order_id'], 'order_items_domain_order_index');
        });

        // Backfill production-like legacy rows before analytics switches to
        // immutable snapshots. Nullable columns keep deleted legacy products
        // deployable; those irrecoverable rows continue through fallbacks.
        DB::statement(<<<'SQL'
            UPDATE order_items
            SET category_id_snapshot = (
                    SELECT products.category_id
                    FROM products
                    WHERE products.id = order_items.product_id
                ),
                category_type = (
                    SELECT categories.type
                    FROM products
                    JOIN categories ON categories.id = products.category_id
                    WHERE products.id = order_items.product_id
                ),
                commission_rate_snapshot = (
                    SELECT categories.commission
                    FROM products
                    JOIN categories ON categories.id = products.category_id
                    WHERE products.id = order_items.product_id
                )
            WHERE product_id IS NOT NULL
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_domain_order_index');
            $table->dropColumn([
                'category_id_snapshot',
                'category_type',
                'commission_rate_snapshot',
            ]);
        });
    }
};
