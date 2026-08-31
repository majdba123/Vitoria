<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorDocument;
use App\Services\Vendor\VendorDocumentService;
use App\Services\Vendor\VendorStaffException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * The vendor's own compliance documents (spec §24). Any active staff member
 * may view the list — knowing what's outstanding isn't sensitive — but only
 * `documents.manage` (Owner/Manager) may upload.
 */
class DocumentController extends Controller
{
    public function __construct(
        private readonly VendorDocumentService $vendorDocumentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()?->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }

        $documents = VendorDocument::query()
            ->where('vendor_id', $vendor->id)
            ->get()
            ->map(fn (VendorDocument $document) => $this->present($document))
            ->values();

        return response()->json([
            'message' => __('api.documents_retrieved'),
            'data' => $documents,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = $user?->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }
        if (! $user->hasVendorPermission($vendor, 'documents.manage')) {
            abort(403, __('You are not allowed to manage documents for this vendor.'));
        }

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(VendorDocument::TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:issued_at'],
        ]);

        try {
            $document = $this->vendorDocumentService->upload(
                $vendor,
                $validated['type'],
                $request->file('file'),
                $validated['title'] ?? null,
                $validated['issued_at'] ?? null,
                $validated['expires_at'] ?? null,
            );
        } catch (VendorStaffException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('vendor_documents.uploaded_success'),
            'data' => $this->present($document),
        ], 201);
    }

    public function show(Request $request, int $documentId): JsonResponse
    {
        $document = VendorDocument::query()->findOrFail($documentId);
        $this->authorize('view', $document);

        return response()->json(['message' => __('api.document_retrieved'), 'data' => $this->present($document)]);
    }

    public function download(Request $request, int $documentId)
    {
        $document = VendorDocument::query()->findOrFail($documentId);
        $this->authorize('view', $document);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, __('vendor_documents.not_found'));
        }

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(VendorDocument $document): array
    {
        return [
            'id' => $document->id,
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
            'reviewed_at' => $document->reviewed_at,
            'created_at' => $document->created_at,
        ];
    }
}
