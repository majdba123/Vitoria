<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * List all categories (cached).
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $cacheKey = $search || $type ? null : 'all_categories';

        if ($cacheKey) {
            try {
                $categories = Cache::tags(['categories'])->remember($cacheKey, 1800, function () {
                    return Category::query()
                        ->with('subcategories:id,name,image,category_id,icon_class')
                        ->latest()
                        ->get();
                });
            } catch (\Exception $e) {
                $categories = Category::query()->with('subcategories:id,name,image,category_id,icon_class')->latest()->get();
            }
        } else {
            $categories = Category::query()
                ->with('subcategories:id,name,image,category_id,icon_class')
                ->when($type, fn ($query) => $query->where('type', $type))
                ->where('name', 'like', '%'.$search.'%')
                ->latest()
                ->get();
        }

        return response()->json([
            'message' => __('Categories retrieved successfully.'),
            'data' => $categories,
        ]);
    }

    /**
     * Show a specific category (cached).
     */
    public function show(Category $category): JsonResponse
    {
        $cacheKey = "category:{$category->id}";

        try {
            $data = Cache::tags(['categories'])->remember($cacheKey, 1800, function () use ($category) {
                $category->load('subcategories:id,name,image,category_id,icon_class');

                return $category;
            });
        } catch (\Exception $e) {
            $category->load('subcategories:id,name,image,category_id,icon_class');
            $data = $category;
        }

        return response()->json([
            'message' => __('Category retrieved successfully.'),
            'data' => $data,
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('categories', 'public');
        }

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }

        $category = Category::create($data);
        $category->load('subcategories');
        $this->flushCategoryCache();

        return response()->json([
            'message' => __('Category created successfully.'),
            'data' => $category,
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($category->logo) {
                Storage::disk('public')->delete($category->logo);
            }
            $data['logo'] = $request->file('logo')->store('categories', 'public');
        } else {
            unset($data['logo']);
        }

        if ($request->hasFile('icon')) {
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        } else {
            unset($data['icon']);
        }

        $category->update($data);
        $category->load('subcategories');
        $this->flushCategoryCache();

        return response()->json([
            'message' => __('Category updated successfully.'),
            'data' => $category,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->logo) {
            Storage::disk('public')->delete($category->logo);
        }

        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }

        $category->delete();
        $this->flushCategoryCache();

        return response()->json([
            'message' => __('Category deleted successfully.'),
        ]);
    }

    protected function flushCategoryCache(): void
    {
        try {
            Cache::tags(['categories'])->flush();
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
