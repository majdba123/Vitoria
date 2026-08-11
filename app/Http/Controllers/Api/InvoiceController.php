<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer-facing invoices (spec §19).
 */
class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with('order:id,order_number')
            ->where('user_id', $request->user()->id)
            ->latest('issued_at')
            ->paginate(10);

        return response()->json([
            'message' => 'Invoices retrieved successfully.',
            'data' => $invoices->getCollection()->map(fn (Invoice $invoice) => $this->present($invoice))->values(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function show(int $invoiceId): JsonResponse
    {
        $invoice = Invoice::query()->with('order:id,order_number')->findOrFail($invoiceId);
        $this->authorize('view', $invoice);

        return response()->json(['message' => 'Invoice retrieved successfully.', 'data' => $this->present($invoice)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'order_id' => $invoice->order_id,
            'order_number' => $invoice->relationLoaded('order') ? $invoice->order?->order_number : null,
            'subtotal_amount' => $invoice->subtotal_amount,
            'discount_total' => $invoice->discount_total,
            'shipping_total' => $invoice->shipping_total,
            'tax_total' => $invoice->tax_total,
            'grand_total' => $invoice->grand_total,
            'currency' => $invoice->currency,
            'payment_method' => $invoice->payment_method,
            'issued_at' => $invoice->issued_at,
            'print_url' => route('invoices.print', $invoice->id),
        ];
    }
}
