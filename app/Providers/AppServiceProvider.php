<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\FooterSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\Syndicate;
use App\Models\Vendor;
use App\Observers\CategoryObserver;
use App\Observers\FooterSettingObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\SyndicateObserver;
use App\Observers\VendorObserver;
use App\Services\ApplicationCacheService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction() && ! app()->runningUnitTests());

        Category::observe(CategoryObserver::class);
        Product::observe(ProductObserver::class);
        Vendor::observe(VendorObserver::class);
        Syndicate::observe(SyndicateObserver::class);
        Order::observe(OrderObserver::class);
        FooterSetting::observe(FooterSettingObserver::class);

        $this->configureRateLimiters();

        View::composer('layouts.app', function ($view): void {
            $view->with('footerSettings', app(ApplicationCacheService::class)->remember(
                ApplicationCacheService::SETTINGS_WEBSITE,
                1800,
                fn () => FooterSetting::instance(),
                ['settings'],
            ));
        });
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('auth.strict', fn (Request $request) => Limit::perMinute(5)->by(
            strtolower(trim((string) $request->input('phone_number', 'guest'))).'|'.$request->ip()
        )->response(fn () => $this->throttleResponse(__('common.too_many_requests'))));

        RateLimiter::for('public.browse', fn (Request $request) => Limit::perMinute(120)->by($request->ip())->response(
            fn () => $this->throttleResponse(__('common.too_many_requests'))
        ));

        RateLimiter::for('search.filters', fn (Request $request) => Limit::perMinute(45)->by($request->ip())->response(
            fn () => $this->throttleResponse(__('common.too_many_requests'))
        ));

        RateLimiter::for('orders.write', fn (Request $request) => Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip()))->response(
            fn () => $this->throttleResponse(__('common.too_many_requests'))
        ));

        RateLimiter::for('dashboard.stats', fn (Request $request) => Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip()))->response(
            fn () => $this->throttleResponse(__('common.too_many_requests'))
        ));

        RateLimiter::for('api.authenticated', fn (Request $request) => Limit::perMinute(180)->by(
            (string) ($request->user()?->id ?? $request->ip())
        )->response(fn () => $this->throttleResponse(__('common.too_many_requests'))));

        RateLimiter::for('api.write', fn (Request $request) => Limit::perMinute(30)->by(
            (string) ($request->user()?->id ?? $request->ip())
        )->response(fn () => $this->throttleResponse(__('common.too_many_requests'))));

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)->by(
            (string) ($request->user()?->id ?? $request->ip())
        )->response(fn () => $this->throttleResponse(__('common.too_many_requests'))));

        RateLimiter::for('notifications.write', fn (Request $request) => Limit::perMinute(20)->by(
            (string) ($request->user()?->id ?? $request->ip())
        )->response(fn () => $this->throttleResponse(__('common.too_many_requests'))));
    }

    protected function throttleResponse(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'status' => 429,
        ], 429);
    }
}
