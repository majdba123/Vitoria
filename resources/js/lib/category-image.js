/**
 * Resolve a display image for a category card, falling back through the
 * dynamic per-category image, its logo, then its icon. Returns null when the
 * category has none of these — callers render a brand-neutral icon fallback
 * in that case rather than a generic stock photo.
 */
export function categoryImageUrl(category) {
    const semanticFallbacks = {
        'fa-solid fa-droplet': '/images/category-fallbacks/irrigation.svg',
        'fa-solid fa-hand-holding-medical': '/images/category-fallbacks/animal-care.svg',
        'fa-solid fa-pump-medical': '/images/category-fallbacks/disinfectants.svg',
        'fa-solid fa-user-doctor': '/images/category-fallbacks/veterinary-services.svg',
    };

    const repeatedDemoAssets = ['soil_compost.webp', 'livestock_sheep.webp', 'vaccine_vial.webp', 'vet_exam.webp'];
    const configuredImage = category.image_url || category.logo || category.icon || '';
    const usesRepeatedDemoAsset = repeatedDemoAssets.some((filename) => configuredImage.endsWith(filename));

    if (usesRepeatedDemoAsset && semanticFallbacks[category.icon_class]) {
        return semanticFallbacks[category.icon_class];
    }

    if (category.image_url) return category.image_url;
    if (category.logo) return `/storage/${category.logo}`;
    if (category.icon) return `/storage/${category.icon}`;
    return null;
}
