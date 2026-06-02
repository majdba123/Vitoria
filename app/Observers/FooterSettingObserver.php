<?php

namespace App\Observers;

use App\Models\FooterSetting;
use App\Services\ApplicationCacheService;

class FooterSettingObserver
{
    public function __construct(protected ApplicationCacheService $cacheService) {}

    public function created(FooterSetting $footerSetting): void
    {
        $this->cacheService->flushSettings();
    }

    public function updated(FooterSetting $footerSetting): void
    {
        $this->cacheService->flushSettings();
    }

    public function deleted(FooterSetting $footerSetting): void
    {
        $this->cacheService->flushSettings();
    }

    public function restored(FooterSetting $footerSetting): void
    {
        $this->cacheService->flushSettings();
    }

    public function forceDeleted(FooterSetting $footerSetting): void
    {
        $this->cacheService->flushSettings();
    }
}
