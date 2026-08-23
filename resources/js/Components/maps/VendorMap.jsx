import { useMemo, useRef, useState } from 'react';
import { useLocale } from '@/hooks/use-i18n';

const REGION_SHAPES = {
    aleppo: '165,82 292,48 350,76 340,170 280,205 170,178 125,120',
    idlib: '105,128 165,82 170,178 126,220 72,190',
    latakia: '58,188 105,128 126,220 102,278 54,270 35,230',
    tartus: '54,270 102,278 128,345 82,390 42,356',
    hama: '126,220 170,178 280,205 304,275 214,306 128,345 102,278',
    homs: '128,345 214,306 304,275 380,332 348,410 240,447 82,390',
    al_hasakah: '350,76 510,24 668,58 620,152 535,170 430,130',
    raqqa: '340,170 350,76 430,130 535,170 520,248 405,266 304,275 280,205',
    deir_ez_zor: '405,266 520,248 620,152 690,184 646,330 520,390 380,332 304,275',
    rif_dimashq: '240,447 348,410 380,332 520,390 440,478 334,508 260,492',
    damascus: '304,421 326,410 340,432 320,448 298,440',
    quneitra: '210,448 240,447 260,492 226,510 194,482',
    daraa: '226,510 260,492 334,508 318,548 242,552',
    as_suwayda: '334,508 440,478 430,536 378,558 318,548',
};

export function VendorMap({ regions = [], labels, className = 'min-h-[26rem]' }) {
    const locale = useLocale();
    const containerRef = useRef(null);
    const [activeKey, setActiveKey] = useState(null);
    const [position, setPosition] = useState({ x: 24, y: 24 });
    const regionsByKey = useMemo(() => new Map(regions.map((region) => [region.key, region])), [regions]);
    const active = activeKey ? regionsByKey.get(activeKey) : null;

    const moveTooltip = (event) => {
        const bounds = containerRef.current?.getBoundingClientRect();
        if (!bounds) return;
        setPosition({
            x: Math.min(Math.max(event.clientX - bounds.left + 14, 12), Math.max(bounds.width - 230, 12)),
            y: Math.min(Math.max(event.clientY - bounds.top + 14, 12), Math.max(bounds.height - 190, 12)),
        });
    };

    return (
        <div ref={containerRef} className={`relative isolate overflow-hidden rounded-xl border border-border bg-muted/25 p-3 sm:p-6 ${className}`}>
            <svg viewBox="0 0 720 580" role="img" aria-label={labels.region} className="mx-auto block h-auto max-h-[34rem] w-full max-w-3xl">
                <title>{labels.region}</title>
                {Object.entries(REGION_SHAPES).map(([key, points]) => {
                    const region = regionsByKey.get(key);
                    const isActive = activeKey === key;
                    return (
                        <polygon
                            key={key}
                            points={points}
                            tabIndex="0"
                            role="button"
                            aria-label={`${locale === 'ar' ? region?.name_ar : region?.name_en}: ${region?.vendor_count ?? 0}`}
                            className="cursor-pointer stroke-background transition-[fill,filter] duration-150 focus:outline-none focus-visible:stroke-primary"
                            style={{
                                fill: isActive ? 'var(--color-primary)' : region?.vendor_count ? 'color-mix(in srgb, var(--color-primary) 72%, var(--color-card))' : 'color-mix(in srgb, var(--color-primary) 28%, var(--color-card))',
                                strokeWidth: isActive ? 5 : 3,
                                filter: isActive ? 'drop-shadow(0 8px 12px rgb(0 0 0 / .18))' : undefined,
                            }}
                            onMouseEnter={(event) => { setActiveKey(key); moveTooltip(event); }}
                            onMouseMove={moveTooltip}
                            onMouseLeave={() => setActiveKey(null)}
                            onFocus={() => setActiveKey(key)}
                            onBlur={() => setActiveKey(null)}
                        />
                    );
                })}
            </svg>

            {active && (
                <div
                    className="pointer-events-none absolute z-20 w-[13.5rem] rounded-lg border border-border bg-popover p-3 text-start text-popover-foreground shadow-xl"
                    style={{ left: position.x, top: position.y }}
                    dir={locale === 'ar' ? 'rtl' : 'ltr'}
                    role="status"
                >
                    <p className="font-bold">{locale === 'ar' ? active.name_ar : active.name_en}</p>
                    <dl className="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                        <dt className="text-muted-foreground">{labels.vendors}</dt><dd className="text-end font-semibold tabular-nums" dir="auto">{active.vendor_count}</dd>
                        <dt className="text-muted-foreground">{labels.active}</dt><dd className="text-end font-semibold tabular-nums" dir="auto">{active.active_count}</dd>
                        <dt className="text-muted-foreground">{labels.mapped}</dt><dd className="text-end font-semibold tabular-nums" dir="auto">{active.mapped_count}</dd>
                        <dt className="text-muted-foreground">{labels.unmapped}</dt><dd className="text-end font-semibold tabular-nums" dir="auto">{active.unmapped_count}</dd>
                    </dl>
                    <p className="mt-2 border-t border-border pt-2 text-[11px] text-muted-foreground">
                        {labels.agriculture}: <b dir="auto">{active.business_types?.agriculture ?? 0}</b> · {labels.veterinary}: <b dir="auto">{active.business_types?.veterinary ?? 0}</b> · {labels.both}: <b dir="auto">{active.business_types?.both ?? 0}</b>
                    </p>
                </div>
            )}
        </div>
    );
}
