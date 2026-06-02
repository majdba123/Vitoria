<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubcategoryRequest;
use App\Http\Requests\Admin\UpdateSubcategoryRequest;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SubcategoryController extends Controller
{
    /**
     * List all subcategories.
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Subcategory::query()->with('category');

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $subcategories = $query->latest()->get();

        return response()->json([
            'message' => __('Subcategories retrieved successfully.'),
            'data' => $subcategories,
        ]);
    }

    /**
     * Show a specific subcategory.
     */
    public function show(Subcategory $subcategory): JsonResponse
    {
        $subcategory->load('category');

        return response()->json([
            'message' => __('Subcategory retrieved successfully.'),
            'data' => $subcategory,
        ]);
    }

    /**
     * Create a new subcategory.
     */
    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('subcategories', 'public');
        }

        $subcategory = Subcategory::create($data);
        $subcategory->load('category');
        $this->flushCategoryCache();

        return response()->json([
            'message' => __('Subcategory created successfully.'),
            'data' => $subcategory,
        ], 201);
    }

    /**
     * Update an existing subcategory.
     */
    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($subcategory->image) {
                Storage::disk('public')->delete($subcategory->image);
            }
            $data['image'] = $request->file('image')->store('subcategories', 'public');
        } else {
            // Remove image from data if not being updated
            unset($data['image']);
        }

        $subcategory->update($data);
        $subcategory->load('category');
        $this->flushCategoryCache();

        return response()->json([
            'message' => __('Subcategory updated successfully.'),
            'data' => $subcategory,
        ]);
    }

    /**
     * Delete a subcategory.
     */
    public function destroy(Subcategory $subcategory): JsonResponse
    {
        // Delete image if exists
        if ($subcategory->image) {
            Storage::disk('public')->delete($subcategory->image);
        }

        $subcategory->delete();
        $this->flushCategoryCache();

        return response()->json([
            'message' => __('Subcategory deleted successfully.'),
        ]);
    }

    protected function flushCategoryCache(): void
    {
        Cache::forget('admin_dashboard_overview');

        try {
            Cache::tags(['categories'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }
    }
}
