<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubcategoryRequest;
use App\Http\Requests\Admin\UpdateSubcategoryRequest;
use App\Models\Subcategory;
use App\Services\Import\CsvImportException;
use App\Services\Import\Importers\SubcategoryImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubcategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 24), 1), 100);
        $subcategories = Subcategory::query()
            ->with('category:id,name,type')
            ->withCount('products')
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('type'), fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('type', (string) $request->input('type'))))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->input('search');

                $query->where(function ($builder) use ($search) {
                    $builder->where('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => __('Subcategories retrieved successfully.'),
            'data' => $subcategories->items(),
            'meta' => [
                'current_page' => $subcategories->currentPage(),
                'last_page' => $subcategories->lastPage(),
                'per_page' => $subcategories->perPage(),
                'total' => $subcategories->total(),
            ],
        ]);
    }

    public function show(Subcategory $subcategory): JsonResponse
    {
        $subcategory->load('category:id,name,type')->loadCount('products');

        return response()->json([
            'message' => __('Subcategory retrieved successfully.'),
            'data' => $subcategory,
        ]);
    }

    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        $subcategory = Subcategory::query()->create($request->validated())->load('category:id,name,type');

        return response()->json([
            'message' => __('Subcategory created successfully.'),
            'data' => $subcategory,
        ], 201);
    }

    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): JsonResponse
    {
        $subcategory->update($request->validated());
        $subcategory->load('category:id,name,type');

        return response()->json([
            'message' => __('Subcategory updated successfully.'),
            'data' => $subcategory,
        ]);
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        $subcategory->delete();

        return response()->json([
            'message' => __('Subcategory deleted successfully.'),
        ]);
    }

    public function importTemplate(): StreamedResponse
    {
        return (new SubcategoryImporter)->template();
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $result = (new SubcategoryImporter)->import($request->file('file'));
        } catch (CsvImportException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __(':created of :total subcategories imported successfully.', [
                'created' => $result['created'],
                'total' => $result['total_rows'],
            ]),
            'data' => $result,
        ]);
    }
}
