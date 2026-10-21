<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportShipments\StoreImportShipmentRequest;
use App\Http\Requests\ImportShipments\UpdateImportShipmentRequest;
use App\Models\Category;
use App\Models\ImportShipment;
use App\Models\ImportShipmentItem;
use App\Models\Port;
use App\Models\Syndicate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Import shipments (الشحنات) CRUD, filters and statistics. Mounted under the
 * admin, employee and syndicate API groups; a syndicate user is scoped to its
 * own syndicate's shipments via ImportShipment::scopeVisibleTo().
 */
class ImportShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $shipments = $this->filteredQuery($request)
            ->with(['port:id,name', 'syndicate:id,name'])
            ->withCount('items')
            ->withSum('items', 'total_price')
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'message' => __('import_shipments.retrieved'),
            'data' => $shipments->items(),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $shipmentIds = $this->filteredQuery($request)->select('import_shipments.id');

        $totals = $this->filteredQuery($request)
            ->selectRaw('COUNT(*) as shipments_count')
            ->selectRaw('COALESCE(SUM(stickers_count), 0) as stickers_count')
            ->selectRaw('COALESCE(SUM(stickers_count * sticker_price), 0) as stickers_total')
            ->selectRaw('SUM(CASE WHEN clearance_number IS NOT NULL AND clearance_number <> \'\' THEN 1 ELSE 0 END) as cleared_count')
            ->selectRaw('SUM(CASE WHEN release_decision IS NOT NULL AND release_decision <> \'\' THEN 1 ELSE 0 END) as released_count')
            ->toBase()
            ->first();

        $itemTotals = ImportShipmentItem::query()
            ->whereIn('import_shipment_id', $shipmentIds)
            ->selectRaw('COUNT(*) as items_count')
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(total_price), 0) as total_value')
            ->toBase()
            ->first();

        $byPort = $this->filteredQuery($request)
            ->join('ports', 'ports.id', '=', 'import_shipments.port_id')
            ->groupBy('ports.id', 'ports.name')
            ->selectRaw('ports.id as port_id, ports.name as name, COUNT(*) as shipments_count, COALESCE(SUM(stickers_count * sticker_price), 0) as stickers_total')
            ->orderByDesc('shipments_count')
            ->toBase()
            ->get();

        $byCountry = ImportShipmentItem::query()
            ->whereIn('import_shipment_id', $shipmentIds)
            ->groupBy('country')
            ->selectRaw('country, COUNT(*) as items_count, COALESCE(SUM(quantity), 0) as total_quantity, COALESCE(SUM(total_price), 0) as total_value')
            ->orderByDesc('total_value')
            ->limit(10)
            ->toBase()
            ->get();

        $byStickerPrice = $this->filteredQuery($request)
            ->groupBy('sticker_price')
            ->selectRaw('sticker_price, COUNT(*) as shipments_count, COALESCE(SUM(stickers_count), 0) as stickers_count')
            ->orderBy('sticker_price')
            ->toBase()
            ->get();

        return response()->json([
            'message' => __('import_shipments.stats_retrieved'),
            'data' => [
                'totals' => [
                    'shipments_count' => (int) ($totals->shipments_count ?? 0),
                    'stickers_count' => (int) ($totals->stickers_count ?? 0),
                    'stickers_total' => (int) ($totals->stickers_total ?? 0),
                    'cleared_count' => (int) ($totals->cleared_count ?? 0),
                    'released_count' => (int) ($totals->released_count ?? 0),
                    'items_count' => (int) ($itemTotals->items_count ?? 0),
                    'total_quantity' => (int) ($itemTotals->total_quantity ?? 0),
                    'total_value' => round((float) ($itemTotals->total_value ?? 0), 2),
                ],
                'by_port' => $byPort->map(fn ($row) => [
                    'port_id' => (int) $row->port_id,
                    'name' => $row->name,
                    'shipments_count' => (int) $row->shipments_count,
                    'stickers_total' => (int) $row->stickers_total,
                ])->values(),
                'by_country' => $byCountry->map(fn ($row) => [
                    'country' => $row->country,
                    'items_count' => (int) $row->items_count,
                    'total_quantity' => (int) $row->total_quantity,
                    'total_value' => round((float) $row->total_value, 2),
                ])->values(),
                'by_sticker_price' => $byStickerPrice->map(fn ($row) => [
                    'sticker_price' => (int) $row->sticker_price,
                    'shipments_count' => (int) $row->shipments_count,
                    'stickers_count' => (int) $row->stickers_count,
                ])->values(),
                'monthly' => $this->monthlyCounts($request),
            ],
        ]);
    }

    /**
     * Select options for the shipment form and filters. Syndicate users never
     * pick a syndicate (their own is assigned), so they get no syndicate list.
     */
    public function options(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'message' => __('import_shipments.ports_retrieved'),
            'data' => [
                'ports' => Port::query()->active()->orderBy('name')->get(['id', 'name']),
                'syndicates' => $user->isSyndicate()
                    ? []
                    : Syndicate::query()->orderBy('name')->get(['id', 'name']),
                'sticker_prices' => ImportShipment::STICKER_PRICES,
                'can_pick_syndicate' => ! $user->isSyndicate(),
            ],
        ]);
    }

    public function store(StoreImportShipmentRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $shipment = DB::transaction(function () use ($validated, $user): ImportShipment {
            $shipment = ImportShipment::query()->create([
                ...$this->shipmentAttributes($validated, $user),
                'created_by' => $user->id,
            ]);
            $shipment->items()->createMany($validated['items']);

            return $shipment;
        });

        return response()->json([
            'message' => __('import_shipments.created'),
            'data' => $this->loadDetail($shipment),
        ], 201);
    }

    public function show(Request $request, int $importShipment): JsonResponse
    {
        $shipment = $this->findVisible($request, $importShipment);

        return response()->json([
            'message' => __('import_shipments.retrieved_one'),
            'data' => $this->loadDetail($shipment),
        ]);
    }

    public function update(UpdateImportShipmentRequest $request, int $importShipment): JsonResponse
    {
        $shipment = $this->findVisible($request, $importShipment);
        $validated = $request->validated();

        DB::transaction(function () use ($shipment, $validated, $request): void {
            $shipment->update($this->shipmentAttributes($validated, $request->user(), $shipment));
            $shipment->items()->delete();
            $shipment->items()->createMany($validated['items']);
        });

        return response()->json([
            'message' => __('import_shipments.updated'),
            'data' => $this->loadDetail($shipment->fresh()),
        ]);
    }

    public function destroy(Request $request, int $importShipment): JsonResponse
    {
        $shipment = $this->findVisible($request, $importShipment);

        DB::transaction(fn () => $shipment->delete());

        return response()->json([
            'message' => __('import_shipments.deleted'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function shipmentAttributes(array $validated, User $user, ?ImportShipment $existing = null): array
    {
        $attributes = collect($validated)->except(['items', 'syndicate_id'])->all();

        $attributes['syndicate_id'] = $user->isSyndicate()
            ? $user->syndicate?->id
            : ($validated['syndicate_id'] ?? null);

        if ($existing && $user->isSyndicate()) {
            $attributes['syndicate_id'] = $existing->syndicate_id;
        }

        return $attributes;
    }

    private function findVisible(Request $request, int $id): ImportShipment
    {
        return ImportShipment::query()->visibleTo($request->user())->findOrFail($id);
    }

    private function loadDetail(ImportShipment $shipment): ImportShipment
    {
        return $shipment->load(['port:id,name', 'syndicate:id,name', 'creator:id,name', 'items']);
    }

    /**
     * @return Builder<ImportShipment>
     */
    private function filteredQuery(Request $request): Builder
    {
        $search = trim((string) $request->input('search'));
        $country = trim((string) $request->input('country'));

        return ImportShipment::query()
            ->visibleTo($request->user())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('merchant_name', 'like', $like)
                        ->orWhere('merchant_register_number', 'like', $like)
                        ->orWhere('invoice_number', 'like', $like)
                        ->orWhere('clearance_number', 'like', $like);
                });
            })
            ->when($request->filled('port_id'), fn (Builder $query) => $query->where('import_shipments.port_id', (int) $request->input('port_id')))
            ->when($request->filled('syndicate_id') && ! $request->user()->isSyndicate(), fn (Builder $query) => $query->where('import_shipments.syndicate_id', (int) $request->input('syndicate_id')))
            ->when($request->user()->isMinistry() && in_array($request->input('sector'), [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], true), fn (Builder $query) => $query->whereHas('syndicate', fn (Builder $syndicate) => $syndicate->where('type', $request->input('sector'))))
            ->when($request->filled('sticker_price'), fn (Builder $query) => $query->where('sticker_price', (int) $request->input('sticker_price')))
            ->when($country !== '', fn (Builder $query) => $query->whereHas('items', fn (Builder $items) => $items->where('country', 'like', '%'.$country.'%')))
            ->when($request->input('released') === '1', fn (Builder $query) => $query->whereNotNull('release_decision')->where('release_decision', '<>', ''))
            ->when($request->input('released') === '0', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner->whereNull('release_decision')->orWhere('release_decision', '')))
            ->when($this->validDate($request->input('date_from')), fn (Builder $query, Carbon $from) => $query->where('import_shipments.created_at', '>=', $from->startOfDay()))
            ->when($this->validDate($request->input('date_to')), fn (Builder $query, Carbon $to) => $query->where('import_shipments.created_at', '<=', $to->endOfDay()));
    }

    private function validDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Shipment counts for the last 12 months, grouped in PHP so the query stays
     * portable across MySQL and the SQLite test database.
     *
     * @return list<array{month: string, shipments_count: int}>
     */
    private function monthlyCounts(Request $request): array
    {
        $start = now()->startOfMonth()->subMonths(11);

        $counts = $this->filteredQuery($request)
            ->where('import_shipments.created_at', '>=', $start)
            ->pluck('import_shipments.created_at')
            ->countBy(fn ($createdAt) => Carbon::parse($createdAt)->format('Y-m'));

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i)->format('Y-m');
            $months[] = ['month' => $month, 'shipments_count' => (int) ($counts[$month] ?? 0)];
        }

        return $months;
    }
}
