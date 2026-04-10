<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * Store a contact message (public). If user is authenticated, link to user and use their email/name by default.
     */
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $message = new ContactMessage;
        $message->user_id = $user?->id;
        $message->name = $validated['name'] ?? $user?->name;
        $message->email = $validated['email'] ?? $user?->email;
        $message->message = $validated['message'];
        $message->status = ContactMessage::STATUS_PENDING;
        $message->save();

        return response()->json([
            'message' => __('Your message has been sent. We will get back to you soon.'),
            'data' => [
                'id' => $message->id,
                'status' => $message->status,
            ],
        ], 201);
    }

    /**
     * List contact messages for the authenticated user (for profile history). Paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->get('per_page', 10), 30);
        $messages = $user->contactMessages()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = $messages->getCollection()->map(fn (ContactMessage $m) => [
            'id' => $m->id,
            'message' => $m->message,
            'status' => $m->status,
            'admin_reply' => $m->admin_reply,
            'replied_at' => $m->replied_at?->toIso8601String(),
            'created_at' => $m->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'message' => __('Contact messages retrieved successfully.'),
            'data' => $items,
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }
}
