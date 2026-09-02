import { useEffect, useMemo, useState } from 'react';
import { RefreshCw } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';

export const MAP_VIEWBOX = '0 0 512 468';
export const MAP_ASSET = '/images/syria-governorates-map.jpg';

// These coordinates are pixel coordinates from MAP_ASSET. Keep this canvas
// physical: a geographic map must never be mirrored when the surrounding UI
// switches to Arabic RTL.
export const REGION_SHAPES = {
    aleppo: '92,62 154,43 207,40 237,67 232,127 204,179 122,177 91,145 76,101', idlib: '54,86 91,62 91,145 73,180 54,174 42,143',
    latakia: '31,130 54,86 54,174 48,211 29,238 17,203 20,165', tartus: '17,203 48,211 64,269 40,314 11,285 4,249',
    hama: '73,180 122,177 204,179 238,207 203,235 132,240 64,269 48,211', homs: '64,269 132,240 203,235 291,263 311,321 251,377 164,414 40,314',
    al_hasakah: '298,28 400,1 512,0 451,77 433,163 367,162 318,112', raqqa: '232,127 237,67 298,28 318,112 367,162 433,163 406,206 317,207 238,207 204,179',
    deir_ez_zor: '317,207 406,206 451,77 512,51 512,225 452,288 357,333 311,321 291,263', rif_dimashq: '164,414 251,377 311,321 357,333 369,399 293,432 218,449',
    damascus: '239,365 254,357 264,369 250,382 236,376', quneitra: '95,382 164,414 150,445 112,437 91,412',
    daraa: '112,437 150,445 218,449 190,468 128,468', as_suwayda: '218,449 293,432 277,468 190,468',
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

    const regionsByKey = useMemo(() => new Map(payload.regions.map((region) => [region.key, region])), [payload.regions]);
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
