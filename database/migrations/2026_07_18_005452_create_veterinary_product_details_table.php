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
        Schema::create('veterinary_product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_product_detail_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('active_ingredients')->nullable();
            $table->string('concentration')->nullable();
            $table->string('dosage_form')->nullable();
            $table->json('routes_of_administration')->nullable();
            $table->json('target_species')->nullable();
            $table->json('indications')->nullable();
            $table->json('dosage_instructions')->nullable();
            $table->string('treatment_duration')->nullable();
            $table->json('contraindications')->nullable();
            $table->json('warnings')->nullable();
            $table->text('special_precautions')->nullable();
            $table->json('adverse_reactions')->nullable();
            $table->json('drug_interactions')->nullable();
            $table->text('pregnancy_lactation_use')->nullable();
            $table->unsignedInteger('withdrawal_meat_days')->nullable();
            $table->unsignedInteger('withdrawal_milk_days')->nullable();
            $table->unsignedInteger('withdrawal_eggs_days')->nullable();
            $table->json('storage_conditions')->nullable();
            $table->string('shelf_life_after_opening')->nullable();
            $table->text('overdose_information')->nullable();
            $table->text('disposal_instructions')->nullable();
            $table->text('medical_disclaimer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veterinary_product_details');
    }
};
