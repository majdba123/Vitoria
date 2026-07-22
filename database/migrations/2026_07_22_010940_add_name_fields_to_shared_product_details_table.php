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
        Schema::table('shared_product_details', function (Blueprint $table) {
            $table->string('manufacturer_name_ar')->nullable()->after('sku');
            $table->string('manufacturer_name_en')->nullable()->after('manufacturer_name_ar');
            $table->string('brand_name_ar')->nullable()->after('manufacturer_name_en');
            $table->string('brand_name_en')->nullable()->after('brand_name_ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shared_product_details', function (Blueprint $table) {
            $table->dropColumn([
                'manufacturer_name_ar',
                'manufacturer_name_en',
                'brand_name_ar',
                'brand_name_en',
            ]);
        });
    }
};
