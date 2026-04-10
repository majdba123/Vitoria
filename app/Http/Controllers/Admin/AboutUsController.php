<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterSetting;
use Illuminate\View\View;

class AboutUsController extends Controller
{
    /**
     * Show the About us (footer) settings form.
     */
    public function edit(): View
    {
        $setting = FooterSetting::instance();

        return view('admin.about-us.edit', [
            'about_description' => $setting->about_description,
            'facebook_url' => $setting->facebook_url,
            'instagram_url' => $setting->instagram_url,
            'twitter_url' => $setting->twitter_url,
            'contact_email' => $setting->contact_email,
            'contact_address' => $setting->contact_address,
        ]);
    }
}
