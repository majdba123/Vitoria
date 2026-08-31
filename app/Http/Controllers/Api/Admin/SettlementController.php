<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorSettlementRequest;
use App\Models\Vendor;
use App\Models\VendorSettlement;
use App\Services\Commerce\CartException;
use App\Services\Commerce\VendorLedgerService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

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
            'message' => __('api.settlements_retrieved'),
            'data' => $settlements->items(),
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'last_page' => $settlements->lastPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
            ],
        ]);
    }

    public function store(StoreVendorSettlementRequest $request, Vendor $vendor): JsonResponse
    {
        $validated = $request->validated();

        try {
            $settlement = $this->vendorLedgerService->recordSettlement(
                $vendor,
                $request->user(),
                (float) $validated['amount'],
                $validated['method'],
                $validated['reference'] ?? null,
                $validated['notes'] ?? null,
                isset($validated['payment_date']) ? CarbonImmutable::parse($validated['payment_date'])->startOfDay() : CarbonImmutable::now(),
                $validated['idempotency_key'] ?? (string) Str::uuid(),
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('vendor_ledger.settlement_recorded'),
            'data' => [
                'id' => $settlement->id,
                'amount' => $settlement->amount,
                'method' => $settlement->method,
                'settled_at' => $settlement->settled_at,
            ],
        ], 201);
    }
}
