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
        Schema::create('shared_product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('commercial_name')->nullable();
            $table->json('aliases')->nullable();
            $table->json('barcodes')->nullable();
            $table->string('sku')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('registration_status')->nullable();
            $table->decimal('package_size', 12, 2)->nullable();
            $table->string('package_unit')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('approved_description')->nullable();
            $table->json('keywords')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_product_details');
    }
};
