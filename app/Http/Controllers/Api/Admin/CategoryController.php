<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\ApplicationCacheService;
use App\Services\SelectedProductTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function __construct(
        protected ApplicationCacheService $cacheService,
        protected SelectedProductTypeService $selectedProductTypeService,
    ) {}

    /**
     * List all categories (cached).
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $type = $this->resolvedCategoryTypeFilter($request);
        $perPage = min(max((int) $request->input('per_page', 24), 1), 100);

        $cacheKey = $search ? null : 'categories:list:'.sha1(json_encode([
            'type' => $type,
            'per_page' => $perPage,
            'page' => (int) $request->input('page', 1),
        ], JSON_THROW_ON_ERROR));

        if ($cacheKey) {
            $categories = $this->cacheService->remember($cacheKey, 1800, function () use ($type, $perPage) {
                return Category::query()
                    ->with('subcategories:id,name,image,category_id')
                    ->when($type, fn ($query) => $query->where('type', $type))
                    ->latest()
                    ->paginate($perPage);
            }, ['categories']);
        } else {
            $categories = Category::query()
                ->with('subcategories:id,name,image,category_id')
                ->when($type, fn ($query) => $query->where('type', $type))
                ->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->latest()
                ->paginate($perPage);
        }

        return response()->json([
            'message' => __('Categories retrieved successfully.'),
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * Show a specific category (cached).
     */
    public function show(Request $request, Category $category): JsonResponse
    {
        $this->selectedProductTypeService->abortIfTypeMismatch($request, $category->type);

        $cacheKey = "category:{$category->id}";

        try {
            $data = $this->cacheService->remember($cacheKey, 1800, function () use ($category) {
                $category->load('subcategories:id,name,image,category_id');

                return $category;
            }, ['categories']);
        } catch (\Exception $e) {
            $category->load('subcategories:id,name,image,category_id');
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
            $data['icon'] = $data['logo'];
        }

        $data['icon_class'] = null;

        $category = DB::transaction(fn () => Category::create($data));
        $category->load('subcategories');

        return response()->json([
            'message' => __('Category created successfully.'),
            'data' => $category,
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $newImagePath = $request->file('logo')->store('categories', 'public');
            $this->deleteCategoryImages($category);
            $data['logo'] = $newImagePath;
            $data['icon'] = $data['logo'];
        } else {
            unset($data['logo']);
            unset($data['icon']);
        }

        $data['icon_class'] = null;

        DB::transaction(function () use ($category, $data): void {
            $category->fill($data);

            if ($category->isDirty()) {
                $category->save();
            }
        });
        $category->load('subcategories');

        return response()->json([
            'message' => __('Category updated successfully.'),
            'data' => $category,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->deleteCategoryImages($category);

        DB::transaction(fn () => $category->delete());

        return response()->json([
            'message' => __('Category deleted successfully.'),
        ]);
    }

    protected function preferredCategoryType(Request $request): ?string
    {
        return $this->selectedProductTypeService->resolve($request);
    }

    protected function resolvedCategoryTypeFilter(Request $request): ?string
    {
        if ($request->has('type')) {
            $type = trim((string) $request->input('type'));

            return in_array($type, [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], true)
                ? $type
                : null;
        }

        return $this->preferredCategoryType($request);
    }

    protected function deleteCategoryImages(Category $category): void
    {
        collect([$category->logo, $category->icon])
            ->filter()
            ->unique()
            ->each(fn (string $path) => Storage::disk('public')->delete($path));
    }
}
