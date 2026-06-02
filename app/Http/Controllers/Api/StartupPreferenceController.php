<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStartupPreferenceRequest;
use App\Models\FooterSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class StartupPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $timezone = $user?->timezone
            ?? $request->session()->get('timezone')
            ?? $request->cookie('sz_timezone');

        return response()->json([
            'message' => __('Startup preferences retrieved successfully.'),
            'data' => [
                'completed' => (bool) $timezone || (bool) $request->session()->get('startup_completed') || $request->cookie('sz_startup_completed') === '1',
                'timezone' => $timezone,
                'default_timezone' => FooterSetting::instance()->default_timezone ?: config('app.timezone'),
                'timezones' => $this->timezoneOptions(),
            ],
        ]);
    }

    public function store(StoreStartupPreferenceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $timezone = $validated['timezone'];
        $user = $request->user();

        if ($user) {
            $profileFields = array_intersect_key($validated, array_flip(['timezone', 'city_id', 'latitude', 'longitude']));
            $user->update($profileFields);
        }

        $request->session()->put('timezone', $timezone);
        $request->session()->put('startup_completed', true);
        $request->session()->put('location_preference', $validated['location_preference'] ?? null);

        return response()
            ->json([
                'message' => __('Startup preferences saved successfully.'),
                'data' => [
                    'completed' => true,
                    'timezone' => $timezone,
                ],
            ])
            ->withCookie(Cookie::create('sz_timezone', $timezone, 60 * 24 * 365, '/', null, false, false, false, 'Lax'))
            ->withCookie(Cookie::create('sz_startup_completed', '1', 60 * 24 * 365, '/', null, false, false, false, 'Lax'));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function timezoneOptions(): array
    {
        return collect(timezone_identifiers_list())
            ->filter(fn (string $timezone) => str_starts_with($timezone, 'Asia/')
                || str_starts_with($timezone, 'Europe/')
                || str_starts_with($timezone, 'Africa/')
                || str_starts_with($timezone, 'America/'))
            ->map(fn (string $timezone) => [
                'value' => $timezone,
                'label' => str_replace('_', ' ', $timezone),
            ])
            ->values()
            ->all();
    }
}
