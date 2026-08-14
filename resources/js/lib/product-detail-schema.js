/**
 * Field schema for the three detail buckets a product can carry
 * (shared_detail / agricultural_detail / veterinary_detail), ported field
 * for field from components/products/detail-fields.blade.php. Kept as data
 * so ProductDetailFields.jsx can render it with one generic loop instead of
 * ~80 hand-written <input> elements.
 */

export const AGRICULTURAL_TYPE_OPTIONS = [
    { value: 'pesticide', label: 'Pesticide' },
    { value: 'fertilizer', label: 'Fertilizer' },
    { value: 'seed', label: 'Seed' },
    { value: 'soil_amendment', label: 'Soil Amendment' },
    { value: 'growth_regulator', label: 'Growth Regulator' },
    { value: 'other', label: 'Other' },
];

export const SHARED_FIELDS = [
    { key: 'commercial_name', label: 'Commercial name' },
    { key: 'sku', label: 'SKU' },
    { key: 'manufacturer_name_ar', label: 'Manufacturer name (AR)' },
    { key: 'manufacturer_name_en', label: 'Manufacturer name (EN)' },
    { key: 'brand_name_ar', label: 'Brand name (AR)' },
    { key: 'brand_name_en', label: 'Brand name (EN)' },
    { key: 'country_of_origin', label: 'Country of origin' },
    { key: 'registration_number', label: 'Registration number' },
    { key: 'package_size', label: 'Package size', type: 'number' },
    { key: 'package_unit', label: 'Package unit' },
];

export const SHARED_REGISTRATION_STATUS_OPTIONS = [
    { value: 'registered', label: 'Registered' },
    { value: 'pending', label: 'Pending review' },
    { value: 'expired', label: 'Expired' },
    { value: 'unregistered', label: 'Unregistered' },
];

export const SHARED_REPEATERS = [
    { key: 'barcodes', label: 'Barcodes', button: 'Add barcode', placeholder: 'Enter product barcode', hint: 'You can enter multiple barcodes for the same product.' },
    { key: 'aliases', label: 'Aliases', button: 'Add alias', placeholder: 'Enter alias' },
    { key: 'keywords', label: 'Keywords', button: 'Add keyword', placeholder: 'Enter keyword' },
];

export const SHARED_TEXTAREAS = [
    { key: 'short_description', label: 'Short description' },
    { key: 'approved_description', label: 'Approved description' },
];

export const AGRI_COMMON_REPEATERS = [
    { key: 'approved_uses', label: 'Approved uses', button: 'Add use', placeholder: 'Enter approved use' },
    { key: 'application_methods', label: 'Application methods', button: 'Add method', placeholder: 'Enter application method' },
    { key: 'application_rates', label: 'Application rates', button: 'Add rate', placeholder: 'Enter application rate' },
    { key: 'storage_conditions', label: 'Storage conditions', button: 'Add condition', placeholder: 'Enter storage condition' },
    { key: 'warnings', label: 'Warnings', button: 'Add warning', placeholder: 'Enter warning' },
    { key: 'growth_stages', label: 'Growth stages', button: 'Add stage', placeholder: 'Enter growth stage' },
];

export const PESTICIDE_SUBTYPES = ['pesticide'];
export const FERTILIZER_SUBTYPES = ['fertilizer', 'soil_amendment', 'growth_regulator'];
export const SEED_SUBTYPES = ['seed'];

export const PESTICIDE_FIELDS = [
    { key: 'pesticide_type', label: 'Pesticide type' },
    { key: 'chemical_group', label: 'Chemical group' },
    { key: 're_entry_interval_hours', label: 'Re-entry interval (hours)', type: 'number' },
    { key: 'toxicity_class', label: 'Toxicity class' },
    { key: 'max_applications', label: 'Max applications' },
    { key: 'application_interval_days', label: 'Application interval (days)' },
];

export const PESTICIDE_REPEATERS = [
    { key: 'active_ingredients', label: 'Active ingredients', button: 'Add active ingredient', placeholder: 'Enter active ingredient' },
    { key: 'target_crops', label: 'Target crops', button: 'Add crop', placeholder: 'Enter target crop' },
    { key: 'target_pests', label: 'Target pests', button: 'Add pest', placeholder: 'Enter target pest' },
    { key: 'pre_harvest_intervals', label: 'Pre-harvest intervals', button: 'Add interval', placeholder: 'Enter pre-harvest interval' },
    { key: 'ppe_requirements', label: 'PPE requirements', button: 'Add requirement', placeholder: 'Enter PPE requirement' },
    { key: 'first_aid', label: 'First aid', button: 'Add first aid step', placeholder: 'Enter first aid step' },
    { key: 'compatibility', label: 'Compatibility', button: 'Add item', placeholder: 'Enter compatibility detail' },
    { key: 'environmental_hazards', label: 'Environmental hazards', button: 'Add hazard', placeholder: 'Enter environmental hazard' },
];

export const PESTICIDE_TEXTAREAS = [
    { key: 'mode_of_action', label: 'Mode of action' },
    { key: 'resistance_management', label: 'Resistance management' },
    { key: 'container_disposal', label: 'Container disposal' },
];

export const FERTILIZER_FIELDS = [
    { key: 'fertilizer_type', label: 'Fertilizer type' },
    { key: 'organic_matter_percent', label: 'Organic matter %', type: 'number' },
    { key: 'ph_value', label: 'PH value' },
    { key: 'solubility', label: 'Solubility' },
    { key: 'nutrient_n_percent', label: 'Nitrogen %', type: 'number' },
    { key: 'nutrient_p_percent', label: 'Phosphorus %', type: 'number' },
    { key: 'nutrient_k_percent', label: 'Potassium %', type: 'number' },
];

export const FERTILIZER_REPEATERS = [
    { key: 'micronutrients', label: 'Micronutrients', button: 'Add nutrient', placeholder: 'Enter micronutrient' },
    { key: 'fertilization_methods', label: 'Fertilization methods', button: 'Add method', placeholder: 'Enter fertilization method' },
];

export const SEED_FIELDS = [
    { key: 'crop_name_ar', label: 'Crop name (AR)' },
    { key: 'crop_name_en', label: 'Crop name (EN)' },
    { key: 'variety_name', label: 'Variety name' },
    { key: 'variety_type', label: 'Variety type' },
    { key: 'germination_percent', label: 'Germination %', type: 'number' },
    { key: 'purity_percent', label: 'Purity %', type: 'number' },
    { key: 'maturity_days', label: 'Maturity days' },
];

export const SEED_REPEATERS = [
    { key: 'seed_treatment', label: 'Seed treatment', button: 'Add treatment', placeholder: 'Enter seed treatment' },
    { key: 'disease_resistance', label: 'Disease resistance', button: 'Add resistance', placeholder: 'Enter disease resistance' },
    { key: 'planting_windows', label: 'Planting windows', button: 'Add window', placeholder: 'Enter planting window' },
    { key: 'seeding_rate', label: 'Seeding rate', button: 'Add rate', placeholder: 'Enter seeding rate' },
    { key: 'planting_depth', label: 'Planting depth', button: 'Add depth', placeholder: 'Enter planting depth' },
    { key: 'plant_spacing', label: 'Plant spacing', button: 'Add spacing', placeholder: 'Enter plant spacing' },
    { key: 'expected_yield', label: 'Expected yield', button: 'Add value', placeholder: 'Enter expected yield' },
];

export const VETERINARY_FIELDS = [
    { key: 'concentration', label: 'Concentration' },
    { key: 'dosage_form', label: 'Dosage form' },
    { key: 'treatment_duration', label: 'Treatment duration' },
    { key: 'withdrawal_meat_days', label: 'Withdrawal meat (days)', type: 'number' },
    { key: 'withdrawal_milk_days', label: 'Withdrawal milk (days)', type: 'number' },
    { key: 'withdrawal_eggs_days', label: 'Withdrawal eggs (days)', type: 'number' },
    { key: 'shelf_life_after_opening', label: 'Shelf life after opening' },
];

export const VETERINARY_REPEATERS = [
    { key: 'active_ingredients', label: 'Active ingredients', button: 'Add active ingredient', placeholder: 'Enter active ingredient' },
    { key: 'routes_of_administration', label: 'Routes of administration', button: 'Add route', placeholder: 'Enter administration route' },
    { key: 'target_species', label: 'Target species', button: 'Add species', placeholder: 'Enter target species' },
    { key: 'indications', label: 'Indications', button: 'Add indication', placeholder: 'Enter indication' },
    { key: 'dosage_instructions', label: 'Dosage instructions', button: 'Add instruction', placeholder: 'Enter dosage instruction' },
    { key: 'contraindications', label: 'Contraindications', button: 'Add contraindication', placeholder: 'Enter contraindication' },
    { key: 'warnings', label: 'Warnings', button: 'Add warning', placeholder: 'Enter warning' },
    { key: 'adverse_reactions', label: 'Adverse reactions', button: 'Add reaction', placeholder: 'Enter adverse reaction' },
    { key: 'drug_interactions', label: 'Drug interactions', button: 'Add interaction', placeholder: 'Enter drug interaction' },
    { key: 'storage_conditions', label: 'Storage conditions', button: 'Add condition', placeholder: 'Enter storage condition' },
];

export const VETERINARY_TEXTAREAS = [
    { key: 'pregnancy_lactation_use', label: 'Pregnancy / lactation use' },
    { key: 'special_precautions', label: 'Special precautions' },
    { key: 'overdose_information', label: 'Overdose information' },
    { key: 'disposal_instructions', label: 'Disposal instructions' },
    { key: 'medical_disclaimer', label: 'Medical disclaimer' },
];

export function emptyDetailValues(fields = [], repeaters = [], textareas = []) {
    const values = {};
    fields.forEach((f) => { values[f.key] = ''; });
    repeaters.forEach((r) => { values[r.key] = []; });
    textareas.forEach((t) => { values[t.key] = ''; });
    return values;
}

/**
 * Merges an API detail-bucket response into a schema-shaped base object,
 * keeping only keys the schema declares. The API's detail rows also carry
 * db metadata (id, foreign keys, timestamps) that must never round-trip
 * back into a submit payload.
 */
export function mergeKnownDetailValues(base, incoming) {
    const source = incoming ?? {};
    const merged = { ...base };
    Object.keys(base).forEach((key) => {
        if (key in source && source[key] !== null && source[key] !== undefined) {
            merged[key] = source[key];
        }
    });
    return merged;
}
