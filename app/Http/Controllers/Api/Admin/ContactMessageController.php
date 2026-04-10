<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * List all contact messages for admin (paginated, filter by status).
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::query()
            ->with('user:id,name,email')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $messages = $query->paginate($perPage);

        $items = $messages->getCollection()->map(function (ContactMessage $m) {
            return [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'user' => $m->user ? [
                    'id' => $m->user->id,
                    'name' => $m->user->name,
                    'email' => $m->user->email,
                ] : null,
                'name' => $m->name,
                'email' => $m->email,
                'message' => $m->message,
                'status' => $m->status,
                'admin_reply' => $m->admin_reply,
                'replied_at' => $m->replied_at?->toIso8601String(),
                'created_at' => $m->created_at?->toIso8601String(),
            ];
        });

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

    /**
     * Show a single contact message (admin).
     */
    public function show(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->load('user:id,name,email');

        return response()->json([
            'message' => __('Contact message retrieved successfully.'),
            'data' => [
                'id' => $contactMessage->id,
                'user_id' => $contactMessage->user_id,
                'user' => $contactMessage->user ? [
                    'id' => $contactMessage->user->id,
                    'name' => $contactMessage->user->name,
                    'email' => $contactMessage->user->email,
                ] : null,
                'name' => $contactMessage->name,
                'email' => $contactMessage->email,
                'message' => $contactMessage->message,
                'status' => $contactMessage->status,
                'admin_reply' => $contactMessage->admin_reply,
                'replied_at' => $contactMessage->replied_at?->toIso8601String(),
                'created_at' => $contactMessage->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Reply to a contact message (admin).
     */
    public function reply(ReplyContactMessageRequest $request, ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->update([
            'admin_reply' => $request->validated('admin_reply'),
            'status' => ContactMessage::STATUS_REPLIED,
            'replied_at' => now(),
        ]);

        return response()->json([
            'message' => __('Reply sent successfully.'),
            'data' => [
                'id' => $contactMessage->id,
                'status' => $contactMessage->status,
                'admin_reply' => $contactMessage->admin_reply,
                'replied_at' => $contactMessage->replied_at?->toIso8601String(),
            ],
        ]);
    }
}
