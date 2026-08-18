<?php

namespace App\Http\Middleware;

use App\Http\Resources\Auth\UserResource;
use App\Models\FooterSetting;
use App\Services\ApplicationCacheService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? (new UserResource($user->loadMissing('syndicate')))->resolve($request) : null,
            ],
            'locale' => app()->getLocale(),
            'direction' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            // Computed from the live request rather than config('app.url') — that
            // config often lags behind the real dev port, and canonical/OpenGraph
            // URLs must reflect the host that actually served the page.
            'origin' => $request->getSchemeAndHttpHost(),
            'i18n' => [
                'admin' => trans('admin'),
                'vendor' => trans('vendor'),
                'syndicate' => trans('syndicate'),
                'employee' => trans('employee'),
                'nav' => trans('nav'),
                'common' => trans('common'),
                'lang' => trans('lang'),
                'products' => trans('products'),
                'categories' => trans('categories'),
                'category' => trans('category'),
                'cart' => trans('cart'),
                'checkout' => trans('checkout'),
                'orders' => trans('orders'),
                'footer' => trans('footer'),
                'home' => trans('home'),
                'startup' => trans('startup'),
                'profile' => trans('profile'),
                'authPage' => trans('auth'),
                'preferences' => trans('preferences'),
                'addresses' => trans('addresses'),
                'pagination' => trans('pagination'),
                'vendors' => trans('vendors'),
                'productSpecs' => trans('productSpecs'),
                'vendorAnalytics' => trans('vendor_analytics'),
            ],
            'footerSettings' => fn () => app(ApplicationCacheService::class)->remember(
                ApplicationCacheService::SETTINGS_WEBSITE,
                1800,
                fn () => FooterSetting::instance(),
                ['settings'],
            ),
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
        ];
    }
}
