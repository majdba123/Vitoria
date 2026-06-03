<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSyndicateRequest;
use App\Http\Requests\Admin\UpdateSyndicateRequest;
use App\Http\Resources\Admin\SyndicateResource;
use App\Models\Category;
use App\Models\Syndicate;
use App\Services\Admin\SyndicateService;
use App\Services\Syndicate\SyndicateDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyndicateController extends Controller
{
    public function __construct(
        public SyndicateService $syndicateService,
        public SyndicateDashboardService $dashboardService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $syndicates = Syndicate::query()
            ->with('user:id,name,email,phone_number,type')
            ->when($request->filled('type'), fn ($query) => $query->where('type', (string) $request->input('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($perPage);

        $this->attachTypeCounts($syndicates->getCollection());

        return response()->json([
            'message' => __('Syndicates retrieved successfully.'),
            'data' => SyndicateResource::collection($syndicates),
            'meta' => [
                'current_page' => $syndicates->currentPage(),
                'last_page' => $syndicates->lastPage(),
                'per_page' => $syndicates->perPage(),
                'total' => $syndicates->total(),
            ],
        ]);
    }

    public function store(StoreSyndicateRequest $request): JsonResponse
    {
        $syndicate = $this->syndicateService->create($request->validated());

        return response()->json([
            'message' => __('Syndicate created successfully.'),
            'data' => new SyndicateResource($syndicate),
        ], 201);
    }

    public function show(Syndicate $syndicate): JsonResponse
    {
        $syndicate->load('user:id,name,email,phone_number,type');
        $this->attachTypeCounts(collect([$syndicate]));

        return response()->json([
            'message' => __('Syndicate retrieved successfully.'),
            'data' => new SyndicateResource($syndicate),
        ]);
    }

    public function update(UpdateSyndicateRequest $request, Syndicate $syndicate): JsonResponse
    {
        $syndicate = $this->syndicateService->update($syndicate, $request->validated());

        return response()->json([
            'message' => __('Syndicate updated successfully.'),
            'data' => new SyndicateResource($syndicate),
        ]);
    }

    public function toggleActive(Syndicate $syndicate): JsonResponse
    {
        $syndicate = $this->syndicateService->toggleActive($syndicate);

        return response()->json([
            'message' => $syndicate->isActive()
                ? __('Syndicate activated successfully.')
                : __('Syndicate deactivated successfully.'),
            'data' => new SyndicateResource($syndicate),
        ]);
    }

    public function destroy(Syndicate $syndicate): JsonResponse
    {
        $this->syndicateService->delete($syndicate);

        return response()->json([
            'message' => __('Syndicate deleted successfully.'),
        ]);
    }

    protected function attachTypeCounts(\Illuminate\Support\Collection $syndicates): void
    {
        if ($syndicates->isEmpty()) {
            return;
        }

        $categoryCounts = Category::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $countsByType = collect(Category::typeLabels())->keys()->mapWithKeys(fn (string $type) => [
            $type => [
                'categories_count' => (int) ($categoryCounts[$type] ?? 0),
                'vendors_count' => $this->dashboardService->vendorQuery($type)->count(),
                'products_count' => $this->dashboardService->productQuery($type)->count(),
                'orders_count' => $this->dashboardService->orderQuery($type)->count(),
            ],
        ]);

        $syndicates->each(function (Syndicate $syndicate) use ($countsByType): void {
            foreach ($countsByType[$syndicate->type] ?? [] as $key => $value) {
                $syndicate->setAttribute($key, $value);
            }
        });
    }
}
