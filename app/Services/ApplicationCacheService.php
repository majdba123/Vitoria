<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class ApplicationCacheService
{
    public const SETTINGS_WEBSITE = 'settings:website';

    public const DASHBOARD_ADMIN_STATS = 'dashboard:admin:stats';

    public const ADMIN_DASHBOARD_LEGACY = 'admin_dashboard_overview';

    public function remember(string $key, int $ttl, \Closure $callback, array $tags = []): mixed
    {
        try {
            return $tags === []
                ? Cache::remember($key, $ttl, $callback)
                : Cache::tags($tags)->remember($key, $ttl, $callback);
        } catch (\Exception) {
            return $callback();
        }
    }

    public function categoriesKey(?string $type = null): string
    {
        return $type ? "categories:{$type}" : 'categories:all';
    }

    public function productFiltersKey(array $filters): string
    {
        ksort($filters);

        return 'products:filters:'.sha1(json_encode($filters, JSON_THROW_ON_ERROR));
    }

    public function flushCategories(): void
    {
        $this->forgetMany([
            $this->categoriesKey(),
            $this->categoriesKey(Category::TYPE_AGRICULTURE),
            $this->categoriesKey(Category::TYPE_VETERINARY),
        ]);
        $this->flushTags(['categories']);
        $this->flushDashboard();
    }

    public function flushProducts(): void
    {
        $this->flushTags(['products']);
        $this->flushDashboard();
    }

    public function flushVendors(): void
    {
        $this->flushTags(['vendors']);
        $this->flushDashboard();
    }

    public function flushOrders(): void
    {
        $this->flushTags(['orders']);
        $this->flushTags(['products']);
        $this->flushDashboard();
    }

    public function flushSettings(): void
    {
        $this->forgetMany([self::SETTINGS_WEBSITE]);
        $this->flushTags(['settings']);
    }

    public function flushDashboard(): void
    {
        $this->forgetMany([self::DASHBOARD_ADMIN_STATS, self::ADMIN_DASHBOARD_LEGACY]);
        $this->flushTags(['dashboard']);
    }

    /**
     * @param  array<int, string>  $keys
     */
    protected function forgetMany(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * @param  array<int, string>  $tags
     */
    protected function flushTags(array $tags): void
    {
        try {
            Cache::tags($tags)->flush();
        } catch (\Exception) {
            //
        }
    }
}
