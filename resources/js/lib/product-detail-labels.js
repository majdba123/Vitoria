import {
    SHARED_FIELDS, SHARED_REPEATERS, SHARED_TEXTAREAS,
    AGRI_COMMON_REPEATERS, PESTICIDE_FIELDS, PESTICIDE_REPEATERS, PESTICIDE_TEXTAREAS,
    FERTILIZER_FIELDS, FERTILIZER_REPEATERS, SEED_FIELDS, SEED_REPEATERS,
    VETERINARY_FIELDS, VETERINARY_REPEATERS, VETERINARY_TEXTAREAS,
} from '@/lib/product-detail-schema';

function toMap(...groups) {
    const map = {};
    groups.flat().forEach((item) => { map[item.key] = item.label; });
    return map;
}

export const SHARED_LABELS = toMap(SHARED_FIELDS, SHARED_REPEATERS, SHARED_TEXTAREAS, [{ key: 'registration_status', label: 'Registration status' }]);

export const AGRICULTURAL_LABELS = toMap(
    [{ key: 'formulation', label: 'Formulation / formula' }, { key: 'agricultural_product_type', label: 'Agricultural product type' }],
    AGRI_COMMON_REPEATERS,
    PESTICIDE_FIELDS, PESTICIDE_REPEATERS, PESTICIDE_TEXTAREAS,
    FERTILIZER_FIELDS, FERTILIZER_REPEATERS,
    SEED_FIELDS, SEED_REPEATERS,
);

export const VETERINARY_LABELS = toMap(VETERINARY_FIELDS, VETERINARY_REPEATERS, VETERINARY_TEXTAREAS);

export function formatDetailValue(value) {
    if (Array.isArray(value)) return value.filter(Boolean).join(', ');
    return value;
}
