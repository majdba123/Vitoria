<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

/**
 * The stakeholder asked for "أرشفة المعلومات / احتياطي المعلومات" (archiving /
 * backup) implemented safely — not a fake button. These tests prove the
 * scheduled backup infrastructure (config/backup.php, config/filesystems.php,
 * routes/console.php) is wired correctly rather than just present in config
 * files nobody exercises. See docs/operations/BACKUP_AND_RESTORE.md.
 */
it('stores backups on a disk that is never publicly reachable', function () {
    $disks = config('backup.backup.destination.disks');

    expect($disks)->toBe(['backups']);

    $backupsDisk = config('filesystems.disks.backups');

    expect($backupsDisk)->not->toBeNull()
        ->and($backupsDisk['visibility'] ?? null)->toBe('private')
        ->and($backupsDisk)->not->toHaveKey('url');

    $root = $backupsDisk['root'];

    expect(str_starts_with($root, public_path()))->toBeFalse()
        ->and(str_starts_with($root, storage_path('app/public')))->toBeFalse();
});

it('excludes build/vendor/cache noise and never includes the backups disk itself', function () {
    $include = config('backup.backup.source.files.include');
    $exclude = config('backup.backup.source.files.exclude');

    expect($exclude)->toContain(base_path('vendor'))
        ->and($exclude)->toContain(base_path('node_modules'))
        ->and($exclude)->toContain(storage_path('framework'))
        ->and($exclude)->toContain(storage_path('app/backups'));

    // Backing up its own destination would make every run larger than the
    // last and could recurse; the exclude list must prevent that regardless
    // of what gets added to "include" later.
    foreach ($include as $path) {
        expect(str_starts_with(storage_path('app/backups'), $path))->toBeFalse();
    }
});

it('registers the daily backup, cleanup, and monitor commands on the scheduler', function () {
    $schedule = app(Schedule::class);
    $commands = collect($schedule->events())
        ->map(fn ($event) => $event->command ?? '')
        ->filter();

    expect($commands->contains(fn ($c) => str_contains($c, 'backup:run')))->toBeTrue()
        ->and($commands->contains(fn ($c) => str_contains($c, 'backup:clean')))->toBeTrue()
        ->and($commands->contains(fn ($c) => str_contains($c, 'backup:monitor')))->toBeTrue();
});

it('fails loudly instead of silently succeeding when a backup source is broken', function () {
    config(['backup.backup.source.databases' => ['this_connection_does_not_exist']]);

    $exitCode = Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);

    expect($exitCode)->not->toBe(0);
});
