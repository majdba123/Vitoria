<?php

use App\Models\ShippingMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lightweight shipping layer with no warehouse or inventory concept
     * (spec §14). Zones and methods are admin-configurable; rates default to
     * 0 because no real business rate exists yet — inventing one would be
     * exactly the fake shipping behaviour §63 forbids. This mirrors the
     * reasoning decision D7 already applied to tax.
     */
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            // Exactly one zone is the catch-all used when an order's
            // governorate matches no explicit zone. Enforced in
            // ShippingService, not the schema.
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_zone_governorates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            // A governorate is free text on user_addresses/orders (no
            // governorate master table exists), so this maps that string to
            // a zone rather than introducing a new geography hierarchy.
            $table->string('governorate');
            $table->timestamps();

            $table->unique('governorate');
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->decimal('rate', 12, 2)->default(0);
            // Free shipping above this subtotal; null means never free via
            // this rule.
            $table->decimal('free_over_subtotal', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shipping_zone_id', 'shipping_method_id']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // One shipment per order in the current model — a reshipment
            // after `returned` would be a new order, not implemented here.
            $table->unique('order_id');
        });

        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('previous_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 20)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'id']);
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zone_governorates');
        Schema::dropIfExists('shipping_zones');
    }

    /**
     * Production only runs `migrate --force`, never `db:seed` (see
     * .github/workflows/deploy.yml) — so the methods and catch-all zone
     * checkout depends on must be created here, not in a Seeder that would
     * never run against the live database.
     */
    private function seedDefaults(): void
    {
        $zoneId = DB::table('shipping_zones')->insertGetId([
            'name_en' => 'All governorates',
            'name_ar' => 'جميع المحافظات',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $methods = [
            [ShippingMethod::STANDARD, 'Standard delivery', 'توصيل عادي', 1],
            [ShippingMethod::EXPRESS, 'Express delivery', 'توصيل سريع', 2],
            [ShippingMethod::VENDOR_DELIVERY, 'Vendor delivery', 'توصيل من التاجر', 3],
        ];

        foreach ($methods as [$code, $nameEn, $nameAr, $sortOrder]) {
            $methodId = DB::table('shipping_methods')->insertGetId([
                'code' => $code,
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'is_active' => true,
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('shipping_rates')->insert([
                'shipping_zone_id' => $zoneId,
                'shipping_method_id' => $methodId,
                'rate' => 0,
                'free_over_subtotal' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
