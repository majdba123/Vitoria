<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorSettlement;
use App\Services\Commerce\CartException;
use App\Services\Commerce\VendorLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettlementController extends Controller
{
    public function __construct(
        private readonly VendorLedgerService $vendorLedgerService,
    ) {}

    public function index(Vendor $vendor): JsonResponse
    {
        $settlements = VendorSettlement::query()
            ->where('vendor_id', $vendor->id)
            ->latest('settled_at')
            ->paginate(20);

        return response()->json([
            'message' => 'Settlements retrieved successfully.',
            'data' => $settlements->items(),
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'last_page' => $settlements->lastPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
            ],
        ]);
    }

    public function store(Request $request, Vendor $vendor): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', Rule::in(VendorSettlement::METHODS)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $settlement = $this->vendorLedgerService->recordSettlement(
                $vendor,
                $request->user(),
                (float) $validated['amount'],
                $validated['method'],
                $validated['reference'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('vendor_ledger.settlement_recorded'),
            'data' => ['id' => $settlement->id, 'amount' => $settlement->amount, 'method' => $settlement->method],
        ], 201);
    }
}
