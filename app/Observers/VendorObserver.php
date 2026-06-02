<?php

namespace App\Observers;

use App\Models\Vendor;
use App\Services\ApplicationCacheService;

class VendorObserver
{
    public function __construct(protected ApplicationCacheService $cacheService) {}

    public function created(Vendor $vendor): void
    {
        $this->flush();
    }

    public function updated(Vendor $vendor): void
    {
        $this->flush();
    }

    public function deleted(Vendor $vendor): void
    {
        $this->flush();
    }

    public function restored(Vendor $vendor): void
    {
        $this->flush();
    }

    public function forceDeleted(Vendor $vendor): void
    {
        $this->flush();
    }

    protected function flush(): void
    {
        $this->cacheService->flushVendors();
        $this->cacheService->flushProducts();
    }
}
