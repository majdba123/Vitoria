<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Services\Commerce\VendorLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LedgerController extends Controller
{
    public function __construct(
        private readonly VendorLedgerService $vendorLedgerService,
    ) {}

    public function index(Vendor $vendor): JsonResponse
    {
        $entries = VendorLedgerEntry::query()
            ->with('order:id,order_number')
            ->where('vendor_id', $vendor->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Ledger entries retrieved successfully.',
            'data' => $entries->items(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    public function summary(Vendor $vendor): JsonResponse
    {
        return response()->json([
            'message' => 'Ledger summary retrieved successfully.',
            'data' => $this->vendorLedgerService->summary($vendor),
        ]);
    }

    public function adjust(Request $request, Vendor $vendor): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'direction' => ['required', 'string', Rule::in(['credit', 'debit'])],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $entry = $this->vendorLedgerService->recordAdjustment(
            $vendor,
            $request->user(),
            (float) $validated['amount'],
            $validated['direction'],
            $validated['description'],
        );

        return response()->json([
            'message' => __('vendor_ledger.adjustment_recorded'),
            'data' => ['id' => $entry->id, 'direction' => $entry->direction, 'amount' => $entry->amount],
        ], 201);
    }
}
