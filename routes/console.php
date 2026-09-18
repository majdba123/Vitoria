<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:activate-scheduled-discounts-and-coupons')
    ->daily();

Schedule::command('app:expire-scheduled-discounts-and-coupons')
    ->daily();

// Backup + retention + health monitoring, in that order, so cleanup never
// prunes the run that monitor is about to check. `withoutOverlapping` stops
// a slow backup from stacking a second one on the next minute's schedule
// tick; `onFailure` guarantees a failed run is logged loudly instead of
// disappearing into the scheduler's default swallow-and-continue behavior
// (see docs/operations/BACKUP_AND_RESTORE.md).
Schedule::command('backup:run')
    ->dailyAt('01:30')
    ->withoutOverlapping(120)
    ->onFailure(fn () => Log::error('Scheduled database/file backup failed.'));

Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->withoutOverlapping(60)
    ->onFailure(fn () => Log::error('Scheduled backup cleanup (retention enforcement) failed.'));

Schedule::command('backup:monitor')
    ->dailyAt('03:00')
    ->onFailure(fn () => Log::error('Backup health monitor reports an unhealthy or missing backup.'));
