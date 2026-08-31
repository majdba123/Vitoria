/**
 * Technical product-detail schema. User-facing copy is supplied by the
 * products translation namespace when the schema is rendered.
 */
const fields = (definitions) => definitions.map((definition) => typeof definition === 'string'
    ? { key: definition }
    : { key: definition[0], type: definition[1] });

const repeaters = (keys) => keys.map((key) => ({ key }));

export const AGRICULTURAL_TYPE_OPTIONS = ['pesticide', 'fertilizer', 'seed', 'soil_amendment', 'growth_regulator', 'other'].map((value) => ({ value }));

export const SHARED_FIELDS = fields([
    'commercial_name', 'sku', 'manufacturer_name_ar', 'manufacturer_name_en', 'brand_name_ar', 'brand_name_en',
    'country_of_origin', 'registration_number', ['package_size', 'number'], 'package_unit',
]);
export const SHARED_REGISTRATION_STATUS_OPTIONS = ['registered', 'pending', 'expired', 'unregistered'].map((value) => ({ value }));
export const SHARED_REPEATERS = repeaters(['barcodes', 'aliases', 'keywords']);
export const SHARED_TEXTAREAS = fields(['short_description', 'approved_description']);

export const AGRI_COMMON_REPEATERS = repeaters(['approved_uses', 'application_methods', 'application_rates', 'storage_conditions', 'warnings', 'growth_stages']);
export const PESTICIDE_SUBTYPES = ['pesticide'];
export const FERTILIZER_SUBTYPES = ['fertilizer', 'soil_amendment', 'growth_regulator'];
export const SEED_SUBTYPES = ['seed'];

export const PESTICIDE_FIELDS = fields([
    'pesticide_type', 'chemical_group', ['re_entry_interval_hours', 'number'], 'toxicity_class',
    ['max_applications', 'number'], ['application_interval_days', 'number'],
]);
export const PESTICIDE_REPEATERS = repeaters([
    'active_ingredients', 'target_crops', 'target_pests', 'pre_harvest_intervals', 'ppe_requirements',
    'first_aid', 'compatibility', 'environmental_hazards',
]);
export const PESTICIDE_TEXTAREAS = fields(['mode_of_action', 'resistance_management', 'container_disposal']);

export const FERTILIZER_FIELDS = fields([
    'fertilizer_type', ['organic_matter_percent', 'number'], 'ph_value', 'solubility', ['nutrient_n_percent', 'number'],
    ['nutrient_p_percent', 'number'], ['nutrient_k_percent', 'number'],
]);
export const FERTILIZER_REPEATERS = repeaters(['micronutrients', 'fertilization_methods']);

export const SEED_FIELDS = fields([
    'crop_name_ar', 'crop_name_en', 'variety_name', 'variety_type', ['germination_percent', 'number'],
    ['purity_percent', 'number'], ['maturity_days', 'number'],
]);
export const SEED_REPEATERS = repeaters([
    'seed_treatment', 'disease_resistance', 'planting_windows', 'seeding_rate', 'planting_depth', 'plant_spacing', 'expected_yield',
]);

export const VETERINARY_FIELDS = fields([
    'concentration', 'dosage_form', 'treatment_duration', ['withdrawal_meat_days', 'number'],
    ['withdrawal_milk_days', 'number'], ['withdrawal_eggs_days', 'number'], 'shelf_life_after_opening',
]);
export const VETERINARY_REPEATERS = repeaters([
    'active_ingredients', 'routes_of_administration', 'target_species', 'indications', 'dosage_instructions',
    'contraindications', 'warnings', 'adverse_reactions', 'drug_interactions', 'storage_conditions',
]);
export const VETERINARY_TEXTAREAS = fields([
    'pregnancy_lactation_use', 'special_precautions', 'overdose_information', 'disposal_instructions', 'medical_disclaimer',
]);

export function emptyDetailValues(fieldDefinitions = [], repeaterDefinitions = [], textareaDefinitions = []) {
    const values = {};
    fieldDefinitions.forEach((field) => { values[field.key] = ''; });
    repeaterDefinitions.forEach((repeater) => { values[repeater.key] = []; });
    textareaDefinitions.forEach((textarea) => { values[textarea.key] = ''; });

    return values;
}

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
