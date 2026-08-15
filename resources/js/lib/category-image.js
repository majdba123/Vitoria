/**
 * Resolve a display image for a category card, falling back through the
 * dynamic per-category image, its logo, then its icon. Returns null when the
 * category has none of these — callers render a brand-neutral icon fallback
 * in that case rather than a generic stock photo.
 */
export function categoryImageUrl(category) {
    if (category.image_url) return category.image_url;
    if (category.logo) return `/storage/${category.logo}`;
    if (category.icon) return `/storage/${category.icon}`;
    return null;
}
