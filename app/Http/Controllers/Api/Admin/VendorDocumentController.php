<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorDocument;
use App\Services\Vendor\VendorDocumentService;
use App\Services\Vendor\VendorStaffException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Admin review queue for vendor compliance documents (spec §24).
 */
class VendorDocumentController extends Controller
{
    public function __construct(
        private readonly VendorDocumentService $vendorDocumentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->vendorDocumentService->expireOverdue();

        $query = VendorDocument::query()
            ->with(['vendor:id,store_name', 'reviewedBy:id,name'])
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

        $documents = $query->paginate(20);

        return response()->json([
            'message' => 'Documents retrieved successfully.',
            'data' => $documents->getCollection()->map(fn (VendorDocument $document) => $this->present($document))->values(),
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
        $document = VendorDocument::query()->with(['vendor:id,store_name', 'reviewedBy:id,name'])->findOrFail($documentId);

        return response()->json(['message' => 'Document retrieved successfully.', 'data' => $this->present($document)]);
    }

    public function download(int $documentId)
    {
        $document = VendorDocument::query()->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, __('vendor_documents.not_found'));
        }

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function review(Request $request, int $documentId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([VendorDocument::STATUS_VERIFIED, VendorDocument::STATUS_REJECTED])],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
        ]);

        $document = VendorDocument::query()->findOrFail($documentId);

        try {
            $document = $this->vendorDocumentService->review(
                $document,
                $request->user(),
                $validated['status'],
                $validated['rejection_reason'] ?? null,
            );
        } catch (VendorStaffException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('vendor_documents.reviewed_success'),
            'data' => $this->present($document),
        ]);
    }

    public function suspend(Request $request, int $documentId): JsonResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $document = VendorDocument::query()->findOrFail($documentId);

        try {
            $document = $this->vendorDocumentService->suspend($document, $validated['reason'] ?? null);
        } catch (VendorStaffException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('vendor_documents.suspended_success'),
            'data' => $this->present($document),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(VendorDocument $document): array
    {
        return [
            'id' => $document->id,
            'vendor_id' => $document->vendor_id,
            'vendor_name' => $document->relationLoaded('vendor') ? $document->vendor?->store_name : null,
            'type' => $document->type,
            'type_name' => __("vendor_documents.type.{$document->type}"),
            'title' => $document->title,
            'original_filename' => $document->original_filename,
            'file_size' => $document->file_size,
            'issued_at' => $document->issued_at,
            'expires_at' => $document->expires_at,
            'status' => $document->status,
            'status_name' => __("vendor_documents.status.{$document->status}"),
            'rejection_reason' => $document->rejection_reason,
            'reviewed_by' => $document->relationLoaded('reviewedBy') ? $document->reviewedBy?->name : null,
            'reviewed_at' => $document->reviewed_at,
            'created_at' => $document->created_at,
        ];
    }
}
