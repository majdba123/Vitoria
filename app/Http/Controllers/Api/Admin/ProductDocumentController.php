<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDocument;
use App\Services\Product\ProductDocumentException;
use App\Services\Product\ProductDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Admin review queue for product documents (spec §25).
 */
class ProductDocumentController extends Controller
{
    public function __construct(
        private readonly ProductDocumentService $productDocumentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = ProductDocument::query()
            ->with(['product:id,name', 'vendor:id,store_name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->input('type'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->input('vendor_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        $documents = $query->paginate(20);

        return response()->json([
            'message' => __('api.documents_retrieved'),
            'data' => $documents->getCollection()->map(fn (ProductDocument $document) => $this->present($document))->values(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    public function show(int $documentId): JsonResponse
    {
        $document = ProductDocument::query()->with(['product:id,name', 'vendor:id,store_name'])->findOrFail($documentId);

        return response()->json(['message' => __('api.document_retrieved'), 'data' => $this->present($document)]);
    }

    public function download(int $documentId)
    {
        $document = ProductDocument::query()->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, __('product_documents.not_found'));
        }

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function review(Request $request, int $documentId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([ProductDocument::STATUS_APPROVED, ProductDocument::STATUS_REJECTED])],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
        ]);

        $document = ProductDocument::query()->findOrFail($documentId);

        try {
            $document = $this->productDocumentService->review(
                $document,
                $request->user(),
                $validated['status'],
                $validated['rejection_reason'] ?? null,
            );
        } catch (ProductDocumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('product_documents.reviewed_success'),
            'data' => $this->present($document),
        ]);
    }

    public function disable(int $documentId): JsonResponse
    {
        $document = ProductDocument::query()->findOrFail($documentId);

        try {
            $document = $this->productDocumentService->disable($document);
        } catch (ProductDocumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('product_documents.disabled_success'),
            'data' => $this->present($document),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ProductDocument $document): array
    {
        return [
            'id' => $document->id,
            'product_id' => $document->product_id,
            'product_name' => $document->relationLoaded('product') ? $document->product?->name : null,
            'vendor_id' => $document->vendor_id,
            'vendor_name' => $document->relationLoaded('vendor') ? $document->vendor?->store_name : null,
            'type' => $document->type,
            'type_name' => __("product_documents.type.{$document->type}"),
            'title' => $document->title,
            'language' => $document->language,
            'source' => $document->source,
            'original_filename' => $document->original_filename,
            'file_size' => $document->file_size,
            'status' => $document->status,
            'status_name' => __("product_documents.status.{$document->status}"),
            'rejection_reason' => $document->rejection_reason,
            'issued_at' => $document->issued_at,
            'expires_at' => $document->expires_at,
            'created_at' => $document->created_at,
        ];
    }
}
