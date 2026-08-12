<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Import\CsvFile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV exports for admin data (spec §37).
 *
 * Reuses the CsvFile streaming helper already built for the CSV import
 * template downloads (App\Services\Import\CsvFile) rather than adding a new
 * export library — same UTF-8 BOM handling, same streamed-download shape.
 */
class ExportController extends Controller
{
    private const MAX_ROWS = 5000;

    public function orders(Request $request): StreamedResponse
    {
        $query = Order::query()->with(['user:id,name,email', 'vendor:id,store_name'])->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->input('vendor_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $headers = ['Order Number', 'Customer', 'Email', 'Vendor', 'Status', 'Grand Total', 'Currency', 'Placed At'];

        $rows = $query->limit(self::MAX_ROWS)->get()->map(fn (Order $order) => [
            $order->order_number,
            $order->user?->name,
            $order->user?->email,
            $order->vendor?->store_name,
            $order->status,
            $order->grand_total,
            $order->currency,
            $order->created_at?->toDateTimeString(),
        ])->all();

        return CsvFile::download('orders_export_'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }

    public function products(Request $request): StreamedResponse
    {
        $query = Product::query()->with(['vendor:id,store_name', 'category:id,name'])->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->input('vendor_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        $headers = ['Name', 'Vendor', 'Category', 'Price', 'Quantity', 'Status', 'Active', 'Created At'];

        $rows = $query->limit(self::MAX_ROWS)->get()->map(fn (Product $product) => [
            $product->name,
            $product->vendor?->store_name,
            $product->category?->name,
            $product->price,
            $product->quantity,
            $product->status,
            $product->is_active ? 'yes' : 'no',
            $product->created_at?->toDateTimeString(),
        ])->all();

        return CsvFile::download('products_export_'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }

    public function vendors(Request $request): StreamedResponse
    {
        $query = Vendor::query()->with('user:id,name,email')->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('business_type')) {
            $query->where('business_type', (string) $request->input('business_type'));
        }

        $headers = ['Store Name', 'Owner', 'Email', 'Business Type', 'Status', 'Active', 'Created At'];

        $rows = $query->limit(self::MAX_ROWS)->get()->map(fn (Vendor $vendor) => [
            $vendor->store_name,
            $vendor->user?->name,
            $vendor->user?->email,
            $vendor->business_type,
            $vendor->status,
            $vendor->is_active ? 'yes' : 'no',
            $vendor->created_at?->toDateTimeString(),
        ])->all();

        return CsvFile::download('vendors_export_'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }
}
