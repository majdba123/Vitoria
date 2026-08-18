<?php

namespace App\Services\Vendor;

use App\Models\City;
use App\Models\Vendor;
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
     * @return array{vendors: list<array<string, mixed>>, unmapped: list<array<string, mixed>>, counts: array<string, int>, cities: list<array{id: int, name: string}>}
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
            'vendors' => $mapped->map(fn (Vendor $vendor): array => $this->point($vendor, $withAdminActions))->values()->all(),
            'unmapped' => $unmapped->map(fn (Vendor $vendor): array => $this->point($vendor, $withAdminActions))->values()->all(),
            'counts' => [
                'total' => $vendors->count(),
                'mapped' => $mapped->count(),
                'unmapped' => $unmapped->count(),
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
     * A single map point. Deliberately hand-built rather than reusing
     * VendorResource: the map must never carry account credentials, owner
     * contact details, commercial-register paths or financial columns.
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
            'latitude' => $vendor->latitude !== null ? (float) $vendor->latitude : null,
            'longitude' => $vendor->longitude !== null ? (float) $vendor->longitude : null,
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
