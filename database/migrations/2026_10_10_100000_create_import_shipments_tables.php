<?php

use App\Models\Port;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Import shipments registered at a border port (الشحنات). Kept separate from
 * the order-delivery `shipments` table, which tracks marketplace orders.
 *
 * Default ports are inserted here as well as by PortSeeder because production
 * only runs `migrate --force`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('import_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syndicate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('port_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('merchant_name');
            $table->string('merchant_register_number', 100);
            $table->string('merchant_address', 500);
            $table->string('invoice_number', 100);
            $table->unsignedInteger('stickers_count');
            $table->unsignedSmallInteger('sticker_price');
            $table->string('clearance_number', 100)->nullable();
            $table->string('release_decision', 1000)->nullable();
            $table->timestamps();

            $table->index(['syndicate_id', 'created_at']);
            $table->index(['port_id', 'created_at']);
            $table->index('invoice_number');
            $table->index('merchant_register_number');
        });

        Schema::create('import_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_shipment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('country', 100);
            $table->unsignedInteger('quantity');
            $table->decimal('total_price', 15, 2);
            $table->timestamps();

            $table->index('country');
        });

        $now = now();
        DB::table('ports')->insert(array_map(
            fn (string $name) => ['name' => $name, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            Port::DEFAULT_NAMES,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('import_shipment_items');
        Schema::dropIfExists('import_shipments');
        Schema::dropIfExists('ports');
    }
};
