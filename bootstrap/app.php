<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsSyndicate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware(['api', 'auth:sanctum', 'admin'])
                ->prefix('api/admin')
                ->as('admin.')
                ->group(base_path('routes/api_admin.php'));

            Route::middleware(['api', 'auth:sanctum', 'vendor'])
                ->prefix('api/vendor')
                ->as('vendor.')
                ->group(base_path('routes/api_vendor.php'));

            Route::middleware(['api', 'auth:sanctum', 'syndicate'])
                ->prefix('api/syndicate')
                ->as('syndicate.')
                ->group(base_path('routes/api_syndicate.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [\App\Http\Middleware\SetLocale::class]);
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: ['api/*']);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'vendor' => \App\Http\Middleware\EnsureUserIsVendor::class,
            'syndicate' => EnsureUserIsSyndicate::class,
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
            'timezone' => \App\Http\Middleware\ApplyUserTimezone::class,
        ]);
        $middleware->appendToGroup('web', [\App\Http\Middleware\ApplyUserTimezone::class]);
        $middleware->appendToGroup('api', [\App\Http\Middleware\ApplyUserTimezone::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
