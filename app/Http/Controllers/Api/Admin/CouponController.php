<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query()->with('creator:id,name');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $coupons = $query->latest()->paginate(15);

        return response()->json([
            'message' => __('Coupons retrieved successfully.'),
            'data' => $coupons->items(),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCouponRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['code'] = strtoupper(trim((string) $validated['code']));
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['status'] = Coupon::resolveStatus(
            $validated['is_active'],
            $validated['starts_at'] ?? null,
            $validated['ends_at'] ?? null,
        );
        $validated['created_by_user_id'] = $request->user()?->id;

        $coupon = Coupon::query()->create($validated);

        return response()->json([
            'message' => __('Coupon created successfully.'),
            'data' => $coupon->load('creator:id,name'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Coupon $coupon): JsonResponse
    {
        return response()->json([
            'message' => __('Coupon retrieved successfully.'),
            'data' => $coupon->load('creator:id,name'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validated();

        if (array_key_exists('code', $validated)) {
            $code = strtoupper(trim((string) $validated['code']));
            $request->validate([
                'code' => ['required', 'string', 'max:60', Rule::unique('coupons', 'code')->ignore($coupon->id)],
            ]);
            $validated['code'] = $code;
        }

        $isEnabled = (bool) ($validated['is_active'] ?? $coupon->is_active);
        $validated['status'] = Coupon::resolveStatus(
            $isEnabled,
            $validated['starts_at'] ?? optional($coupon->starts_at)->toDateTimeString(),
            $validated['ends_at'] ?? optional($coupon->ends_at)->toDateTimeString(),
        );

        $coupon->update($validated);

        return response()->json([
            'message' => __('Coupon updated successfully.'),
            'data' => $coupon->fresh()->load('creator:id,name'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json([
            'message' => __('Coupon deleted successfully.'),
        ]);
    }
}
