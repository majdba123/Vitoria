<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait InteractsWithProductDetails
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function localizedNameRules(bool $required = false): array
    {
        $prefix = $required ? ['required'] : ['sometimes', 'nullable'];

        return [
            'name_ar' => [...$prefix, 'string', 'max:255'],
            'name_en' => [...$prefix, 'string', 'max:255'],
        ];
    }

    /**
     * Prevents a vendor/admin from setting a minimum order quantity above the
     * product's own stock, which would silently make the product permanently
     * unpurchasable (every cart attempt would fail either the minimum-quantity
     * check or the stock check) with no warning at write time.
     *
     * Falls back to the existing product's `quantity` when this request
     * doesn't submit one (e.g. a partial update that only touches
     * minimum_order_quantity) — `route('product')` is bound on update
     * requests only, but `quantity` is always required (and therefore always
     * present) on store requests, where there is no existing product to
     * fall back to.
     */
    protected function minimumOrderQuantityRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $quantity = $this->has('quantity')
                ? (int) $this->input('quantity')
                : (int) ($this->route('product')?->quantity ?? 0);

            if ((int) $value > $quantity) {
                $fail(__('The minimum order quantity may not exceed the product quantity.'));
            }
        };
    }

    /**
     * SEO meta fields (spec §39) — optional on every product form regardless
     * of actor (admin/vendor/employee).
     *
     * @return array<string, array<int, string>>
     */
    protected function metaRules(): array
    {
        return [
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function sharedDetailRules(bool $required = false): array
    {
        $prefix = $required ? ['nullable'] : ['sometimes', 'nullable'];

        return [
            'shared_detail.commercial_name' => [...$prefix, 'string', 'max:255'],
            'shared_detail.aliases' => [...$prefix, 'array'],
            'shared_detail.aliases.*' => ['string', 'max:255'],
            'shared_detail.barcodes' => [...$prefix, 'array'],
            'shared_detail.barcodes.*' => ['string', 'max:255'],
            'shared_detail.sku' => [...$prefix, 'string', 'max:255'],
            'shared_detail.manufacturer_name_ar' => [...$prefix, 'string', 'max:255'],
            'shared_detail.manufacturer_name_en' => [...$prefix, 'string', 'max:255'],
            'shared_detail.brand_name_ar' => [...$prefix, 'string', 'max:255'],
            'shared_detail.brand_name_en' => [...$prefix, 'string', 'max:255'],
            'shared_detail.country_of_origin' => [...$prefix, 'string', 'max:255'],
            'shared_detail.registration_number' => [...$prefix, 'string', 'max:255'],
            'shared_detail.registration_status' => [...$prefix, 'string', 'max:255'],
            'shared_detail.package_size' => [...$prefix, 'numeric', 'min:0'],
            'shared_detail.package_unit' => [...$prefix, 'string', 'max:255'],
            'shared_detail.short_description' => [...$prefix, 'string'],
            'shared_detail.approved_description' => [...$prefix, 'string'],
            'shared_detail.keywords' => [...$prefix, 'array'],
            'shared_detail.keywords.*' => ['string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function agriculturalDetailRules(bool $required = false): array
    {
        $prefix = $required ? ['nullable'] : ['sometimes', 'nullable'];

        return [
            'agricultural_detail.agricultural_product_type' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.active_ingredients' => [...$prefix, 'array'],
            'agricultural_detail.formulation' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.target_crops' => [...$prefix, 'array'],
            'agricultural_detail.approved_uses' => [...$prefix, 'array'],
            'agricultural_detail.application_methods' => [...$prefix, 'array'],
            'agricultural_detail.application_rates' => [...$prefix, 'array'],
            'agricultural_detail.max_applications' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.application_interval_days' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.storage_conditions' => [...$prefix, 'array'],
            'agricultural_detail.warnings' => [...$prefix, 'array'],
            'agricultural_detail.ppe_requirements' => [...$prefix, 'array'],
            'agricultural_detail.first_aid' => [...$prefix, 'array'],
            'agricultural_detail.container_disposal' => [...$prefix, 'string'],
            'agricultural_detail.compatibility' => [...$prefix, 'array'],
            'agricultural_detail.pesticide_type' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.chemical_group' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.mode_of_action' => [...$prefix, 'string'],
            'agricultural_detail.target_pests' => [...$prefix, 'array'],
            'agricultural_detail.pre_harvest_intervals' => [...$prefix, 'array'],
            'agricultural_detail.re_entry_interval_hours' => [...$prefix, 'integer', 'min:0'],
            'agricultural_detail.toxicity_class' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.environmental_hazards' => [...$prefix, 'array'],
            'agricultural_detail.resistance_management' => [...$prefix, 'string'],
            'agricultural_detail.fertilizer_type' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.nutrient_n_percent' => [...$prefix, 'numeric'],
            'agricultural_detail.nutrient_p_percent' => [...$prefix, 'numeric'],
            'agricultural_detail.nutrient_k_percent' => [...$prefix, 'numeric'],
            'agricultural_detail.micronutrients' => [...$prefix, 'array'],
            'agricultural_detail.organic_matter_percent' => [...$prefix, 'numeric'],
            'agricultural_detail.ph_value' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.solubility' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.growth_stages' => [...$prefix, 'array'],
            'agricultural_detail.fertilization_methods' => [...$prefix, 'array'],
            'agricultural_detail.crop_name_ar' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.crop_name_en' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.variety_name' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.variety_type' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.germination_percent' => [...$prefix, 'numeric'],
            'agricultural_detail.purity_percent' => [...$prefix, 'numeric'],
            'agricultural_detail.seed_treatment' => [...$prefix, 'array'],
            'agricultural_detail.disease_resistance' => [...$prefix, 'array'],
            'agricultural_detail.planting_windows' => [...$prefix, 'array'],
            'agricultural_detail.seeding_rate' => [...$prefix, 'array'],
            'agricultural_detail.planting_depth' => [...$prefix, 'array'],
            'agricultural_detail.plant_spacing' => [...$prefix, 'array'],
            'agricultural_detail.maturity_days' => [...$prefix, 'string', 'max:255'],
            'agricultural_detail.expected_yield' => [...$prefix, 'array'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function veterinaryDetailRules(bool $required = false): array
    {
        $prefix = $required ? ['nullable'] : ['sometimes', 'nullable'];

        return [
            'veterinary_detail.active_ingredients' => [...$prefix, 'array'],
            'veterinary_detail.concentration' => [...$prefix, 'string', 'max:255'],
            'veterinary_detail.dosage_form' => [...$prefix, 'string', 'max:255'],
            'veterinary_detail.routes_of_administration' => [...$prefix, 'array'],
            'veterinary_detail.target_species' => [...$prefix, 'array'],
            'veterinary_detail.indications' => [...$prefix, 'array'],
            'veterinary_detail.dosage_instructions' => [...$prefix, 'array'],
            'veterinary_detail.treatment_duration' => [...$prefix, 'string', 'max:255'],
            'veterinary_detail.contraindications' => [...$prefix, 'array'],
            'veterinary_detail.warnings' => [...$prefix, 'array'],
            'veterinary_detail.special_precautions' => [...$prefix, 'string'],
            'veterinary_detail.adverse_reactions' => [...$prefix, 'array'],
            'veterinary_detail.drug_interactions' => [...$prefix, 'array'],
            'veterinary_detail.pregnancy_lactation_use' => [...$prefix, 'string'],
            'veterinary_detail.withdrawal_meat_days' => [...$prefix, 'integer', 'min:0'],
            'veterinary_detail.withdrawal_milk_days' => [...$prefix, 'integer', 'min:0'],
            'veterinary_detail.withdrawal_eggs_days' => [...$prefix, 'integer', 'min:0'],
            'veterinary_detail.storage_conditions' => [...$prefix, 'array'],
            'veterinary_detail.shelf_life_after_opening' => [...$prefix, 'string', 'max:255'],
            'veterinary_detail.overdose_information' => [...$prefix, 'string'],
            'veterinary_detail.disposal_instructions' => [...$prefix, 'string'],
            'veterinary_detail.medical_disclaimer' => [...$prefix, 'string'],
        ];
    }

    protected function normalizeProductDetailPayload(): void
    {
        $payload = $this->all();

        if (! empty($payload['name']) && empty($payload['name_ar']) && empty($payload['name_en'])) {
            $payload['name_ar'] = $payload['name'];
            $payload['name_en'] = $payload['name'];
        }

        $jsonFields = [
            'shared_detail.aliases',
            'shared_detail.barcodes',
            'shared_detail.keywords',
            'agricultural_detail.active_ingredients',
            'agricultural_detail.target_crops',
            'agricultural_detail.approved_uses',
            'agricultural_detail.application_methods',
            'agricultural_detail.application_rates',
            'agricultural_detail.storage_conditions',
            'agricultural_detail.warnings',
            'agricultural_detail.ppe_requirements',
            'agricultural_detail.first_aid',
            'agricultural_detail.compatibility',
            'agricultural_detail.target_pests',
            'agricultural_detail.pre_harvest_intervals',
            'agricultural_detail.environmental_hazards',
            'agricultural_detail.micronutrients',
            'agricultural_detail.growth_stages',
            'agricultural_detail.fertilization_methods',
            'agricultural_detail.seed_treatment',
            'agricultural_detail.disease_resistance',
            'agricultural_detail.planting_windows',
            'agricultural_detail.seeding_rate',
            'agricultural_detail.planting_depth',
            'agricultural_detail.plant_spacing',
            'agricultural_detail.expected_yield',
            'veterinary_detail.active_ingredients',
            'veterinary_detail.routes_of_administration',
            'veterinary_detail.target_species',
            'veterinary_detail.indications',
            'veterinary_detail.dosage_instructions',
            'veterinary_detail.contraindications',
            'veterinary_detail.warnings',
            'veterinary_detail.adverse_reactions',
            'veterinary_detail.drug_interactions',
            'veterinary_detail.storage_conditions',
        ];

        foreach ($jsonFields as $field) {
            $value = data_get($payload, $field);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                data_set($payload, $field, $decoded);
            }
        }

        $this->replace($payload);
    }

    protected function productPhotoExistsRule(string $column = 'id'): \Illuminate\Validation\Rules\Exists
    {
        $productId = (int) ($this->route('product')?->id ?? 0);

        return Rule::exists('product_photos', $column)->where(function ($query) use ($productId) {
            return $query->where('product_id', $productId);
        });
    }
}
