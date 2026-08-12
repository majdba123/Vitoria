<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Public "Documents & Downloads" access (spec §25). Only ever returns or
 * streams a document whose status is `approved` — a pending, rejected or
 * disabled document stays unreachable through this controller even if its
 * id is known, because the check is against the live database status, not
 * against where the file happens to be stored.
 */
class ProductDocumentController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $documents = ProductDocument::query()
            ->where('product_id', $product->id)
            ->where('status', ProductDocument::STATUS_APPROVED)
            ->get()
            ->map(fn (ProductDocument $document) => [
                'id' => $document->id,
                'type' => $document->type,
                'type_name' => __("product_documents.type.{$document->type}"),
                'title' => $document->title,
                'language' => $document->language,
                'issued_at' => $document->issued_at,
            ])
            ->values();

        return response()->json(['message' => 'Documents retrieved successfully.', 'data' => $documents]);
    }

    public function download(Product $product, int $documentId)
    {
        $document = ProductDocument::query()
            ->where('product_id', $product->id)
            ->where('status', ProductDocument::STATUS_APPROVED)
            ->find($documentId);

        if (! $document || ! Storage::disk('local')->exists($document->file_path)) {
            abort(404, __('product_documents.not_found'));
        }

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }
}
