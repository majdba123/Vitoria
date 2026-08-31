<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Services\Commerce\CartException;
use App\Services\Commerce\RefundService;
use App\Services\Commerce\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin oversight of return requests across all vendors (spec §12).
 */
class ReturnController extends Controller
{
    public function __construct(
        protected ReturnService $returnService,
        protected RefundService $refundService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = OrderReturn::query()
            ->with(['order:id,order_number', 'user:id,name,email', 'vendor:id,store_name', 'items'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->input('vendor_id'));
        }

        $returns = $query->paginate(12);

        return response()->json([
            'message' => __('api.returns_retrieved'),
            'data' => $returns->items(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    public function show(int $returnId): JsonResponse
    {
        $return = OrderReturn::query()
            ->with(['order:id,order_number', 'user:id,name,email', 'vendor:id,store_name', 'items', 'refund'])
            ->findOrFail($returnId);

        return response()->json(['message' => __('api.return_retrieved'), 'data' => $return]);
    }

    public function updateStatus(Request $request, int $returnId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(OrderReturn::TRANSITIONS))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $return = OrderReturn::query()->findOrFail($returnId);

        try {
            $return = $this->returnService->transition(
                $return,
                $validated['status'],
                $request->user(),
                'admin',
                $validated['notes'] ?? null,
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('returns.transition_success'),
            'data' => ['id' => $return->id, 'status' => $return->status],
        ]);
    }

    public function refund(Request $request, int $returnId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $return = OrderReturn::query()->findOrFail($returnId);

        try {
            $refund = $this->refundService->initiate(
                $return,
                $request->user(),
                isset($validated['amount']) ? (float) $validated['amount'] : null,
                $validated['notes'] ?? null,
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('refunds.initiated_success'),
            'data' => ['id' => $refund->id, 'refund_number' => $refund->refund_number, 'status' => $refund->status, 'amount' => $refund->amount],
        ], 201);
    }
}
