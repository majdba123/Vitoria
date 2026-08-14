<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\ApplicationCacheService;
use App\Services\Import\CsvImportException;
use App\Services\Import\Importers\CategoryImporter;
use App\Services\SelectedProductTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                    ->with('subcategories')
                    ->when($type, fn ($query) => $query->where('type', $type))
                    ->latest()
                    ->paginate($perPage);
            }, ['categories']);
        } else {
            $categories = Category::query()
                ->with('subcategories')
                ->when($type, fn ($query) => $query->where('type', $type))
                ->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->latest()
                ->paginate($perPage);
        }

        $isAdminRequest = $this->isAdminRequest($request);

        return response()->json([
            'message' => __('Categories retrieved successfully.'),
            'data' => collect($categories->items())
                ->map(fn (Category $category) => $this->serializeCategory($category, $isAdminRequest))
                ->all(),
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
        $isAdminRequest = $this->isAdminRequest($request);

        if (! $isAdminRequest) {
            $this->selectedProductTypeService->abortIfTypeMismatch($request, $category->type);
        }

        $cacheKey = "category:{$category->id}";

        try {
            $data = $this->cacheService->remember($cacheKey, 1800, function () use ($category) {
                return $category->load('subcategories');
            }, ['categories']);
        } catch (\Exception $e) {
            $data = $category->load('subcategories');
        }

        return response()->json([
            'message' => __('Category retrieved successfully.'),
            'data' => $this->serializeCategory($data, $isAdminRequest),
        ]);
    }

    /**
     * Vendor commission is internal business data (the marketplace's own take
     * rate) - it must never reach the public storefront, only the admin panel
     * that manages it. This shared controller serves both, so the field is
     * stripped here rather than trusted to the caller.
     */
    protected function serializeCategory(Category $category, bool $isAdminRequest): array
    {
        $data = $category->toArray();

        if (! $isAdminRequest) {
            unset($data['commission']);
        }

        return $data;
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

        return response()->json([
            'message' => __('Category updated successfully.'),
            'data' => $category,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => __('This category cannot be deleted while it still has products assigned to it.'),
            ], 422);
        }

        DB::transaction(fn () => $category->delete());

        $this->deleteCategoryImages($category);

        return response()->json([
            'message' => __('Category deleted successfully.'),
        ]);
    }

    public function importTemplate(): StreamedResponse
    {
        return (new CategoryImporter)->template();
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $result = (new CategoryImporter)->import($request->file('file'));
        } catch (CsvImportException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($result['created'] > 0) {
            $this->cacheService->flushCategories();
        }

        return response()->json([
            'message' => __(':created of :total categories imported successfully.', [
                'created' => $result['created'],
                'total' => $result['total_rows'],
            ]),
            'data' => $result,
        ]);
    }

    protected function resolvedCategoryTypeFilter(Request $request): ?string
    {
        if (! $this->isAdminRequest($request)) {
            if ($request->has('type')) {
                $type = trim((string) $request->input('type'));

                return in_array($type, [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], true)
                    ? $type
                    : null;
            }

            return $this->selectedProductTypeService->resolve($request);
        }

        $type = trim((string) $request->input('type'));

        return in_array($type, [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], true)
            ? $type
            : null;
    }

    protected function isAdminRequest(Request $request): bool
    {
        return $request->is('api/admin/*');
    }

    protected function deleteCategoryImages(Category $category): void
    {
        collect([$category->logo, $category->icon])
            ->filter()
            ->unique()
            ->each(fn (string $path) => Storage::disk('public')->delete($path));
    }
}
