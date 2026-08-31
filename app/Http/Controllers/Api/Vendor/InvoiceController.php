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
        $vendor = $request->user()?->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }

        $invoices = Invoice::query()
            ->with('order:id,order_number')
            ->where('vendor_id', $vendor->id)
            ->latest('issued_at')
            ->paginate(12);

        return response()->json([
            'message' => __('api.invoices_retrieved'),
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

        return response()->json(['message' => __('api.invoice_retrieved'), 'data' => $invoice]);
    }
}
