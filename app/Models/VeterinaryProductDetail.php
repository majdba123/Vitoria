<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @phpstan-type VeterinaryIngredient array{
 *     name?: string,
 *     name_ar?: string,
 *     name_en?: string,
 *     concentration?: string,
 *     value?: float|int|string,
 *     unit?: string
 * }
 * @phpstan-type TargetSpecies array{
 *     species_id?: int|string,
 *     name_ar?: string,
 *     name_en?: string,
 *     aliases?: list<string>
 * }
 * @phpstan-type Indication array{
 *     species_id?: int|string,
 *     species_name_ar?: string,
 *     species_name_en?: string,
 *     indication?: string,
 *     notes?: string
 * }
 * @phpstan-type DosageInstruction array{
 *     species_id?: int|string,
 *     species_name_ar?: string,
 *     species_name_en?: string,
 *     dose?: float|int|string,
 *     unit?: string,
 *     frequency?: string,
 *     duration?: string,
 *     route?: string,
 *     notes?: string
 * }
 *
 * @property int $shared_product_detail_id
 * @property list<VeterinaryIngredient>|null $active_ingredients
 * @property list<string>|null $routes_of_administration
 * @property list<TargetSpecies>|null $target_species
 * @property list<Indication>|null $indications
 * @property list<DosageInstruction>|null $dosage_instructions
 * @property string|list<string>|null $contraindications
 * @property string|list<string>|null $warnings
 * @property string|list<string>|null $adverse_reactions
 * @property string|list<string>|null $drug_interactions
 */
class VeterinaryProductDetail extends Model
{
    use HasFactory;

    protected $table = 'veterinary_product_details';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shared_product_detail_id',
        'active_ingredients',
        'concentration',
        'dosage_form',
        'routes_of_administration',
        'target_species',
        'indications',
        'dosage_instructions',
        'treatment_duration',
        'contraindications',
        'warnings',
        'special_precautions',
        'adverse_reactions',
        'drug_interactions',
        'pregnancy_lactation_use',
        'withdrawal_meat_days',
        'withdrawal_milk_days',
        'withdrawal_eggs_days',
        'storage_conditions',
        'shelf_life_after_opening',
        'overdose_information',
        'disposal_instructions',
        'medical_disclaimer',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shared_product_detail_id' => 'integer',
            'active_ingredients' => 'array',
            'routes_of_administration' => 'array',
            'target_species' => 'array',
            'indications' => 'array',
            'dosage_instructions' => 'array',
            'contraindications' => 'array',
            'warnings' => 'array',
            'adverse_reactions' => 'array',
            'drug_interactions' => 'array',
            'withdrawal_meat_days' => 'integer',
            'withdrawal_milk_days' => 'integer',
            'withdrawal_eggs_days' => 'integer',
        ];
    }

    public function sharedProductDetail(): BelongsTo
    {
        return $this->belongsTo(SharedProductDetail::class, 'shared_product_detail_id');
    }
}
