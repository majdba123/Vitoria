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
        Schema::table('agricultural_product_details', function (Blueprint $table) {
            if (! Schema::hasColumn('agricultural_product_details', 'crop_name_ar')) {
                $table->string('crop_name_ar')->nullable()->after('fertilization_methods');
            }

            if (! Schema::hasColumn('agricultural_product_details', 'crop_name_en')) {
                $table->string('crop_name_en')->nullable()->after('crop_name_ar');
            }
        });

        if (Schema::hasColumn('agricultural_product_details', 'crop_id')) {
            Schema::table('agricultural_product_details', function (Blueprint $table) {
                $table->dropColumn('crop_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('agricultural_product_details', 'crop_id')) {
            Schema::table('agricultural_product_details', function (Blueprint $table) {
                $table->unsignedBigInteger('crop_id')->nullable()->after('fertilization_methods');
            });
        }

        Schema::table('agricultural_product_details', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('agricultural_product_details', 'crop_name_ar')) {
                $dropColumns[] = 'crop_name_ar';
            }

            if (Schema::hasColumn('agricultural_product_details', 'crop_name_en')) {
                $dropColumns[] = 'crop_name_en';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
