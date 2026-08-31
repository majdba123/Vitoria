<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->with(['order:id,order_number', 'vendor:id,store_name'])
            ->latest('issued_at');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->input('vendor_id'));
        }

        $invoices = $query->paginate(12);

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
        $invoice = Invoice::query()->with(['order:id,order_number', 'vendor:id,store_name'])->findOrFail($invoiceId);

        return response()->json(['message' => __('api.invoice_retrieved'), 'data' => $invoice]);
    }
}
