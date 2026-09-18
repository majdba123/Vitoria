import { useEffect, useMemo, useState } from 'react';
import { RefreshCw } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { SYRIA_GOVERNORATE_PATHS, SYRIA_VIEWBOX } from './syria-governorates';

// Keep this canvas physical: a geographic map must never be mirrored when
// the surrounding UI switches to Arabic RTL.
export const MAP_VIEWBOX = SYRIA_VIEWBOX;

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
                {status === 'loading' && <Skeleton className="aspect-[572/516] w-full" />}
                {status === 'error' && <button type="button" onClick={load} className="mx-auto flex items-center gap-2 py-12 text-sm font-semibold text-primary"><RefreshCw className="size-4" />{common.retry}</button>}
                {status === 'ready' && (
                    <div className="relative isolate mx-auto w-full max-w-3xl overflow-hidden rounded-lg bg-black p-2 sm:p-4">
                        <div className="relative aspect-[572/516] w-full">
                            <svg viewBox={SYRIA_VIEWBOX} preserveAspectRatio="xMidYMid meet" role="group" aria-label={title} dir="ltr" className="absolute inset-0 size-full">
                                {Object.entries(SYRIA_GOVERNORATE_PATHS).map(([key, path]) => {
                                    const region = regionsByKey.get(key);
                                    const selected = activeKey === key;
                                    return <path key={key} d={path} data-key={key} tabIndex="0" role={adminDrilldown ? 'link' : 'img'} aria-label={accessibleLabel(region)}
                                        className={`${adminDrilldown ? 'cursor-pointer' : ''} stroke-border transition-colors focus:outline-none focus-visible:fill-white/30 focus-visible:stroke-white`}
                                        style={{ fill: selected ? 'rgb(255 255 255 / .35)' : 'var(--color-primary)', strokeWidth: selected ? 2.5 : 1 }}
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
