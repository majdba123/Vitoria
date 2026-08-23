import { useMemo, useRef, useState } from 'react';
import { useLocale } from '@/hooks/use-i18n';

const REGION_SHAPES = {
    aleppo: '92,62 154,43 207,40 237,67 232,127 204,179 122,177 91,145 76,101',
    idlib: '54,86 91,62 91,145 73,180 54,174 42,143',
    latakia: '31,130 54,86 54,174 48,211 29,238 17,203 20,165',
    tartus: '17,203 48,211 64,269 40,314 11,285 4,249',
    hama: '73,180 122,177 204,179 238,207 203,235 132,240 64,269 48,211',
    homs: '64,269 132,240 203,235 291,263 311,321 251,377 164,414 40,314',
    al_hasakah: '298,28 400,1 512,0 451,77 433,163 367,162 318,112',
    raqqa: '232,127 237,67 298,28 318,112 367,162 433,163 406,206 317,207 238,207 204,179',
    deir_ez_zor: '317,207 406,206 451,77 512,51 512,225 452,288 357,333 311,321 291,263',
    rif_dimashq: '164,414 251,377 311,321 357,333 369,399 293,432 218,449',
    damascus: '239,365 254,357 264,369 250,382 236,376',
    quneitra: '95,382 164,414 150,445 112,437 91,412',
    daraa: '112,437 150,445 218,449 190,468 128,468',
    as_suwayda: '218,449 293,432 277,468 190,468',
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
        <div ref={containerRef} className={`relative isolate overflow-hidden rounded-xl border border-border bg-black p-2 sm:p-4 ${className}`}>
            <div className="relative mx-auto aspect-[512/468] w-full max-w-3xl">
                <img
                    src="/images/syria-governorates-map.jpg"
                    alt=""
                    className="absolute inset-0 size-full object-contain"
                    draggable="false"
                />
            <svg viewBox="0 0 512 468" role="img" aria-label={labels.region} className="absolute inset-0 size-full">
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
                                fill: isActive ? 'rgb(255 255 255 / .2)' : 'transparent',
                                stroke: isActive ? 'rgb(255 255 255 / .75)' : 'transparent',
                                strokeWidth: isActive ? 2 : 0,
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
            </div>

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
