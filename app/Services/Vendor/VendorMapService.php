<?php

namespace App\Services\Vendor;

use App\Models\City;
use App\Models\Vendor;
use App\Support\SyriaGovernorates;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class VendorMapService
{
    /**
     * Build the Table/Map payload for a caller-supplied vendor scope.
     *
     * `$scope` is a factory rather than a Builder so the filtered vendor read
     * and the city-filter options can each start from a pristine copy of the
     * caller's scope - a Syndicate can therefore never be widened past the
     * vendors its own scope already allows.
     *
     * @param  Closure(): Builder  $scope
     * @param  array{city_id?: int|null, business_type?: string|null, status?: string|null}  $filters
     * @return array{vendors: list<array<string, mixed>>, unmapped: list<array<string, mixed>>, counts: array<string, int>, cities: list<array{id: int, name: string}>, regions: list<array<string, mixed>>}
     */
    public function payload(Closure $scope, array $filters, bool $withAdminActions): array
    {
        $vendors = $this->applyFilters($scope(), $filters)
            ->with('city:id,name')
            ->withCount('products')
            ->orderBy('store_name')
            ->get();

        [$mapped, $unmapped] = $vendors->partition(
            fn (Vendor $vendor): bool => $vendor->latitude !== null && $vendor->longitude !== null,
        );

        return [
            'vendors' => $mapped->map(fn (Vendor $vendor): array => $this->point($vendor, $withAdminActions, true))->values()->all(),
            'unmapped' => $unmapped->map(fn (Vendor $vendor): array => $this->point($vendor, $withAdminActions))->values()->all(),
            'counts' => [
                'total' => $vendors->count(),
                'mapped' => $mapped->count(),
                'unmapped' => $unmapped->count(),
            ],
            'cities' => $this->cities($scope()),
            'regions' => $this->regions($vendors),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Vendor>  $vendors
     * @return list<array<string, mixed>>
     */
    protected function regions(\Illuminate\Support\Collection $vendors): array
    {
        $grouped = $vendors->groupBy(fn (Vendor $vendor): ?string => SyriaGovernorates::keyForCity($vendor->city?->name));

        return collect(SyriaGovernorates::ALL)->map(function (array $region) use ($grouped): array {
            $regionVendors = $grouped->get($region['key'], collect());

            return [
                'key' => $region['key'],
                'name_en' => $region['name_en'],
                'name_ar' => $region['name_ar'],
                'vendor_count' => $regionVendors->count(),
                'active_count' => $regionVendors->where('is_active', true)->count(),
                'mapped_count' => $regionVendors->filter(fn (Vendor $vendor): bool => $vendor->latitude !== null && $vendor->longitude !== null)->count(),
                'unmapped_count' => $regionVendors->filter(fn (Vendor $vendor): bool => $vendor->latitude === null || $vendor->longitude === null)->count(),
                'business_types' => [
                    Vendor::BUSINESS_TYPE_AGRICULTURE => $regionVendors->where('business_type', Vendor::BUSINESS_TYPE_AGRICULTURE)->count(),
                    Vendor::BUSINESS_TYPE_VETERINARY => $regionVendors->where('business_type', Vendor::BUSINESS_TYPE_VETERINARY)->count(),
                    Vendor::BUSINESS_TYPE_BOTH => $regionVendors->where('business_type', Vendor::BUSINESS_TYPE_BOTH)->count(),
                ],
            ];
        })->all();
    }

    /**
     * @param  array{city_id?: int|null, business_type?: string|null, status?: string|null}  $filters
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(($filters['city_id'] ?? null) !== null, fn (Builder $builder) => $builder->where('vendors.city_id', (int) $filters['city_id']))
            ->when(($filters['business_type'] ?? null) !== null, fn (Builder $builder) => $builder->where('vendors.business_type', (string) $filters['business_type']))
            ->when(($filters['status'] ?? null) !== null, fn (Builder $builder) => $builder->where('vendors.status', (string) $filters['status']));
    }

    /**
     * The cities the scope actually contains, so the city filter can only ever
     * narrow what the viewer is already allowed to see.
     *
     * @return list<array{id: int, name: string}>
     */
    protected function cities(Builder $scope): array
    {
        $cityIds = $scope->whereNotNull('vendors.city_id')
            ->distinct()
            ->pluck('vendors.city_id')
            ->all();

        if ($cityIds === []) {
            return [];
        }

        return City::query()
            ->whereIn('id', $cityIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (City $city): array => ['id' => $city->id, 'name' => $city->name])
            ->all();
    }

    /**
     * A single vendor row for the "unassigned" list (vendors with no city
     * set). Deliberately hand-built rather than reusing VendorResource: this
     * must never carry account credentials, owner contact details,
     * commercial-register paths, financial columns, or exact coordinates -
     * the map is governorate-level only, so `latitude`/`longitude` are never
     * exposed here at all.
     *
     * @return array<string, mixed>
     */
    protected function point(Vendor $vendor, bool $withAdminActions, bool $withCoordinates = false): array
    {
        $point = [
            'id' => $vendor->id,
            'store_name' => $vendor->store_name,
            'business_type' => $vendor->business_type,
            'business_type_label' => Vendor::businessTypeLabels()[$vendor->business_type] ?? $vendor->business_type,
            'address' => $vendor->address,
            'city_id' => $vendor->city_id,
            'city_name' => $vendor->city?->name,
            'is_active' => (bool) $vendor->is_active,
            'status' => $vendor->status,
            'products_count' => (int) ($vendor->products_count ?? 0),
        ];

        if ($withCoordinates) {
            $point['latitude'] = (float) $vendor->latitude;
            $point['longitude'] = (float) $vendor->longitude;
        }

        if ($withAdminActions) {
            $point['edit_url'] = route('admin.vendors.edit', $vendor->id);
        }

        return $point;
    }
}
