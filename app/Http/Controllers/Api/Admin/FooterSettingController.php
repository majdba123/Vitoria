<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFooterSettingRequest;
use App\Models\FooterSetting;
use App\Services\ApplicationCacheService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FooterSettingController extends Controller
{
    public function __construct(
        protected ApplicationCacheService $cacheService,
        protected AuditLogService $auditLogService,
    ) {}

    /**
     * Get current footer (About us) settings.
     */
    public function show(): JsonResponse
    {
        $setting = $this->cacheService->remember(
            ApplicationCacheService::SETTINGS_WEBSITE,
            1800,
            fn () => FooterSetting::instance(),
            ['settings'],
        );

        return response()->json([
            'message' => __('Footer settings retrieved successfully.'),
            'data' => [
                'about_description' => $setting->about_description,
                'facebook_url' => $setting->facebook_url,
                'instagram_url' => $setting->instagram_url,
                'twitter_url' => $setting->twitter_url,
                'contact_email' => $setting->contact_email,
                'contact_address' => $setting->contact_address,
                'default_timezone' => $setting->default_timezone,
            ],
        ]);
    }

    /**
     * Update footer (About us) settings.
     */
    public function update(UpdateFooterSettingRequest $request): JsonResponse
    {
        $before = [];
        $after = [];

        $setting = DB::transaction(function () use ($request, &$after, &$before): FooterSetting {
            $setting = FooterSetting::instance();
            $before = $setting->only(array_keys($request->validated()));
            $setting->fill($request->validated());

            if ($setting->isDirty()) {
                $setting->save();
            }

            $after = $setting->only(array_keys($request->validated()));

            return $setting->refresh();
        });

        if ($before !== $after) {
            $this->auditLogService->record($request->user(), 'settings.updated', 'FooterSetting', $setting->id, $before, $after);
        }

        return response()->json([
            'message' => __('Footer settings updated successfully.'),
            'data' => [
                'about_description' => $setting->about_description,
                'facebook_url' => $setting->facebook_url,
                'instagram_url' => $setting->instagram_url,
                'twitter_url' => $setting->twitter_url,
                'contact_email' => $setting->contact_email,
                'contact_address' => $setting->contact_address,
                'default_timezone' => $setting->default_timezone,
            ],
        ]);
    }
}
