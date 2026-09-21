import { useEffect, useMemo, useState } from 'react';
import { RefreshCw } from 'lucide-react';
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

// Damascus is ~13 viewBox units across, far below a usable touch target on a
// phone; a transparent disc around it widens the pointer target only.
const DAMASCUS = GOVERNORATES.find(({ key }) => key === 'damascus');

export function DashboardVendorMap({ endpoint, adminDrilldown = false }) {
    const locale = useLocale();
    const { common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [payload, setPayload] = useState({ domain: null, regions: [] });
    // Hover and keyboard focus are visual only; the panel reads selectedKey alone.
    const [hoveredKey, setHoveredKey] = useState(null);
    const [focusedKey, setFocusedKey] = useState(null);
    const [selectedKey, setSelectedKey] = useState(null);

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
    const vendorCount = (region) => Number((payload.domain ? region?.vendor_count : region?.unique_vendor_count) ?? 0);
    const maxCount = Math.max(1, ...payload.regions.map(vendorCount));
    /* A quiet tint by vendor count keeps the map readable at a glance without
       competing with the selected region, which alone gets the full primary. */
    const regionFill = (region) => {
        const count = vendorCount(region);
        return count === 0
            ? 'var(--color-muted)'
            : `color-mix(in srgb, var(--color-primary) ${Math.round(12 + (26 * count) / maxCount)}%, var(--color-muted))`;
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

    const stats = selected
        ? (payload.domain
            ? [[scopedLabel, selected.vendor_count]]
            : [[totalLabel, selected.unique_vendor_count], [agricultureLabel, selected.agriculture_count], [veterinaryLabel, selected.veterinary_count]])
        : [];

    const hoverProps = (key) => ({
        // Touch and pen taps must not leave a sticky hover behind.
        onPointerEnter: (event) => event.pointerType === 'mouse' && setHoveredKey(key),
        onPointerLeave: () => setHoveredKey((current) => (current === key ? null : current)),
        onClick: () => setSelectedKey(key),
    });

    return (
        <Card className="border-border/80 shadow-none">
            <CardHeader className="border-b border-border/80"><CardTitle className="text-base font-bold">{title}</CardTitle></CardHeader>
            <CardContent className="p-3 sm:p-5">
                {status === 'loading' && <Skeleton className="mx-auto aspect-[572/516] w-full max-w-2xl" />}
                {status === 'error' && <button type="button" onClick={load} className="mx-auto flex items-center gap-2 py-12 text-sm font-semibold text-primary"><RefreshCw className="size-4" />{common.retry}</button>}
                {status === 'ready' && (
                    <div className="@container mx-auto w-full max-w-2xl" data-vendor-map>
                        <svg viewBox={SYRIA_VIEWBOX} preserveAspectRatio="xMidYMid meet" role="group" aria-label={title} dir="ltr" className="block h-auto w-full select-none" style={{ overflow: 'visible' }}>
                            {GOVERNORATES.map(({ key, path }) => {
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
                            })}
                            {DAMASCUS && <circle cx={DAMASCUS.anchor.x} cy={DAMASCUS.anchor.y} r="11" fill="transparent" aria-hidden="true" className="cursor-pointer" {...hoverProps('damascus')} />}
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
                                {GOVERNORATES.map(({ key, anchor, label }) => {
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
