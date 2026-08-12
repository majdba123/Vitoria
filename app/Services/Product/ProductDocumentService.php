<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Official product documents (spec §25) — leaflets, labels, safety data
 * sheets, registration certificates, manufacturer documents.
 *
 * Files live on the private `local` disk, exactly like vendor_documents
 * (spec §55) — but unlike a vendor document, an *approved* product document
 * is meant to be downloadable by any visitor to the product page. That is
 * handled by a public download action that streams only when
 * `status === approved` (see Api\ProductDocumentController), not by putting
 * the file on the public disk: a pending or rejected document must stay
 * unreachable even if someone guesses its id.
 */
class ProductDocumentService
{
    private const DISK = 'local';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @throws ProductDocumentException
     */
    public function upload(
        Product $product,
        User $actor,
        string $type,
        UploadedFile $file,
        string $title,
        string $language,
        ?string $issuedAt = null,
        ?string $expiresAt = null,
    ): ProductDocument {
        if (! in_array($type, ProductDocument::TYPES, true)) {
            throw new ProductDocumentException(__('product_documents.type_invalid'));
        }

        $path = Storage::disk(self::DISK)->putFile('product-documents', $file);

        try {
            $document = ProductDocument::create([
                'product_id' => $product->id,
                'vendor_id' => $product->vendor_id,
                'type' => $type,
                'title' => $title,
                'language' => $language,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'source' => $actor->isAdmin() || $actor->isEmployee() ? ProductDocument::SOURCE_ADMIN : ProductDocument::SOURCE_VENDOR,
                'status' => ProductDocument::STATUS_PENDING_REVIEW,
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'created_by_user_id' => $actor->id,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);

            throw $exception;
        }

        $this->notificationService->notifyProductDocumentSubmitted($document);

        return $document;
    }

    /**
     * @throws ProductDocumentException
     */
    public function review(ProductDocument $document, User $admin, string $status, ?string $rejectionReason = null): ProductDocument
    {
        if (! in_array($status, [ProductDocument::STATUS_APPROVED, ProductDocument::STATUS_REJECTED], true)) {
            throw new ProductDocumentException(__('product_documents.status_invalid'));
        }

        if ($status === ProductDocument::STATUS_REJECTED && ! $rejectionReason) {
            throw new ProductDocumentException(__('product_documents.rejection_reason_required'));
        }

        if (! $document->canTransitionTo($status)) {
            throw new ProductDocumentException(__('product_documents.transition_invalid'));
        }

        $applied = ProductDocument::query()
            ->whereKey($document->id)
            ->where('status', ProductDocument::STATUS_PENDING_REVIEW)
            ->update([
                'status' => $status,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $status === ProductDocument::STATUS_REJECTED ? $rejectionReason : null,
                'updated_at' => now(),
            ]);

        if ($applied === 0) {
            throw new ProductDocumentException(__('product_documents.transition_conflict'));
        }

        $document = $document->refresh();
        $this->notificationService->notifyProductDocumentReviewed($document);

        return $document;
    }

    /**
     * @throws ProductDocumentException
     */
    public function disable(ProductDocument $document): ProductDocument
    {
        if (! $document->canTransitionTo(ProductDocument::STATUS_DISABLED)) {
            throw new ProductDocumentException(__('product_documents.transition_invalid'));
        }

        $applied = ProductDocument::query()
            ->whereKey($document->id)
            ->where('status', ProductDocument::STATUS_APPROVED)
            ->update(['status' => ProductDocument::STATUS_DISABLED, 'updated_at' => now()]);

        if ($applied === 0) {
            throw new ProductDocumentException(__('product_documents.transition_conflict'));
        }

        return $document->refresh();
    }
}
