<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    /**
     * Public read of a single published static page by slug (spec §38).
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->first();

        if (! $page) {
            return response()->json(['message' => __('Page not found.')], 404);
        }

        return response()->json([
            'message' => __('Page retrieved successfully.'),
            'data' => $page,
        ]);
    }
}
