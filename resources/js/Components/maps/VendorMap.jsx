import { useEffect, useRef, useState } from 'react';
import { FALLBACK_CENTER, FALLBACK_ZOOM, TILE_ATTRIBUTION, TILE_URL, isFiniteCoordinate, loadLeaflet } from './leaflet';

/**
 * Read-only vendor map. Points come straight from the coordinates saved on each
 * vendor — nothing is geocoded or inferred here.
 *
 * Pins are `circleMarker`s (pure SVG) rather than the default icon so no marker
 * image asset has to be resolved through the bundler. The map is a visual
 * enhancement: `VendorMapPanel` renders the same vendors as real text, so the
 * information is never map-only.
 */
export function VendorMap({ points, labels, className = 'h-[26rem]' }) {
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

                L.tileLayer(TILE_URL, { attribution: TILE_ATTRIBUTION, maxZoom: 18 }).addTo(map);

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

            const coordinates = [];

            points.forEach((point) => {
                if (!isFiniteCoordinate(point.latitude, point.longitude)) {
                    return;
                }

                const position = [Number(point.latitude), Number(point.longitude)];
                coordinates.push(position);

                // Literal brand hexes (--color-brand-600/400): Leaflet writes
                // these into SVG presentation attributes, which cannot resolve
                // a CSS var().
                L.circleMarker(position, {
                    radius: 8,
                    weight: 2,
                    color: '#297497',
                    fillColor: '#29a9d1',
                    fillOpacity: 0.85,
                })
                    .bindPopup(popupElement(point, labels))
                    .addTo(layer);
            });

            if (coordinates.length > 0) {
                map.fitBounds(L.latLngBounds(coordinates).pad(0.2), { maxZoom: 13 });
            } else {
                map.setView(FALLBACK_CENTER, FALLBACK_ZOOM);
            }

            map.invalidateSize();
        });
    }, [isReady, points, labels]);

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
 * Popups are built as DOM nodes with textContent rather than an HTML string, so
 * store names and addresses can never be interpreted as markup.
 */
function popupElement(point, labels) {
    const wrapper = document.createElement('div');
    wrapper.className = 'space-y-1 text-sm';

    const name = document.createElement('p');
    name.className = 'font-semibold';
    name.textContent = point.store_name ?? '';
    wrapper.append(name);

    [point.address, point.city_name, point.business_type_label].forEach((value) => {
        if (!value) {
            return;
        }
        const line = document.createElement('p');
        line.className = 'text-xs';
        line.textContent = value;
        wrapper.append(line);
    });

    const products = document.createElement('p');
    products.className = 'text-xs';
    products.textContent = `${labels.products}: ${Number(point.products_count ?? 0)}`;
    wrapper.append(products);

    if (point.edit_url) {
        const edit = document.createElement('a');
        edit.className = 'inline-block text-xs font-semibold underline';
        edit.href = point.edit_url;
        edit.textContent = labels.edit;
        wrapper.append(edit);
    }

    return wrapper;
}
