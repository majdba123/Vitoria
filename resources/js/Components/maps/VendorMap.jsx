import { useEffect, useRef, useState } from 'react';
import { FALLBACK_CENTER, FALLBACK_ZOOM, TILE_ATTRIBUTION, TILE_URL, loadLeaflet } from './leaflet';

/** Read-only map of vendors that have persisted coordinate pairs. */
export function VendorMap({ vendors, labels, className = 'h-[26rem]' }) {
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

            const bounds = [];
            vendors.forEach((vendor) => {
                const latitude = Number(vendor.latitude);
                const longitude = Number(vendor.longitude);
                if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
                const position = [latitude, longitude];
                bounds.push(position);
                L.marker(position).bindPopup(popup(vendor, labels)).addTo(layer);
            });

            if (bounds.length > 0) map.fitBounds(bounds, { padding: [32, 32], maxZoom: 13 });
            else map.setView(FALLBACK_CENTER, FALLBACK_ZOOM);
            map.invalidateSize();
        });
    }, [isReady, vendors, labels]);

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

function popup(vendor, labels) {
    const root = document.createElement('div');
    root.dir = 'auto';
    [vendor.store_name, vendor.business_type_label, vendor.city_name, vendor.address].filter(Boolean).forEach((value, index) => {
        const line = document.createElement(index === 0 ? 'strong' : 'div');
        line.textContent = value;
        root.appendChild(line);
    });
    if (vendor.edit_url) {
        const link = document.createElement('a');
        link.href = vendor.edit_url;
        link.textContent = labels.edit;
        root.appendChild(link);
    }
    return root;
}
