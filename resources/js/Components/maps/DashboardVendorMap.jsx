import { useEffect, useMemo, useState } from 'react';
import { RefreshCw, ZoomIn, ZoomOut } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';
import { SYRIA_GOVERNORATE_PATHS, SYRIA_VIEWBOX } from './syria-governorates';

// Keep this canvas physical: a geographic map must never be mirrored when
// the surrounding UI switches to Arabic RTL.
export const MAP_VIEWBOX = SYRIA_VIEWBOX;

/* Area-weighted centroid of each governorate's largest ring, computed once
   from the static path data (plain "M x,y L x,y ... Z" strings). */
function largestRingCentroid(path) {
    let best = null;
    for (const ring of path.split('M').filter(Boolean)) {
        const points = ring.replace(/Z/g, '').split('L').map((pair) => pair.split(',').map(Number));
        let area = 0;
        let cx = 0;
        let cy = 0;
        points.forEach(([x1, y1], index) => {
            const [x2, y2] = points[(index + 1) % points.length];
            const cross = x1 * y2 - x2 * y1;
            area += cross;
            cx += (x1 + x2) * cross;
            cy += (y1 + y2) * cross;
        });
        if (!best || Math.abs(area) > best.area) best = { area: Math.abs(area), x: cx / (3 * area), y: cy / (3 * area) };
    }
    return best;
}

/* Governorates too narrow for a centred label get a measured nudge (in
   viewBox units). Only the label moves; the geography never does. Damascus
   is smaller than its own label, so its label sits beside it with a leader. */
const LABEL_OFFSETS = {
    damascus: { dx: 30, dy: -24, size: 9, leader: true },
    quneitra: { dx: 1, dy: -6, size: 8 },
    latakia: { dx: -2, dy: -4, size: 8.5 },
    tartus: { dx: -3, dy: 2, size: 8.5 },
    daraa: { dx: -2, dy: 16, size: 9 },
    as_suwayda: { dx: 6, dy: 12 },
    rif_dimashq: { dx: 30, dy: -14 },
};

const GOVERNORATES = Object.entries(SYRIA_GOVERNORATE_PATHS).map(([key, path]) => {
    const centroid = largestRingCentroid(path);
    const offset = LABEL_OFFSETS[key] ?? {};
    return {
        key,
        path,
        anchor: { x: centroid.x, y: centroid.y },
        label: { x: centroid.x + (offset.dx ?? 0), y: centroid.y + (offset.dy ?? 0), size: offset.size ?? 11, leader: Boolean(offset.leader) },
    };
});

export function DashboardVendorMap({ endpoint, adminDrilldown = false }) {
    const locale = useLocale();
    const { common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [payload, setPayload] = useState({ domain: null, regions: [] });
    // Hover and keyboard focus are visual only; the panel reads selectedKey alone.
    const [hoveredKey, setHoveredKey] = useState(null);
    const [focusedKey, setFocusedKey] = useState(null);
    const [selectedKey, setSelectedKey] = useState(null);
    const [zoomed, setZoomed] = useState(false);
    const [tooltipPoint, setTooltipPoint] = useState(null);

    const load = () => {
        setStatus('loading');
        window.axios.get(endpoint, { silent: true }).then((response) => {
            setPayload(response.data?.data ?? { domain: null, regions: [] });
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, [endpoint]);

    const regionsByKey = useMemo(() => new Map(payload.regions.map((region) => [region.key, region])), [payload.regions]);
    const selected = selectedKey ? regionsByKey.get(selectedKey) : null;
    const hovered = hoveredKey ? regionsByKey.get(hoveredKey) : null;
    const visibleRegions = zoomed ? GOVERNORATES.filter(({ key }) => ['damascus', 'rif_dimashq'].includes(key)) : GOVERNORATES;
    const vendorCount = (region) => Number((payload.domain ? region?.vendor_count : region?.unique_vendor_count) ?? 0);
    const maxCount = Math.max(1, ...payload.regions.map(vendorCount));
    /* A quiet tint by vendor count keeps the map readable at a glance without
       competing with the selected region, which alone gets the full primary. */
    const regionFill = (region) => {
        const count = vendorCount(region);
        return count === 0
            ? 'var(--color-muted)'
            : `color-mix(in srgb, var(--color-primary) ${Math.round(12 + (14 * count) / maxCount)}%, var(--color-muted))`;
    };
    const isArabic = locale === 'ar';
    const title = common.map_distribution;
    const agricultureLabel = common.map_agriculture;
    const veterinaryLabel = common.map_veterinary;
    const totalLabel = common.map_total_unique_vendors;
    const scopedLabel = payload.domain === 'agriculture' ? common.map_agricultural_vendors : common.map_veterinary_vendors;
    const number = (value) => formatNumber(value ?? 0, locale);
    const regionName = (region) => (isArabic ? region?.name_ar : region?.name_en);

    const accessibleLabel = (region) => `${regionName(region)} — ${payload.domain ? scopedLabel : totalLabel}: ${number(vendorCount(region))}`;

    const regionStats = (region) => payload.domain
        ? [[scopedLabel, region.vendor_count]]
        : [[totalLabel, region.unique_vendor_count], [agricultureLabel, region.agriculture_count], [veterinaryLabel, region.veterinary_count]];
    const stats = selected ? regionStats(selected) : [];

    const moveTooltip = (event) => {
        const bounds = event.currentTarget.closest('[data-map-canvas]').getBoundingClientRect();
        const x = event.clientX - bounds.left;
        const y = event.clientY - bounds.top;
        setTooltipPoint({ x: Math.max(8, Math.min(x + 12, bounds.width - 216)), y: y + 110 > bounds.height ? y - 110 : y + 12 });
    };

    const clearHover = () => {
        setHoveredKey(null);
        setTooltipPoint(null);
    };

    const hoverProps = (key) => ({
        onPointerEnter: (event) => { if (event.pointerType !== 'touch') { setHoveredKey(key); moveTooltip(event); } },
        onPointerMove: (event) => { if (event.pointerType !== 'touch') { setHoveredKey(key); moveTooltip(event); } },
        onPointerLeave: clearHover,
        onPointerCancel: clearHover,
        onClick: () => { setSelectedKey(key); clearHover(); },
    });

    const renderRegion = (key, path) => {
        const region = regionsByKey.get(key);
        const isSelected = selectedKey === key;
        const isHovered = hoveredKey === key && !isSelected;

        return <path key={key} d={path} data-key={key} tabIndex="0" role="button" aria-pressed={isSelected} aria-label={accessibleLabel(region)}
            data-state={isSelected ? 'selected' : isHovered ? 'hover' : 'idle'}
            className="cursor-pointer outline-none transition-[fill] duration-150 motion-reduce:transition-none"
            style={{
                fill: isSelected ? 'var(--color-primary)' : isHovered ? 'color-mix(in srgb, var(--color-primary) 48%, var(--color-muted))' : regionFill(region),
                stroke: 'var(--color-card)', strokeWidth: 1.5, strokeLinejoin: 'round',
            }}
            {...hoverProps(key)}
            onFocus={(event) => event.currentTarget.matches(':focus-visible') && setFocusedKey(key)}
            onBlur={() => setFocusedKey((current) => (current === key ? null : current))}
            onKeyDown={(event) => { if (['Enter', ' '].includes(event.key)) { event.preventDefault(); setSelectedKey(key); } }} />;
    };

    return (
        <Card className="border-border/80 shadow-none">
            <CardHeader className="border-b border-border/80"><CardTitle className="text-base font-bold">{title}</CardTitle></CardHeader>
            <CardContent className="p-3 sm:p-5">
                {status === 'loading' && <Skeleton className="mx-auto aspect-[572/516] w-full max-w-2xl" />}
                {status === 'error' && <button type="button" onClick={load} className="mx-auto flex items-center gap-2 py-12 text-sm font-semibold text-primary"><RefreshCw className="size-4" />{common.retry}</button>}
                {status === 'ready' && (
                    <div className="@container mx-auto w-full max-w-2xl" data-vendor-map>
                        <div className="relative" data-map-canvas>
                            <div className="mb-2 flex justify-end">
                                <button type="button" onClick={() => { setZoomed((current) => !current); setSelectedKey(null); setFocusedKey(null); clearHover(); }}
                                    aria-label={zoomed ? common.map_zoom_out : common.map_zoom_damascus}
                                    className="inline-flex min-h-11 items-center gap-2 rounded-md border border-border bg-card px-3 text-xs font-semibold text-card-foreground shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring">
                                    {zoomed ? <ZoomOut className="size-4" /> : <ZoomIn className="size-4" />}
                                    {zoomed ? common.map_zoom_out : common.map_zoom_damascus}
                                </button>
                            </div>
                            <svg viewBox={zoomed ? '45 375 35 28' : SYRIA_VIEWBOX} preserveAspectRatio="xMidYMid meet" role="group" aria-label={title} dir="ltr" className="block h-auto w-full select-none overflow-hidden" onPointerLeave={clearHover}>
                            {visibleRegions.map(({ key, path }) => renderRegion(key, path))}
                            {/* Outlines are drawn above every region so a neighbour painted later can never hide them. */}
                            <g aria-hidden="true" pointerEvents="none" fill="none" strokeLinejoin="round">
                                {hoveredKey && hoveredKey !== selectedKey && <path d={SYRIA_GOVERNORATE_PATHS[hoveredKey]} stroke="var(--color-primary)" strokeWidth="1.5" strokeDasharray="4 3" />}
                                {selectedKey && <path d={SYRIA_GOVERNORATE_PATHS[selectedKey]} stroke="var(--color-foreground)" strokeWidth="2.5" />}
                                {/* The focus ring shares the brand colour with the selected fill, so a card-coloured halo keeps it visible on top of it. */}
                                {focusedKey && <path d={SYRIA_GOVERNORATE_PATHS[focusedKey]} stroke="var(--color-card)" strokeWidth="6" />}
                                {focusedKey && <path d={SYRIA_GOVERNORATE_PATHS[focusedKey]} stroke="var(--color-ring)" strokeWidth="3" data-focus-ring />}
                            </g>
                            {/* Labels are only legible once the map itself is ~576px wide (the viewBox at
                                roughly 1:1), which depends on the sidebar as much as the viewport — hence a
                                container query. Narrower maps rely on the panel below. */}
                            <g aria-hidden="true" pointerEvents="none" className="hidden @xl:inline" textAnchor="middle">
                                {!zoomed && GOVERNORATES.map(({ key, anchor, label }) => {
                                    const region = regionsByKey.get(key);
                                    const isSelected = selectedKey === key && !label.leader;
                                    return (
                                        <g key={key} data-label={key}>
                                            {label.leader && <line x1={anchor.x + 3} y1={anchor.y - 3} x2={label.x - 12} y2={label.y + 6} stroke="var(--color-muted-foreground)" strokeWidth="0.75" />}
                                            <text x={label.x} y={label.y} fontSize={label.size} fontWeight="700" style={{ fill: isSelected ? 'var(--color-primary-foreground)' : 'var(--color-foreground)' }}>{regionName(region)}</text>
                                            <text x={label.x} y={label.y + label.size + 1} fontSize={label.size - 1.5} fontWeight="500" style={{ fill: isSelected ? 'var(--color-primary-foreground)' : 'var(--color-muted-foreground)' }}>{number(vendorCount(region))}</text>
                                        </g>
                                    );
                                })}
                            </g>
                            </svg>
                            {hovered && tooltipPoint && (
                                <div role="tooltip" aria-hidden="true" dir={isArabic ? 'rtl' : 'ltr'} className="pointer-events-none absolute z-10 w-52 rounded-lg border border-border bg-card px-3 py-2 text-card-foreground shadow-lg" style={{ left: tooltipPoint.x, top: tooltipPoint.y }}>
                                    <p className="text-sm font-bold">{regionName(hovered)}</p>
                                    {regionStats(hovered).map(([label, value]) => <p key={label} className="mt-1 flex justify-between gap-2 text-xs"><span>{label}</span><span className="font-semibold tabular-nums">{number(value)}</span></p>)}
                                </div>
                            )}
                        </div>

                        <div aria-live="polite" dir={isArabic ? 'rtl' : 'ltr'} className="mt-3 sm:mt-4">
                            {selected ? (
                                <section key={selected.key} data-map-panel={selected.key} aria-label={regionName(selected)}
                                    className="mx-auto w-full max-w-md rounded-lg border border-border bg-card px-4 py-3 text-card-foreground motion-safe:animate-[map-panel-in_160ms_ease-out]">
                                    <h3 className="text-center text-lg font-bold">{regionName(selected)}</h3>
                                    <dl className={`mt-2 grid gap-2 text-center ${stats.length > 1 ? 'grid-cols-3' : 'grid-cols-1'}`}>
                                        {stats.map(([label, value]) => (
                                            <div key={label} className="min-w-0">
                                                <dt className="text-xs leading-snug text-muted-foreground">{label}</dt>
                                                <dd className="mt-0.5 text-xl font-bold tabular-nums text-primary">{number(value)}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                    {adminDrilldown && (
                                        <div className="mt-3 text-center">
                                            <a href={`/admin/vendors?governorate=${encodeURIComponent(selected.key)}`} className="text-sm font-semibold text-primary underline underline-offset-4">{common.map_view_vendors}</a>
                                        </div>
                                    )}
                                </section>
                            ) : (
                                <p className="text-center text-sm text-muted-foreground">{common.map_select_hint}</p>
                            )}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
