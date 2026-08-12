<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorLedgerEntry;
use App\Services\Commerce\VendorLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The vendor's own financial ledger (spec §20).
 */
class LedgerController extends Controller
{
    public function __construct(
        private readonly VendorLedgerService $vendorLedgerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()?->managedVendor();
        if (! $vendor) {
            abort(403, 'Vendor profile not found.');
        }
        if (! $request->user()->hasVendorPermission($vendor, 'ledger.view')) {
            abort(403, __('You are not allowed to view this vendor\'s ledger.'));
        }

        $entries = VendorLedgerEntry::query()
            ->with('order:id,order_number')
            ->where('vendor_id', $vendor->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Ledger entries retrieved successfully.',
            'data' => $entries->getCollection()->map(fn (VendorLedgerEntry $entry) => $this->present($entry))->values(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $vendor = $request->user()?->managedVendor();
        if (! $vendor) {
            abort(403, 'Vendor profile not found.');
        }
        if (! $request->user()->hasVendorPermission($vendor, 'ledger.view')) {
            abort(403, __('You are not allowed to view this vendor\'s ledger.'));
        }

        return response()->json([
            'message' => 'Ledger summary retrieved successfully.',
            'data' => $this->vendorLedgerService->summary($vendor),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(VendorLedgerEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'order_id' => $entry->order_id,
            'order_number' => $entry->relationLoaded('order') ? $entry->order?->order_number : null,
            'type' => $entry->type,
            'type_name' => __("vendor_ledger.type.{$entry->type}"),
            'direction' => $entry->direction,
            'amount' => $entry->amount,
            'description' => $entry->description,
            'created_at' => $entry->created_at,
        ];
    }
}
