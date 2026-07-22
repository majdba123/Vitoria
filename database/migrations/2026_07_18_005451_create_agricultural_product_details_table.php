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
        Schema::create('agricultural_product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_product_detail_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('agricultural_product_type')->nullable();
            $table->json('active_ingredients')->nullable();
            $table->string('formulation')->nullable();
            $table->json('target_crops')->nullable();
            $table->json('approved_uses')->nullable();
            $table->json('application_methods')->nullable();
            $table->json('application_rates')->nullable();
            $table->string('max_applications')->nullable();
            $table->string('application_interval_days')->nullable();
            $table->json('storage_conditions')->nullable();
            $table->json('warnings')->nullable();
            $table->json('ppe_requirements')->nullable();
            $table->json('first_aid')->nullable();
            $table->text('container_disposal')->nullable();
            $table->json('compatibility')->nullable();
            $table->string('pesticide_type')->nullable();
            $table->string('chemical_group')->nullable();
            $table->text('mode_of_action')->nullable();
            $table->json('target_pests')->nullable();
            $table->json('pre_harvest_intervals')->nullable();
            $table->unsignedInteger('re_entry_interval_hours')->nullable();
            $table->string('toxicity_class')->nullable();
            $table->json('environmental_hazards')->nullable();
            $table->text('resistance_management')->nullable();
            $table->string('fertilizer_type')->nullable();
            $table->decimal('nutrient_n_percent', 8, 2)->nullable();
            $table->decimal('nutrient_p_percent', 8, 2)->nullable();
            $table->decimal('nutrient_k_percent', 8, 2)->nullable();
            $table->json('micronutrients')->nullable();
            $table->decimal('organic_matter_percent', 8, 2)->nullable();
            $table->string('ph_value')->nullable();
            $table->string('solubility')->nullable();
            $table->json('growth_stages')->nullable();
            $table->json('fertilization_methods')->nullable();
            $table->string('crop_name_ar')->nullable();
            $table->string('crop_name_en')->nullable();
            $table->string('variety_name')->nullable();
            $table->string('variety_type')->nullable();
            $table->decimal('germination_percent', 8, 2)->nullable();
            $table->decimal('purity_percent', 8, 2)->nullable();
            $table->json('seed_treatment')->nullable();
            $table->json('disease_resistance')->nullable();
            $table->json('planting_windows')->nullable();
            $table->json('seeding_rate')->nullable();
            $table->json('planting_depth')->nullable();
            $table->json('plant_spacing')->nullable();
            $table->string('maturity_days')->nullable();
            $table->json('expected_yield')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agricultural_product_details');
    }
};
