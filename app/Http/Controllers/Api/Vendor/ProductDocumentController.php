<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\Vendor;
use App\Services\Product\ProductDocumentException;
use App\Services\Product\ProductDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * A vendor's own product documents (spec §25) — every status is visible to
 * the vendor, but only approved ones are ever shown publicly (see the
 * un-namespaced Api\ProductDocumentController).
 */
class ProductDocumentController extends Controller
{
    public function __construct(
        private readonly ProductDocumentService $productDocumentService,
    ) {}

    public function index(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        $documents = ProductDocument::query()
            ->where('product_id', $product->id)
            ->get()
            ->map(fn (ProductDocument $document) => $this->present($document))
            ->values();

        return response()->json(['message' => __('api.documents_retrieved'), 'data' => $documents]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $vendor = $this->authorizeProduct($request, $product);

        if (! $request->user()->hasVendorPermission($vendor, 'products.manage')) {
            abort(403, __('You are not allowed to manage this vendor\'s products.'));
        }

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(ProductDocument::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'in:ar,en'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:issued_at'],
        ]);

        try {
            $document = $this->productDocumentService->upload(
                $product,
                $request->user(),
                $validated['type'],
                $request->file('file'),
                $validated['title'],
                $validated['language'],
                $validated['issued_at'] ?? null,
                $validated['expires_at'] ?? null,
            );
        } catch (ProductDocumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('product_documents.uploaded_success'),
            'data' => $this->present($document),
        ], 201);
    }

    public function disable(Request $request, Product $product, int $documentId): JsonResponse
    {
        $vendor = $this->authorizeProduct($request, $product);

        if (! $request->user()->hasVendorPermission($vendor, 'products.manage')) {
            abort(403, __('You are not allowed to manage this vendor\'s products.'));
        }

        $document = ProductDocument::query()->where('product_id', $product->id)->findOrFail($documentId);

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

    public function download(Request $request, Product $product, int $documentId)
    {
        $this->authorizeProduct($request, $product);

        $document = ProductDocument::query()->where('product_id', $product->id)->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, __('product_documents.not_found'));
        }

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    private function authorizeProduct(Request $request, Product $product): Vendor
    {
        $vendor = $request->user()?->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }
        if ((int) $product->vendor_id !== (int) $vendor->id) {
            abort(403, __('You do not own this product.'));
        }

        return $vendor;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ProductDocument $document): array
    {
        return [
            'id' => $document->id,
            'type' => $document->type,
            'type_name' => __("product_documents.type.{$document->type}"),
            'title' => $document->title,
            'language' => $document->language,
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
