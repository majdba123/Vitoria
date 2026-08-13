<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    /**
     * Public read of currently-visible homepage banners (spec §38).
     */
    public function index(): JsonResponse
    {
        $banners = Banner::query()
            ->currentlyVisible()
            ->orderBy('sort_order')
            ->get(['id', 'title_en', 'title_ar', 'image_path', 'link_url', 'sort_order']);

        return response()->json([
            'message' => __('Banners retrieved successfully.'),
            'data' => $banners,
        ]);
    }
}
