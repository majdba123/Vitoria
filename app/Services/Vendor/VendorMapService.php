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
     * The map is deliberately governorate-level only: individual vendor
     * `latitude`/`longitude` never leave this service. Vendors are grouped by
     * their city's governorate and only the count per governorate is
     * returned, plotted at a fixed governorate centroid - never a real
     * vendor address.
     *
     * @param  Closure(): Builder  $scope
     * @param  array{city_id?: int|null, business_type?: string|null, status?: string|null}  $filters
     * @return array{governorates: list<array<string, mixed>>, unassigned: list<array<string, mixed>>, counts: array<string, int>, cities: list<array{id: int, name: string}>}
     */
    public function payload(Closure $scope, array $filters, bool $withAdminActions): array
    {
        $vendors = $this->applyFilters($scope(), $filters)
            ->with('city:id,name')
            ->withCount('products')
            ->orderBy('store_name')
            ->get();

        [$assigned, $unassigned] = $vendors->partition(
            fn (Vendor $vendor): bool => $vendor->city_id !== null,
        );

        $countsByGovernorate = [];
        foreach ($assigned as $vendor) {
            $key = SyriaGovernorates::keyForCity($vendor->city?->name);
            if ($key === null) {
                continue;
            }
            $countsByGovernorate[$key] = ($countsByGovernorate[$key] ?? 0) + 1;
        }

        $governorates = collect(SyriaGovernorates::ALL)
            ->map(fn (array $governorate): array => [
                'key' => $governorate['key'],
                'name_en' => $governorate['name_en'],
                'name_ar' => $governorate['name_ar'],
                'lat' => $governorate['lat'],
                'lng' => $governorate['lng'],
                'vendor_count' => $countsByGovernorate[$governorate['key']] ?? 0,
            ])
            ->values()
            ->all();

        return [
            'governorates' => $governorates,
            'unassigned' => $unassigned->map(fn (Vendor $vendor): array => $this->point($vendor, $withAdminActions))->values()->all(),
            'counts' => [
                'total' => $vendors->count(),
                'assigned' => $assigned->count(),
                'unassigned' => $unassigned->count(),
            ],
            'cities' => $this->cities($scope()),
        ];
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
    protected function point(Vendor $vendor, bool $withAdminActions): array
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

        if ($withAdminActions) {
            $point['edit_url'] = route('admin.vendors.edit', $vendor->id);
        }

        return $point;
    }
}
