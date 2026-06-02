<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\FooterSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use App\Observers\CategoryObserver;
use App\Observers\FooterSettingObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\VendorObserver;
use App\Services\ApplicationCacheService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
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
        RateLimiter::for('auth.strict', fn (Request $request) => Limit::perMinute(5)->by($request->ip())->response(
            fn () => response()->json(['message' => __('Too many attempts. Please try again soon.')], 429)
        ));

        RateLimiter::for('public.browse', fn (Request $request) => Limit::perMinute(120)->by($request->ip())->response(
            fn () => response()->json(['message' => __('Too many requests. Please slow down.')], 429)
        ));

        RateLimiter::for('search.filters', fn (Request $request) => Limit::perMinute(45)->by($request->ip())->response(
            fn () => response()->json(['message' => __('Too many searches. Please wait a moment.')], 429)
        ));

        RateLimiter::for('orders.write', fn (Request $request) => Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip()))->response(
            fn () => response()->json(['message' => __('Too many order attempts. Please try again later.')], 429)
        ));

        RateLimiter::for('dashboard.stats', fn (Request $request) => Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip()))->response(
            fn () => response()->json(['message' => __('Dashboard is receiving too many requests. Please wait a moment.')], 429)
        ));
    }
}
