<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_product_details', function (Blueprint $table): void {
            if (Schema::hasColumn('shared_product_details', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shared_product_details', function (Blueprint $table): void {
            if (! Schema::hasColumn('shared_product_details', 'barcode')) {
                $table->string('barcode')->nullable()->after('aliases');
            }
        });
    }
};
