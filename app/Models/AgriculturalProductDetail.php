<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @phpstan-type AgriculturalIngredient array{
 *     name?: string,
 *     name_ar?: string,
 *     name_en?: string,
 *     percentage?: float|int|string,
 *     value?: float|int|string,
 *     unit?: string
 * }
 * @phpstan-type AgriculturalCrop array{
 *     crop_id?: int|string,
 *     name_ar?: string,
 *     name_en?: string,
 *     scientific_name?: string
 * }
 * @phpstan-type ApprovedUse array{
 *     crop_id?: int|string,
 *     crop_name_ar?: string,
 *     crop_name_en?: string,
 *     target?: string,
 *     pest_id?: int|string,
 *     pest_name_ar?: string,
 *     pest_name_en?: string,
 *     rate?: string|float|int,
 *     method?: string,
 *     notes?: string
 * }
 * @phpstan-type ApplicationRate array{
 *     value?: float|int|string,
 *     unit?: string,
 *     crop_id?: int|string,
 *     crop_name_ar?: string,
 *     crop_name_en?: string,
 *     area?: string,
 *     notes?: string
 * }
 * @phpstan-type AgriculturalPest array{
 *     pest_id?: int|string,
 *     name_ar?: string,
 *     name_en?: string,
 *     scientific_name?: string,
 *     crop_id?: int|string
 * }
 * @phpstan-type PreHarvestInterval array{
 *     crop_id?: int|string,
 *     crop_name_ar?: string,
 *     crop_name_en?: string,
 *     days?: int|float|string
 * }
 * @phpstan-type Micronutrient array{
 *     name?: string,
 *     name_ar?: string,
 *     name_en?: string,
 *     percentage?: float|int|string,
 *     value?: float|int|string,
 *     unit?: string
 * }
 * @phpstan-type GrowthStage array{
 *     code?: string,
 *     name_ar?: string,
 *     name_en?: string,
 *     notes?: string
 * }
 * @phpstan-type SeedResistance array{
 *     disease?: string,
 *     disease_name_ar?: string,
 *     disease_name_en?: string,
 *     resistance_level?: string,
 *     notes?: string
 * }
 * @phpstan-type PlantingWindow array{
 *     region?: string,
 *     season?: string,
 *     start_date?: string,
 *     end_date?: string,
 *     notes?: string
 * }
 * @phpstan-type ValueWithUnit array{
 *     value?: float|int|string,
 *     unit?: string,
 *     area?: string,
 *     notes?: string
 * }
 *
 * @property int $shared_product_detail_id
 * @property list<AgriculturalIngredient>|null $active_ingredients
 * @property list<AgriculturalCrop>|null $target_crops
 * @property list<ApprovedUse>|null $approved_uses
 * @property list<string>|null $application_methods
 * @property list<ApplicationRate>|null $application_rates
 * @property string|int|null $max_applications
 * @property string|int|null $application_interval_days
 * @property string|array<string, mixed>|null $storage_conditions
 * @property string|list<string>|null $warnings
 * @property list<string>|null $ppe_requirements
 * @property string|array<string, mixed>|null $first_aid
 * @property string|null $container_disposal
 * @property string|list<string>|null $compatibility
 * @property list<AgriculturalPest>|null $target_pests
 * @property list<PreHarvestInterval>|null $pre_harvest_intervals
 * @property string|array<string, mixed>|null $environmental_hazards
 * @property list<Micronutrient>|null $micronutrients
 * @property list<GrowthStage>|null $growth_stages
 * @property list<string>|null $fertilization_methods
 * @property list<SeedResistance>|null $disease_resistance
 * @property list<PlantingWindow>|null $planting_windows
 * @property ValueWithUnit|array<string, mixed>|null $seeding_rate
 * @property ValueWithUnit|array<string, mixed>|null $planting_depth
 * @property array<string, mixed>|null $plant_spacing
 * @property array<string, mixed>|null $expected_yield
 */
class AgriculturalProductDetail extends Model
{
    use HasFactory;

    protected $table = 'agricultural_product_details';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shared_product_detail_id',
        'agricultural_product_type',
        'active_ingredients',
        'formulation',
        'target_crops',
        'approved_uses',
        'application_methods',
        'application_rates',
        'max_applications',
        'application_interval_days',
        'storage_conditions',
        'warnings',
        'ppe_requirements',
        'first_aid',
        'container_disposal',
        'compatibility',
        'pesticide_type',
        'chemical_group',
        'mode_of_action',
        'target_pests',
        'pre_harvest_intervals',
        're_entry_interval_hours',
        'toxicity_class',
        'environmental_hazards',
        'resistance_management',
        'fertilizer_type',
        'nutrient_n_percent',
        'nutrient_p_percent',
        'nutrient_k_percent',
        'micronutrients',
        'organic_matter_percent',
        'ph_value',
        'solubility',
        'growth_stages',
        'fertilization_methods',
        'crop_id',
        'variety_name',
        'variety_type',
        'germination_percent',
        'purity_percent',
        'seed_treatment',
        'disease_resistance',
        'planting_windows',
        'seeding_rate',
        'planting_depth',
        'plant_spacing',
        'maturity_days',
        'expected_yield',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shared_product_detail_id' => 'integer',
            'active_ingredients' => 'array',
            'target_crops' => 'array',
            'approved_uses' => 'array',
            'application_methods' => 'array',
            'application_rates' => 'array',
            'warnings' => 'array',
            'ppe_requirements' => 'array',
            'first_aid' => 'array',
            'compatibility' => 'array',
            'target_pests' => 'array',
            'pre_harvest_intervals' => 'array',
            'environmental_hazards' => 'array',
            'micronutrients' => 'array',
            'growth_stages' => 'array',
            'fertilization_methods' => 'array',
            'crop_id' => 'integer',
            'germination_percent' => 'decimal:2',
            'purity_percent' => 'decimal:2',
            'planting_windows' => 'array',
            'seeding_rate' => 'array',
            'planting_depth' => 'array',
            'plant_spacing' => 'array',
            'expected_yield' => 'array',
            'nutrient_n_percent' => 'decimal:2',
            'nutrient_p_percent' => 'decimal:2',
            'nutrient_k_percent' => 'decimal:2',
            'organic_matter_percent' => 'decimal:2',
            'ph_value' => 'decimal:2',
            're_entry_interval_hours' => 'integer',
        ];
    }

    public function sharedProductDetail(): BelongsTo
    {
        return $this->belongsTo(SharedProductDetail::class, 'shared_product_detail_id');
    }
}
