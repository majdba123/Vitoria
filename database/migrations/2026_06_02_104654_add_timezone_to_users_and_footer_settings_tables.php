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
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('longitude')->index();
        });

        Schema::table('footer_settings', function (Blueprint $table) {
            $table->string('default_timezone', 64)->nullable()->after('contact_address');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['type', 'created_at'], 'categories_type_created_at_index');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->index(['business_type', 'status'], 'vendors_business_type_status_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'orders_user_status_created_at_index');
            $table->index(['vendor_id', 'status', 'created_at'], 'orders_vendor_status_created_at_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['product_id', 'order_id'], 'order_items_product_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_product_order_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_status_created_at_index');
            $table->dropIndex('orders_vendor_status_created_at_index');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('vendors_business_type_status_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_type_created_at_index');
        });

        Schema::table('footer_settings', function (Blueprint $table) {
            $table->dropColumn('default_timezone');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
