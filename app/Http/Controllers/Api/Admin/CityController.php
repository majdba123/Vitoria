<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCityRequest;
use App\Http\Requests\Admin\UpdateCityRequest;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $perPage = min(max((int) $request->input('per_page', 24), 1), 100);

        $cities = City::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->withCount('vendors')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'message' => __('Cities retrieved successfully.'),
            'data' => $cities->items(),
            'meta' => [
                'current_page' => $cities->currentPage(),
                'last_page' => $cities->lastPage(),
                'per_page' => $cities->perPage(),
                'total' => $cities->total(),
            ],
        ]);
    }

    public function store(StoreCityRequest $request): JsonResponse
    {
        $city = DB::transaction(fn () => City::query()->create($request->validated()));

        return response()->json([
            'message' => __('City created successfully.'),
            'data' => $city->loadCount('vendors'),
        ], 201);
    }

    public function show(City $city): JsonResponse
    {
        return response()->json([
            'message' => __('City retrieved successfully.'),
            'data' => $city->loadCount('vendors'),
        ]);
    }

    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        DB::transaction(function () use ($city, $request): void {
            $city->fill($request->validated());

            if ($city->isDirty()) {
                $city->save();
            }
        });

        return response()->json([
            'message' => __('City updated successfully.'),
            'data' => $city->fresh()->loadCount('vendors'),
        ]);
    }

    public function destroy(City $city): JsonResponse
    {
        if ($city->vendors()->exists()) {
            return response()->json([
                'message' => __('This city cannot be deleted while vendors are assigned to it.'),
            ], 422);
        }

        DB::transaction(fn () => $city->delete());

        return response()->json([
            'message' => __('City deleted successfully.'),
        ]);
    }
}
