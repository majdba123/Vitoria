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
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('is_active');
            $table->string('registration_source', 20)->default('admin')->after('status');
            $table->string('commercial_register_file')->nullable()->after('registration_source');
        });

        DB::table('vendors')
            ->where('is_active', false)
            ->update(['status' => 'inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'registration_source',
                'commercial_register_file',
            ]);
        });
    }
};
