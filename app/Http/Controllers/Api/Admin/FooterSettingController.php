<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFooterSettingRequest;
use App\Models\FooterSetting;
use Illuminate\Http\JsonResponse;

class FooterSettingController extends Controller
{
    /**
     * Get current footer (About us) settings.
     */
    public function show(): JsonResponse
    {
        $setting = FooterSetting::instance();

        return response()->json([
            'message' => __('Footer settings retrieved successfully.'),
            'data' => [
                'about_description' => $setting->about_description,
                'facebook_url' => $setting->facebook_url,
                'instagram_url' => $setting->instagram_url,
                'twitter_url' => $setting->twitter_url,
                'contact_email' => $setting->contact_email,
                'contact_address' => $setting->contact_address,
            ],
        ]);
    }

    /**
     * Update footer (About us) settings.
     */
    public function update(UpdateFooterSettingRequest $request): JsonResponse
    {
        $setting = FooterSetting::instance();
        $setting->update($request->validated());

        return response()->json([
            'message' => __('Footer settings updated successfully.'),
            'data' => [
                'about_description' => $setting->about_description,
                'facebook_url' => $setting->facebook_url,
                'instagram_url' => $setting->instagram_url,
                'twitter_url' => $setting->twitter_url,
                'contact_email' => $setting->contact_email,
                'contact_address' => $setting->contact_address,
            ],
        ]);
    }
}
