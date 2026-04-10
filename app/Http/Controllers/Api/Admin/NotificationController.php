<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\AdminNotificationSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendNotificationRequest;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Send a public or private notification and broadcast via WebSocket.
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $notification = AdminNotification::query()->create([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'type' => $request->validated('type'),
            'sent_by' => $request->user()->id,
        ]);

        $recipientUserIds = [];
        if ($notification->type === AdminNotification::TYPE_PRIVATE) {
            $recipientUserIds = $request->validated('user_ids', []);
            $notification->recipients()->sync($recipientUserIds);
        }

        AdminNotificationSent::dispatch(
            $notification->id,
            $notification->title,
            $notification->body,
            $notification->type,
            $recipientUserIds,
            $notification->action_type,
            $notification->action_id !== null ? (int) $notification->action_id : null,
        );

        return response()->json([
            'message' => __('Notification sent successfully.'),
            'data' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'type' => $notification->type,
                'recipient_count' => count($recipientUserIds),
            ],
        ], 201);
    }
}
