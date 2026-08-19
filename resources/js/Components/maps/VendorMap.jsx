import { useEffect, useRef, useState } from 'react';
import { FALLBACK_CENTER, FALLBACK_ZOOM, TILE_ATTRIBUTION, TILE_URL, loadLeaflet } from './leaflet';

/**
 * Read-only, Syria-only governorate map. This never plots a vendor's real
 * address or saved coordinates - it only shows how many vendors have a city
 * in each governorate, as a count badge centred on that governorate's fixed
 * reference point. No per-vendor location is ever sent to this component.
 */
export function VendorMap({ governorates, labels, locale, className = 'h-[26rem]' }) {
    const containerRef = useRef(null);
    const mapRef = useRef(null);
    const layerRef = useRef(null);
    const [failed, setFailed] = useState(false);
    // Flipped once the map instance exists, so the marker-draw effect below is
    // re-run then rather than depending on promise-callback ordering.
    const [isReady, setIsReady] = useState(false);

    useEffect(() => {
        let cancelled = false;

        loadLeaflet()
            .then((L) => {
                if (cancelled || !L || !containerRef.current || mapRef.current) {
                    return;
                }

                const map = L.map(containerRef.current, {
                    center: FALLBACK_CENTER,
                    zoom: FALLBACK_ZOOM,
                    scrollWheelZoom: false,
                });

                L.tileLayer(TILE_URL, { attribution: TILE_ATTRIBUTION, maxZoom: 10 }).addTo(map);

                mapRef.current = map;
                layerRef.current = L.layerGroup().addTo(map);
                setIsReady(true);
            })
            .catch(() => {
                if (!cancelled) {
                    setFailed(true);
                }
            });

        return () => {
            cancelled = true;
            setIsReady(false);
            mapRef.current?.remove();
            mapRef.current = null;
            layerRef.current = null;
        };
    }, []);

    useEffect(() => {
        if (!isReady) {
            return;
        }

        loadLeaflet().then((L) => {
            const map = mapRef.current;
            const layer = layerRef.current;

            if (!L || !map || !layer) {
                return;
            }

            layer.clearLayers();

            governorates.forEach((governorate) => {
                const position = [governorate.lat, governorate.lng];
                const name = locale === 'ar' ? governorate.name_ar : governorate.name_en;
                const count = Number(governorate.vendor_count ?? 0);

                L.marker(position, { icon: countIcon(L, count) })
                    .bindTooltip(`${name}: ${count}`, { direction: 'top', offset: [0, -6] })
                    .addTo(layer);
            });

            map.setView(FALLBACK_CENTER, FALLBACK_ZOOM);
            map.invalidateSize();
        });
    }, [isReady, governorates, locale]);

    if (failed) {
        return (
            <p className="rounded-lg border border-dashed border-border px-4 py-10 text-center text-sm text-muted-foreground">
                {labels.unavailable}
            </p>
        );
    }

    return (
        <div
            ref={containerRef}
            role="region"
            aria-label={labels.region}
            tabIndex={0}
            className={`w-full overflow-hidden rounded-lg border border-border ${className}`}
        />
    );
}

/**
 * A round count badge rather than a pin - visually distinct from a location
 * marker, since this point is never a real address, just a governorate
 * total. Literal brand hexes (--color-brand-600/400 equivalents): the icon
 * is inline HTML/CSS handed to Leaflet, which cannot resolve a CSS var().
 */
function countIcon(L, count) {
    const empty = count === 0;

    return L.divIcon({
        className: '',
        html: `<div style="display:flex;align-items:center;justify-content:center;min-width:2.25rem;height:2.25rem;padding:0 0.375rem;border-radius:9999px;border:2px solid ${empty ? '#9ca3af' : '#297497'};background:${empty ? '#f3f4f6' : '#29a9d1'};color:${empty ? '#6b7280' : '#ffffff'};font-weight:700;font-size:0.8125rem;box-shadow:0 1px 3px rgba(0,0,0,0.25);">${count}</div>`,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
    });
}
