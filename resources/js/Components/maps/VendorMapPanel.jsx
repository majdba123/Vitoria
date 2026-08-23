import { useCallback, useEffect, useMemo, useState } from 'react';
import { RefreshCw } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n } from '@/hooks/use-i18n';
import { VendorMap } from './VendorMap';

const EMPTY_PAYLOAD = { vendors: [], unmapped: [], counts: { total: 0, mapped: 0, unmapped: 0 }, cities: [], regions: [] };

/**
 * The Map half of the Table/Map switch, shared by the Admin Vendors page and the
 * Syndicate Vendors section. `endpoint` decides the scope: the syndicate
 * endpoint answers only with vendors inside its own canonical scope and without
 * any admin URL, so this component never has to know about roles.
 *
 * `filters` are the caller's own table filters, forwarded so the two views stay
 * in step; the city filter lives here because it is map-specific.
 */
export function VendorMapPanel({ endpoint, filters = {}, cityId: controlledCityId, onCityIdChange, showCityFilter = true }) {
    const { common } = useI18n();
    const [localCityId, setLocalCityId] = useState('all');
    const cityId = controlledCityId ?? localCityId;
    const setCityId = onCityIdChange ?? setLocalCityId;
    const [status, setStatus] = useState('loading');
    const [payload, setPayload] = useState(EMPTY_PAYLOAD);

    const filterKey = JSON.stringify(filters);

    const load = useCallback(() => {
        setStatus('loading');
        window.axios
            .get(endpoint, {
                params: { ...JSON.parse(filterKey), city_id: cityId === 'all' ? undefined : cityId },
                silent: true,
            })
            .then((response) => {
                setPayload({ ...EMPTY_PAYLOAD, ...(response.data?.data ?? {}) });
                setStatus('ready');
            })
            .catch(() => setStatus('error'));
    }, [endpoint, filterKey, cityId]);

    useEffect(() => {
        load();
    }, [load]);

    const mapLabels = useMemo(
        () => ({
            region: common.map_region_label,
            unavailable: common.map_unavailable,
            products: common.map_products,
            edit: common.edit ?? 'Edit',
            vendors: common.vendors ?? 'Vendors',
            active: common.active ?? 'Active',
            mapped: common.map_count_mapped ?? 'Mapped',
            unmapped: common.map_count_unmapped ?? 'Unmapped',
            agriculture: common.type_agriculture ?? 'Agriculture',
            veterinary: common.type_veterinary ?? 'Veterinary',
            both: common.both ?? 'Both',
        }),
        [common],
    );

    const { vendors, unmapped, counts, cities, regions } = payload;

    if (status === 'error') {
        return (
            <Card className="border-border/80 shadow-none">
                <CardContent className="py-12 text-center">
                    <p className="text-sm font-semibold text-foreground">{common.map_load_failed}</p>
                    <button type="button" onClick={load} className="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                        <RefreshCw className="size-3.5" aria-hidden="true" />
                        {common.refresh ?? 'Retry'}
                    </button>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="border-border/80 shadow-none">
            <CardHeader className="gap-3 border-b border-border/80 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <CardTitle className="text-base font-bold">{common.map_title}</CardTitle>
                    <p className="mt-1 text-xs text-muted-foreground">{common.map_copy}</p>
                </div>
                {showCityFilter && <div className="w-full sm:w-56">
                    <label htmlFor="map-city-filter" className="mb-1.5 block text-sm font-medium">{common.map_city_filter}</label>
                    <Select value={cityId} onValueChange={setCityId}>
                        <SelectTrigger id="map-city-filter" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{common.map_all_cities}</SelectItem>
                            {cities.map((city) => (
                                <SelectItem key={city.id} value={String(city.id)}>{city.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>}
            </CardHeader>

            <CardContent className="space-y-5">
                <dl className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    {[
                        [common.map_count_total, counts.total],
                        [common.map_count_mapped, counts.mapped],
                        [common.map_count_unmapped, counts.unmapped],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-md border border-border bg-muted/40 px-4 py-3">
                            <dt className="text-[10px] font-bold uppercase tracking-[0.16em] text-muted-foreground">{label}</dt>
                            <dd className="mt-1 text-lg font-bold tabular-nums text-foreground">{status === 'ready' ? value : '—'}</dd>
                        </div>
                    ))}
                </dl>

                {status === 'loading' ? (
                    <Skeleton className="h-[26rem] w-full" />
                ) : (
                    <>
                        <VendorMap regions={regions} labels={mapLabels} />
                        <ul className="sr-only">
                            {vendors.map((vendor) => (
                                <li key={vendor.id}>{vendor.store_name}: {vendor.city_name}</li>
                            ))}
                        </ul>
                    </>
                )}

                <section aria-labelledby="map-unmapped-heading" className="space-y-3">
                    <h3 id="map-unmapped-heading" className="text-sm font-bold text-foreground">{common.map_unmapped_title}</h3>
                    {status === 'loading' ? (
                        <Skeleton className="h-20 w-full" />
                    ) : unmapped.length === 0 ? (
                        <p className="rounded-md border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">{common.map_unmapped_empty}</p>
                    ) : (
                        <ul className="space-y-2">
                            {unmapped.map((vendor) => (
                                <li key={vendor.id} className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-border bg-card px-4 py-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-foreground">{vendor.store_name}</p>
                                        <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                            {[vendor.address, vendor.city_name].filter(Boolean).join(' — ') || common.not_specified}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <StatusBadge tone={vendor.is_active ? 'success' : 'danger'}>{vendor.is_active ? common.active : common.inactive}</StatusBadge>
                                        {vendor.edit_url && (
                                            <Button asChild variant="outline" size="sm">
                                                <a href={vendor.edit_url}>{common.edit ?? 'Edit'}</a>
                                            </Button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </CardContent>
        </Card>
    );
}
