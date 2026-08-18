/**
 * Plain Leaflet, loaded lazily and only in the browser.
 *
 * Inertia SSR renders every page module in Node, where Leaflet has no `window`
 * to attach to — so the library is never imported at module scope. The promise
 * is memoised so several maps on one page share a single download.
 */
let leafletPromise = null;

export function loadLeaflet() {
    if (typeof window === 'undefined') {
        return Promise.resolve(null);
    }

    leafletPromise ??= import('leaflet').then((module) => module.default ?? module);

    return leafletPromise;
}

export const TILE_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

export const TILE_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

/** Centre of Syria — used only when there is no coordinate to centre on. */
export const FALLBACK_CENTER = [34.8021, 38.9968];

export const FALLBACK_ZOOM = 6;

/**
 * Whether both halves of the pair are real numbers.
 *
 * Blank and nullish values are rejected explicitly, because `Number('')` and
 * `Number(null)` are both `0` — treating them as numeric would put a pin on the
 * equator for an empty form, which is exactly the guessed coordinate this
 * feature must never produce.
 */
export function isFiniteCoordinate(latitude, longitude) {
    return isCoordinateNumber(latitude) && isCoordinateNumber(longitude);
}

function isCoordinateNumber(value) {
    if (value === null || value === undefined || (typeof value === 'string' && value.trim() === '')) {
        return false;
    }

    return Number.isFinite(Number(value));
}
