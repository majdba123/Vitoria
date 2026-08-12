<?php

namespace App\Services\Vendor;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Vendor compliance document review (spec §24).
 *
 * Files are never public: stored on the private `local` disk (the same one
 * `commercial_register_file` already used), under a server-generated name
 * (`Storage::putFile()` — the original filename is kept only as a display
 * label, never used as the storage path), and only ever reachable through an
 * authorized, streamed download (spec §55).
 */
class VendorDocumentService
{
    private const DISK = 'local';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Upload or resubmit a document. One row per (vendor, type): resubmitting
     * replaces the file and resets review state rather than accumulating
     * history, since only the current document is ever meaningful to a
     * reviewer.
     *
     * @throws VendorStaffException
     */
    public function upload(
        Vendor $vendor,
        string $type,
        UploadedFile $file,
        ?string $title = null,
        ?string $issuedAt = null,
        ?string $expiresAt = null,
    ): VendorDocument {
        if (! in_array($type, VendorDocument::TYPES, true)) {
            throw new VendorStaffException(__('vendor_documents.type_invalid'));
        }

        $path = Storage::disk(self::DISK)->putFile('vendor-documents', $file);

        try {
            $document = DB::transaction(function () use ($expiresAt, $file, $issuedAt, $path, $title, $type, $vendor) {
                $existing = VendorDocument::query()
                    ->where('vendor_id', $vendor->id)
                    ->where('type', $type)
                    ->first();

                $attributes = [
                    'title' => $title,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'issued_at' => $issuedAt,
                    'expires_at' => $expiresAt,
                    'status' => VendorDocument::STATUS_PENDING_REVIEW,
                    'reviewed_by_user_id' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ];

                if ($existing) {
                    $oldPath = $existing->file_path;
                    $existing->update($attributes);
                    Storage::disk(self::DISK)->delete($oldPath);
                    $document = $existing;
                } else {
                    $document = VendorDocument::create(array_merge($attributes, [
                        'vendor_id' => $vendor->id,
                        'type' => $type,
                    ]));
                }

                return $document;
            });
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);

            throw $exception;
        }

        $this->notificationService->notifyVendorDocumentSubmitted($vendor);

        return $document;
    }

    /**
     * @throws VendorStaffException
     */
    public function review(VendorDocument $document, User $admin, string $status, ?string $rejectionReason = null): VendorDocument
    {
        if (! in_array($status, [VendorDocument::STATUS_VERIFIED, VendorDocument::STATUS_REJECTED], true)) {
            throw new VendorStaffException(__('vendor_documents.status_invalid'));
        }

        if ($status === VendorDocument::STATUS_REJECTED && ! $rejectionReason) {
            throw new VendorStaffException(__('vendor_documents.rejection_reason_required'));
        }

        if (! $document->canTransitionTo($status)) {
            throw new VendorStaffException(__('vendor_documents.transition_invalid'));
        }

        $applied = VendorDocument::query()
            ->whereKey($document->id)
            ->where('status', VendorDocument::STATUS_PENDING_REVIEW)
            ->update([
                'status' => $status,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $status === VendorDocument::STATUS_REJECTED ? $rejectionReason : null,
                'updated_at' => now(),
            ]);

        if ($applied === 0) {
            throw new VendorStaffException(__('vendor_documents.transition_conflict'));
        }

        $document = $document->refresh();
        $this->notificationService->notifyVendorDocumentReviewed($document);

        return $document;
    }

    /**
     * @throws VendorStaffException
     */
    public function suspend(VendorDocument $document, ?string $reason = null): VendorDocument
    {
        if (! $document->canTransitionTo(VendorDocument::STATUS_SUSPENDED)) {
            throw new VendorStaffException(__('vendor_documents.transition_invalid'));
        }

        $applied = VendorDocument::query()
            ->whereKey($document->id)
            ->where('status', VendorDocument::STATUS_VERIFIED)
            ->update([
                'status' => VendorDocument::STATUS_SUSPENDED,
                'rejection_reason' => $reason,
                'updated_at' => now(),
            ]);

        if ($applied === 0) {
            throw new VendorStaffException(__('vendor_documents.transition_conflict'));
        }

        return $document->refresh();
    }

    /**
     * Flip verified documents whose `expires_at` has passed. Run lazily at
     * the top of the admin review queue rather than via a scheduled job —
     * this repository has no configured scheduler, and the queue is the
     * only place an accurate "expired" status is actually needed.
     */
    public function expireOverdue(): int
    {
        return VendorDocument::query()
            ->where('status', VendorDocument::STATUS_VERIFIED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->toDateString())
            ->update(['status' => VendorDocument::STATUS_EXPIRED, 'updated_at' => now()]);
    }
}
