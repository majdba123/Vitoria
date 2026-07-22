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
        if (! Schema::hasTable('shared_product_details')) {
            return;
        }

        $hasManufacturerId = Schema::hasColumn('shared_product_details', 'manufacturer_id');
        $hasBrandId = Schema::hasColumn('shared_product_details', 'brand_id');

        if (! $hasManufacturerId && ! $hasBrandId) {
            return;
        }

        Schema::table('shared_product_details', function (Blueprint $table) use ($hasManufacturerId, $hasBrandId) {
            if ($hasManufacturerId) {
                $table->dropForeign(['manufacturer_id']);
                $table->dropColumn('manufacturer_id');
            }

            if ($hasBrandId) {
                $table->dropForeign(['brand_id']);
                $table->dropColumn('brand_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('shared_product_details')) {
            return;
        }

        $hasManufacturerId = Schema::hasColumn('shared_product_details', 'manufacturer_id');
        $hasBrandId = Schema::hasColumn('shared_product_details', 'brand_id');

        if ($hasManufacturerId && $hasBrandId) {
            return;
        }

        Schema::table('shared_product_details', function (Blueprint $table) use ($hasManufacturerId, $hasBrandId) {
            if (! $hasManufacturerId) {
                $table->foreignId('manufacturer_id')->nullable()->after('sku')->constrained()->nullOnDelete();
            }

            if (! $hasBrandId) {
                $table->foreignId('brand_id')->nullable()->after('manufacturer_name_en')->constrained()->nullOnDelete();
            }
        });
    }
};
