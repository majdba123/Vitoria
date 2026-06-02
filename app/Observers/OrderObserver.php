<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\ApplicationCacheService;

class OrderObserver
{
    public function __construct(protected ApplicationCacheService $cacheService) {}

    public function created(Order $order): void
    {
        $this->cacheService->flushOrders();
    }

    public function updated(Order $order): void
    {
        $this->cacheService->flushOrders();
    }

    public function deleted(Order $order): void
    {
        $this->cacheService->flushOrders();
    }

    public function restored(Order $order): void
    {
        $this->cacheService->flushOrders();
    }

    public function forceDeleted(Order $order): void
    {
        $this->cacheService->flushOrders();
    }
}
