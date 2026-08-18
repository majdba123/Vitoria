import { useEffect, useRef, useState } from 'react';
import { MapPin } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { useI18n } from '@/hooks/use-i18n';
import { FALLBACK_CENTER, FALLBACK_ZOOM, TILE_ATTRIBUTION, TILE_URL, isFiniteCoordinate, loadLeaflet } from './leaflet';

/**
 * Click-the-map (or type-the-numbers) location picker.
 *
 * The pair is the unit: `onChange` always emits both coordinates or both empty,
 * which mirrors the `required_with` pair rule the API enforces. Nothing is
 * geocoded — the pin only ever reflects what the operator picked.
 */
export function LocationPicker({ latitude, longitude, onChange, error, disabled = false }) {
    const { common } = useI18n();
    const containerRef = useRef(null);
    const mapRef = useRef(null);
    const markerRef = useRef(null);
    const changeRef = useRef(onChange);
    const [failed, setFailed] = useState(false);
    // Flipped once the map instance exists, so the pin-sync effect below is
    // re-run then rather than depending on promise-callback ordering.
    const [isReady, setIsReady] = useState(false);

    changeRef.current = onChange;

    const hasPin = isFiniteCoordinate(latitude, longitude);

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

                map.on('click', (event) => {
                    changeRef.current({
                        latitude: Number(event.latlng.lat.toFixed(6)),
                        longitude: Number(event.latlng.lng.toFixed(6)),
                    });
                });

                mapRef.current = map;
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
            markerRef.current = null;
        };
    }, []);

    useEffect(() => {
        if (!isReady) {
            return;
        }

        loadLeaflet().then((L) => {
            const map = mapRef.current;

            if (!L || !map) {
                return;
            }

            if (!hasPin) {
                markerRef.current?.remove();
                markerRef.current = null;
                map.setView(FALLBACK_CENTER, FALLBACK_ZOOM);

                return;
            }

            const position = [Number(latitude), Number(longitude)];

            if (markerRef.current) {
                markerRef.current.setLatLng(position);
            } else {
                markerRef.current = L.circleMarker(position, {
                    radius: 9,
                    weight: 2,
                    color: '#297497',
                    fillColor: '#29a9d1',
                    fillOpacity: 0.85,
                }).addTo(map);
            }

            map.setView(position, Math.max(map.getZoom(), 12));
            map.invalidateSize();
        });
    }, [isReady, hasPin, latitude, longitude]);

    const setCoordinate = (key) => (event) => {
        const value = event.target.value;
        onChange({
            latitude: key === 'latitude' ? value : latitude,
            longitude: key === 'longitude' ? value : longitude,
        });
    };

    return (
        <fieldset className="space-y-3" disabled={disabled}>
            <div>
                <legend className="text-sm font-semibold text-foreground">{common.location_title}</legend>
                <p className="mt-0.5 text-xs text-muted-foreground">{common.location_copy}</p>
            </div>

            {failed ? (
                <p className="rounded-lg border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                    {common.map_unavailable}
                </p>
            ) : (
                <div
                    ref={containerRef}
                    role="region"
                    aria-label={common.location_title}
                    tabIndex={0}
                    className="h-64 w-full overflow-hidden rounded-lg border border-border"
                />
            )}

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label htmlFor="latitude" className="mb-1.5 block text-sm font-medium">{common.location_latitude}</label>
                    <Input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" inputMode="decimal" value={latitude ?? ''} onChange={setCoordinate('latitude')} />
                </div>
                <div>
                    <label htmlFor="longitude" className="mb-1.5 block text-sm font-medium">{common.location_longitude}</label>
                    <Input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" inputMode="decimal" value={longitude ?? ''} onChange={setCoordinate('longitude')} />
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="button" variant="outline" size="sm" onClick={() => onChange({ latitude: '', longitude: '' })}>
                    {common.location_clear}
                </Button>
                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <MapPin className="size-3.5" aria-hidden="true" />
                    {hasPin ? `${latitude}, ${longitude}` : common.location_none}
                </p>
            </div>

            {error && <p className="text-xs font-medium text-[var(--color-danger-strong)]">{error}</p>}
        </fieldset>
    );
}
