<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('products', 'icon')) {
                $columns[] = 'icon';
            }

            if (Schema::hasColumn('products', 'image')) {
                $columns[] = 'image';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'icon')) {
                $table->string('icon')->nullable()->after('description');
            }

            if (! Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->after(Schema::hasColumn('products', 'icon') ? 'icon' : 'description');
            }
        });
    }
};
