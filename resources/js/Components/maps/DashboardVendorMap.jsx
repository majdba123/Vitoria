import { useEffect, useMemo, useState } from 'react';
import { RefreshCw } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';

export const MAP_VIEWBOX = '0 0 512 468';
export const MAP_ASSET = '/images/syria-governorates-map.jpg';

// These are pixel-accurate outlines, traced directly from MAP_ASSET's actual
// fill regions (flood-fill + boundary trace + polygon simplification, not
// hand-guessed), so each hit zone hugs the real drawn shape instead of an
// approximate hand-drawn quad. Keep this canvas physical: a geographic map
// must never be mirrored when the surrounding UI switches to Arabic RTL.
//
// The source artwork draws no real boundary between two groups of
// governorates — each group is one undivided shape in the image (confirmed
// by flood-filling: any seed in the group reaches every other member).
// Rather than fabricate borders that don't exist and show whichever guessed
// name the cursor happens to land on, each group renders as a single honest
// hover/click zone. Keep this in sync with
// App\Support\SyriaGovernorates::MAP_MERGED_GROUPS, which resolves these
// synthetic keys back to real cities server-side.
const MERGED_GROUPS = {
    central: { keys: ['damascus', 'rif_dimashq', 'homs', 'tartus'], labelKey: 'map_region_central' },
    southwest: { keys: ['quneitra', 'daraa'], labelKey: 'map_region_southwest' },
};

export const REGION_SHAPES = {
    aleppo: '200,42 213,42 225,48 233,56 235,67 230,88 216,97 197,100 189,111 189,118 198,130 189,143 186,161 201,177 167,177 151,169 136,171 123,165 115,154 117,145 108,139 101,121 90,112 97,105 97,99 92,93 86,94 78,83 84,53 87,49 106,53 111,56 114,68 125,64 147,66',
    idlib: '88,96 95,101 87,110 88,115 98,122 107,143 114,146 111,152 121,169 112,180 116,184 113,187 110,186 109,178 103,183 95,177 87,179 80,180 65,175 65,152 54,146 55,142 48,142 48,135 50,130 55,130 57,125 61,126 64,123 66,105 86,104',
    latakia: '31,130 36,136 40,136 45,144 52,144 48,152 47,172 50,194 43,194 35,195 28,191 28,176 15,162 17,154 22,149 21,139',
    hama: '53,149 63,152 61,171 64,178 80,183 95,180 101,186 107,182 107,187 112,191 120,187 122,181 118,178 125,168 134,173 152,172 168,180 203,180 206,187 201,207 195,210 184,216 184,223 170,206 151,208 144,211 143,217 134,216 125,221 117,220 105,225 97,217 79,222 64,218 59,223 50,179',
    al_hasakah: '499,0 508,4 511,19 507,25 473,63 440,72 433,87 431,119 440,137 439,160 412,161 397,156 356,117 330,93 319,75 303,71 296,60 326,55 388,21 439,23 455,21 458,18 471,18 486,12',
    raqqa: '235,58 242,61 265,60 276,64 293,61 302,74 311,74 320,80 308,139 310,148 282,190 273,189 232,179 207,179 189,160 192,143 201,131 191,114 199,102 217,100 235,86 238,71',
    deir_ez_zor: '322,85 328,95 396,159 412,164 436,165 433,168 429,196 431,236 416,268 388,285 357,303 349,288 313,241 303,224 301,212 295,201 283,192 313,148 311,139',
    as_suwayda: '74,290 95,292 105,310 112,317 121,319 131,332 143,339 157,361 174,368 188,383 204,394 145,432 133,422 128,422 127,410 124,407 120,408 112,399 104,397 92,382 74,384 66,375 58,381 46,379 36,383 29,380 26,382 20,378 20,373 14,370 26,359 26,355 34,350 34,346 26,342 35,328 61,326 59,321 53,319 54,316 67,303',
    central: '211,180 275,192 291,200 298,213 301,227 355,305 210,392 188,380 177,367 160,360 153,352 147,340 129,327 122,316 109,311 97,290 75,287 73,277 67,268 59,262 54,262 58,256 65,253 66,250 62,246 53,243 42,249 36,245 46,234 56,230 65,221 77,225 97,220 100,226 105,228 112,224 125,224 134,219 144,220 150,211 170,209 183,226 186,224 187,216 199,212 204,207',
    southwest: '88,385 95,387 101,398 111,402 118,411 125,412 123,415 118,414 122,424 125,418 128,425 132,424 141,433 94,464 88,467 68,463 68,448 65,443 60,442 63,435 56,429 56,408 71,404 66,391',
};

export function DashboardVendorMap({ endpoint, adminDrilldown = false }) {
    const locale = useLocale();
    const { common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [payload, setPayload] = useState({ domain: null, regions: [] });
    const [activeKey, setActiveKey] = useState(null);

    const load = () => {
        setStatus('loading');
        window.axios.get(endpoint, { silent: true }).then((response) => {
            setPayload(response.data?.data ?? { domain: null, regions: [] });
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, [endpoint]);

    const regionsByKey = useMemo(() => {
        const map = new Map(payload.regions.map((region) => [region.key, region]));

        Object.entries(MERGED_GROUPS).forEach(([groupKey, { keys, labelKey }]) => {
            const members = keys.map((key) => map.get(key)).filter(Boolean);
            if (members.length === 0) return;

            const sum = (field) => members.reduce((total, member) => total + (member[field] ?? 0), 0);
            map.set(groupKey, {
                key: groupKey,
                name_en: common[labelKey],
                name_ar: common[labelKey],
                ...(payload.domain
                    ? { vendor_count: sum('vendor_count') }
                    : {
                          unique_vendor_count: sum('unique_vendor_count'),
                          agriculture_count: sum('agriculture_count'),
                          veterinary_count: sum('veterinary_count'),
                      }),
            });
        });

        return map;
    }, [payload.regions, payload.domain, common]);
    const active = activeKey ? regionsByKey.get(activeKey) : null;
    const isArabic = locale === 'ar';
    const title = common.map_distribution;
    const agricultureLabel = common.map_agriculture;
    const veterinaryLabel = common.map_veterinary;
    const totalLabel = common.map_total_unique_vendors;
    const scopedLabel = payload.domain === 'agriculture' ? common.map_agricultural_vendors : common.map_veterinary_vendors;
    const separator = isArabic ? '، ' : ', ';

    const accessibleLabel = (region) => {
        const name = isArabic ? region?.name_ar : region?.name_en;
        if (payload.domain) return `${name}${separator}${scopedLabel}: ${region?.vendor_count ?? 0}`;
        return `${name}${separator}${agricultureLabel}: ${region?.agriculture_count ?? 0}${separator}${veterinaryLabel}: ${region?.veterinary_count ?? 0}`;
    };

    const navigate = (key) => {
        if (adminDrilldown) window.location.assign(`/admin/vendors?governorate=${encodeURIComponent(key)}`);
    };

    return (
        <Card className="border-border/80 shadow-none">
            <CardHeader className="border-b border-border/80"><CardTitle className="text-base font-bold">{title}</CardTitle></CardHeader>
            <CardContent className="p-3 sm:p-5">
                {status === 'loading' && <Skeleton className="aspect-[512/468] w-full" />}
                {status === 'error' && <button type="button" onClick={load} className="mx-auto flex items-center gap-2 py-12 text-sm font-semibold text-primary"><RefreshCw className="size-4" />{common.retry}</button>}
                {status === 'ready' && (
                    <div className="relative isolate mx-auto w-full max-w-3xl overflow-hidden rounded-lg bg-black p-2 sm:p-4">
                        <div className="relative aspect-[512/468] w-full">
                            <img src={MAP_ASSET} alt="" className="absolute inset-0 size-full object-contain" draggable="false" />
                            <svg viewBox={MAP_VIEWBOX} preserveAspectRatio="none" role="group" aria-label={title} dir="ltr" className="absolute inset-0 size-full">
                                {Object.entries(REGION_SHAPES).map(([key, points]) => {
                                    const region = regionsByKey.get(key);
                                    const selected = activeKey === key;
                                    return <polygon key={key} points={points} tabIndex="0" role={adminDrilldown ? 'link' : 'img'} aria-label={accessibleLabel(region)}
                                        className={`${adminDrilldown ? 'cursor-pointer' : ''} fill-transparent stroke-transparent transition-colors focus:outline-none focus-visible:fill-white/20 focus-visible:stroke-white`}
                                        style={{ fill: selected ? 'rgb(255 255 255 / .2)' : undefined, stroke: selected ? 'white' : undefined, strokeWidth: selected ? 2 : 0 }}
                                        onMouseEnter={() => setActiveKey(key)} onMouseLeave={() => setActiveKey(null)} onFocus={() => setActiveKey(key)} onBlur={() => setActiveKey(null)}
                                        onClick={() => activeKey === key && navigate(key)} onTouchStart={() => setActiveKey(key)}
                                        onKeyDown={(event) => { if (adminDrilldown && ['Enter', ' '].includes(event.key)) { event.preventDefault(); navigate(key); } }} />;
                                })}
                            </svg>
                        </div>
                        {active && <div role="status" dir={isArabic ? 'rtl' : 'ltr'} className="absolute inset-x-2 bottom-2 z-10 rounded-md border border-border bg-popover p-3 text-sm text-popover-foreground shadow-lg sm:inset-x-auto sm:end-4 sm:w-64">
                            <p className="font-bold">{isArabic ? active.name_ar : active.name_en}</p>
                            {payload.domain ? <p className="mt-1">{scopedLabel}: <b>{active.vendor_count}</b></p> : <div className="mt-1 space-y-0.5"><p>{agricultureLabel}: <b>{active.agriculture_count}</b></p><p>{veterinaryLabel}: <b>{active.veterinary_count}</b></p><p className="text-muted-foreground">{totalLabel}: <b>{active.unique_vendor_count}</b></p></div>}
                            {adminDrilldown && <button type="button" onMouseDown={(event) => event.preventDefault()} onClick={() => navigate(active.key)} className="mt-2 font-semibold text-primary underline">{common.map_view_vendors}</button>}
                        </div>}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
