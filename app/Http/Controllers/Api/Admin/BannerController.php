<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => __('Banners retrieved successfully.'),
            'data' => Banner::query()->orderBy('sort_order')->orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $validated['image_path'] = $request->file('image')->store('banners', 'public');
        unset($validated['image']);

        $banner = Banner::query()->create($validated);

        $this->auditLogService->record($request->user(), 'banner.created', 'Banner', $banner->id, null, ['image_path' => $banner->image_path]);

        return response()->json([
            'message' => __('Banner created successfully.'),
            'data' => $banner,
        ], 201);
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $validated = $request->validate([
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $before = $banner->only(['is_active', 'sort_order']);

        if ($request->hasFile('image')) {
            $oldPath = $banner->image_path;
            $validated['image_path'] = $request->file('image')->store('banners', 'public');
            unset($validated['image']);
            Storage::disk('public')->delete($oldPath);
        } else {
            unset($validated['image']);
        }

        $banner->update($validated);

        $this->auditLogService->record(
            $request->user(),
            'banner.updated',
            'Banner',
            $banner->id,
            $before,
            $banner->fresh()->only(['is_active', 'sort_order']),
        );

        return response()->json([
            'message' => __('Banner updated successfully.'),
            'data' => $banner->fresh(),
        ]);
    }

    public function destroy(Request $request, Banner $banner): JsonResponse
    {
        Storage::disk('public')->delete($banner->image_path);
        $banner->delete();

        $this->auditLogService->record($request->user(), 'banner.deleted', 'Banner', null, null, null);

        return response()->json([
            'message' => __('Banner deleted successfully.'),
        ]);
    }
}
