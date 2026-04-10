<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    /**
     * List all cities (e.g. for registration and vendor forms).
     */
    public function index(): JsonResponse
    {
        $cities = City::query()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'message' => __('Cities retrieved successfully.'),
            'data' => $cities,
        ]);
    }
}
