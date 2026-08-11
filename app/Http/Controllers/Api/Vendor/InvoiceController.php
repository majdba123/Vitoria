<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()?->vendor;
        if (! $vendor) {
            abort(403, 'Vendor profile not found.');
        }

        $invoices = Invoice::query()
            ->with('order:id,order_number')
            ->where('vendor_id', $vendor->id)
            ->latest('issued_at')
            ->paginate(12);

        return response()->json([
            'message' => 'Invoices retrieved successfully.',
            'data' => $invoices->items(),
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

        return response()->json(['message' => 'Invoice retrieved successfully.', 'data' => $invoice]);
    }
}
