<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => __('Pages retrieved successfully.'),
            'data' => Page::query()->orderBy('title_en')->get(),
        ]);
    }

    public function show(Page $page): JsonResponse
    {
        return response()->json([
            'message' => __('Page retrieved successfully.'),
            'data' => $page,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:pages,slug'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'content_en' => ['required', 'string'],
            'content_ar' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $validated['updated_by_user_id'] = $request->user()?->id;
        $page = Page::query()->create($validated);

        $this->auditLogService->record($request->user(), 'page.created', 'Page', $page->id, null, ['slug' => $page->slug]);

        return response()->json([
            'message' => __('Page created successfully.'),
            'data' => $page,
        ], 201);
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['sometimes', 'string', 'max:80', 'alpha_dash', Rule::unique('pages', 'slug')->ignore($page->id)],
            'title_en' => ['sometimes', 'string', 'max:255'],
            'title_ar' => ['sometimes', 'string', 'max:255'],
            'content_en' => ['sometimes', 'string'],
            'content_ar' => ['sometimes', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $before = $page->only(['slug', 'title_en', 'is_published']);
        $validated['updated_by_user_id'] = $request->user()?->id;
        $page->update($validated);

        $this->auditLogService->record(
            $request->user(),
            'page.updated',
            'Page',
            $page->id,
            $before,
            $page->fresh()->only(['slug', 'title_en', 'is_published']),
        );

        return response()->json([
            'message' => __('Page updated successfully.'),
            'data' => $page->fresh(),
        ]);
    }

    public function destroy(Request $request, Page $page): JsonResponse
    {
        $slug = $page->slug;
        $page->delete();

        $this->auditLogService->record($request->user(), 'page.deleted', 'Page', null, ['slug' => $slug], null);

        return response()->json([
            'message' => __('Page deleted successfully.'),
        ]);
    }
}
