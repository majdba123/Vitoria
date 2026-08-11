<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Commerce\CartException;
use App\Services\Commerce\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin settlement of refunds (spec §13).
 *
 * Completing or cancelling a refund is admin-only (RefundPolicy::manage) —
 * a vendor can request one via ReturnController::refund, but only an admin
 * actually moves money.
 */
class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Refund::query()
            ->with(['order:id,order_number,vendor_id', 'orderReturn:id,return_number'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', (int) $request->input('order_id'));
        }

        $refunds = $query->paginate(12);

        return response()->json([
            'message' => 'Refunds retrieved successfully.',
            'data' => $refunds->items(),
            'meta' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'per_page' => $refunds->perPage(),
                'total' => $refunds->total(),
            ],
        ]);
    }

    public function show(int $refundId): JsonResponse
    {
        $refund = Refund::query()
            ->with(['order:id,order_number,vendor_id', 'orderReturn:id,return_number', 'payment'])
            ->findOrFail($refundId);

        return response()->json(['message' => 'Refund retrieved successfully.', 'data' => $refund]);
    }

    /**
     * Ad-hoc refund with no return behind it — a duplicate COD collection or
     * a goodwill adjustment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', Rule::in(Refund::ADHOC_REASONS)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::query()->findOrFail($validated['order_id']);

        try {
            $refund = $this->refundService->initiateAdHoc(
                $order,
                $request->user(),
                (float) $validated['amount'],
                $validated['reason'],
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

    public function complete(Request $request, int $refundId): JsonResponse
    {
        $validated = $request->validate([
            'provider_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $refund = Refund::query()->findOrFail($refundId);

        try {
            $refund = $this->refundService->complete($refund, $validated['provider_reference'] ?? null);
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('refunds.completed_success'),
            'data' => ['id' => $refund->id, 'status' => $refund->status, 'completed_at' => $refund->completed_at],
        ]);
    }

    public function cancel(int $refundId): JsonResponse
    {
        $refund = Refund::query()->findOrFail($refundId);

        try {
            $refund = $this->refundService->cancel($refund);
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('refunds.cancelled_success'),
            'data' => ['id' => $refund->id, 'status' => $refund->status],
        ]);
    }
}
