<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Services\Commerce\CartException;
use App\Services\Commerce\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Customer-facing return requests (spec §12).
 */
class OrderReturnController extends Controller
{
    public function __construct(
        protected ReturnService $returnService,
    ) {}

    /**
     * The signed-in customer's own return requests.
     */
    public function index(Request $request): JsonResponse
    {
        $returns = OrderReturn::query()
            ->with(['order:id,order_number', 'items'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Returns retrieved successfully.',
            'data' => $returns->getCollection()->map(fn (OrderReturn $return) => $this->presentReturn($return))->values(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    /**
     * Request a return against a delivered order.
     */
    public function store(Request $request, int $orderId): JsonResponse
    {
        $order = Order::query()->findOrFail($orderId);
        $this->authorize('requestReturn', $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::in(OrderReturn::REASONS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $return = $this->returnService->request(
                $order,
                $request->user(),
                $validated['items'],
                $validated['reason'],
                $validated['notes'] ?? null,
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('returns.requested_success'),
            'data' => $this->presentReturn($return->load('items')),
        ], 201);
    }

    public function show(Request $request, int $returnId): JsonResponse
    {
        $return = OrderReturn::query()->with(['order:id,order_number', 'items'])->findOrFail($returnId);
        $this->authorize('view', $return);

        return response()->json([
            'message' => 'Return retrieved successfully.',
            'data' => $this->presentReturn($return),
        ]);
    }

    public function cancel(Request $request, int $returnId): JsonResponse
    {
        $return = OrderReturn::query()->findOrFail($returnId);
        $this->authorize('cancel', $return);

        try {
            $return = $this->returnService->transition(
                $return,
                OrderReturn::STATUS_CANCELLED,
                $request->user(),
                'customer',
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('returns.cancelled_success'),
            'data' => ['id' => $return->id, 'status' => $return->status],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentReturn(OrderReturn $return): array
    {
        return [
            'id' => $return->id,
            'return_number' => $return->return_number,
            'order_id' => $return->order_id,
            'order_number' => $return->relationLoaded('order') ? $return->order?->order_number : null,
            'status' => $return->status,
            'status_name' => __("returns.status.{$return->status}"),
            'reason' => $return->reason,
            'reason_name' => __("returns.reason.{$return->reason}"),
            'notes' => $return->notes,
            'refundable_amount' => $return->refundable_amount,
            'requested_at' => $return->requested_at,
            'reviewed_at' => $return->reviewed_at,
            'review_notes' => $return->review_notes,
            'items' => $return->relationLoaded('items')
                ? $return->items->map(fn (ReturnItem $item) => [
                    'id' => $item->id,
                    'order_item_id' => $item->order_item_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ])->values()
                : [],
        ];
    }
}
