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
        Schema::table('products', function (Blueprint $table) {
            // Index for filtering by vendor_id
            $table->index('vendor_id', 'products_vendor_id_index');

            // Index for filtering by status
            $table->index('status', 'products_status_index');

            // Index for filtering by is_active
            $table->index('is_active', 'products_is_active_index');

            // Composite index for public product queries (most common query pattern)
            $table->index(['is_active', 'status', 'quantity'], 'products_public_index');

            // Index for sorting by created_at
            $table->index('created_at', 'products_created_at_index');
        });

        Schema::table('vendors', function (Blueprint $table) {
            // Index for filtering by is_active
            $table->index('is_active', 'vendors_is_active_index');

            // Index for sorting by created_at
            $table->index('created_at', 'vendors_created_at_index');
        });

        Schema::table('product_photos', function (Blueprint $table) {
            // Index for filtering by product_id
            $table->index('product_id', 'product_photos_product_id_index');

            // Index for filtering by is_primary
            $table->index('is_primary', 'product_photos_is_primary_index');

            // Composite index for ordering photos
            $table->index(['product_id', 'is_primary', 'sort_order'], 'product_photos_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_vendor_id_index');
            $table->dropIndex('products_status_index');
            $table->dropIndex('products_is_active_index');
            $table->dropIndex('products_public_index');
            $table->dropIndex('products_created_at_index');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('vendors_is_active_index');
            $table->dropIndex('vendors_created_at_index');
        });

        Schema::table('product_photos', function (Blueprint $table) {
            $table->dropIndex('product_photos_product_id_index');
            $table->dropIndex('product_photos_is_primary_index');
            $table->dropIndex('product_photos_order_index');
        });
    }
};
