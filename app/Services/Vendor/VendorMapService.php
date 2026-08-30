<?php

namespace App\Services\Vendor;

use App\Models\Vendor;
use App\Support\SyriaGovernorates;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class VendorMapService
{
    /**
     * @param  Closure(): Builder  $scope
     * @return array{domain: string|null, regions: list<array<string, int|string>>}
     */
    public function dashboardPayload(Closure $scope, ?string $domain = null): array
    {
        $rows = $scope()
            ->join('cities', 'cities.id', '=', 'vendors.city_id')
            ->selectRaw('cities.name as city_name, vendors.business_type, COUNT(DISTINCT vendors.id) as vendor_count')
            ->groupBy('cities.name', 'vendors.business_type')
            ->get();
        $counts = [];

        foreach ($rows as $row) {
            $regionKey = SyriaGovernorates::keyForCity($row->city_name);

            if ($regionKey === null) {
                continue;
            }

            $count = (int) $row->vendor_count;
            $counts[$regionKey]['unique_vendor_count'] = ($counts[$regionKey]['unique_vendor_count'] ?? 0) + $count;

            if (in_array($row->business_type, [Vendor::BUSINESS_TYPE_AGRICULTURE, Vendor::BUSINESS_TYPE_BOTH], true)) {
                $counts[$regionKey]['agriculture_count'] = ($counts[$regionKey]['agriculture_count'] ?? 0) + $count;
            }

            if (in_array($row->business_type, [Vendor::BUSINESS_TYPE_VETERINARY, Vendor::BUSINESS_TYPE_BOTH], true)) {
                $counts[$regionKey]['veterinary_count'] = ($counts[$regionKey]['veterinary_count'] ?? 0) + $count;
            }
        }

        return [
            'domain' => $domain,
            'regions' => collect(SyriaGovernorates::ALL)->map(function (array $region) use ($counts, $domain): array {
                $regionCounts = $counts[$region['key']] ?? [];
                $payload = ['key' => $region['key'], 'name_en' => $region['name_en'], 'name_ar' => $region['name_ar']];

                if ($domain !== null) {
                    $payload['vendor_count'] = (int) ($regionCounts['unique_vendor_count'] ?? 0);

                    return $payload;
                }

                return $payload + [
                    'unique_vendor_count' => (int) ($regionCounts['unique_vendor_count'] ?? 0),
                    'agriculture_count' => (int) ($regionCounts['agriculture_count'] ?? 0),
                    'veterinary_count' => (int) ($regionCounts['veterinary_count'] ?? 0),
                ];
            })->all(),
        ];
    }
}
