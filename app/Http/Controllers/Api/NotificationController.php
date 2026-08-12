<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService,
    ) {}

    /**
     * List notifications for the authenticated user (public + private to them).
     * Paginated; unread_count is global for badge.
     *
     * Public notifications have no per-recipient row, so a category the
     * user has opted out of (spec §33) is excluded here rather than at
     * creation time — see NotificationPreferenceService::disabledCategoriesFor().
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;
        $disabledCategories = $this->preferenceService->disabledCategoriesFor($user);

        $visibility = function ($q) use ($disabledCategories, $userId) {
            $q->where(function ($inner) use ($disabledCategories) {
                $inner->where('admin_notifications.type', AdminNotification::TYPE_PUBLIC);
                if ($disabledCategories !== []) {
                    $inner->where(function ($cat) use ($disabledCategories) {
                        $cat->whereNull('admin_notifications.category')
                            ->orWhereNotIn('admin_notifications.category', $disabledCategories);
                    });
                }
            })->orWhereExists(function ($sub) use ($userId) {
                $sub->select(DB::raw(1))
                    ->from('admin_notification_recipients')
                    ->whereColumn('admin_notification_recipients.admin_notification_id', 'admin_notifications.id')
                    ->where('admin_notification_recipients.user_id', $userId);
            });
        };

        $baseQuery = AdminNotification::query()
            ->select('admin_notifications.id', 'admin_notifications.title', 'admin_notifications.body', 'admin_notifications.type', 'admin_notifications.action_type', 'admin_notifications.action_id', 'admin_notifications.created_at', 'users.name as sender_name', 'admin_notification_reads.read_at')
            ->leftJoin('users', 'users.id', '=', 'admin_notifications.sent_by')
            ->leftJoin('admin_notification_reads', function ($join) use ($userId) {
                $join->on('admin_notification_reads.admin_notification_id', '=', 'admin_notifications.id')
                    ->where('admin_notification_reads.user_id', '=', $userId);
            })
            ->where($visibility)
            ->orderByDesc('admin_notifications.created_at');

        $perPage = max(1, min(20, (int) $request->query('per_page', 15)));
        $paginator = $baseQuery->paginate($perPage);

        $unreadCount = (int) AdminNotification::query()
            ->leftJoin('admin_notification_reads', function ($join) use ($userId) {
                $join->on('admin_notification_reads.admin_notification_id', '=', 'admin_notifications.id')
                    ->where('admin_notification_reads.user_id', '=', $userId);
            })
            ->whereNull('admin_notification_reads.read_at')
            ->where($visibility)
            ->count('admin_notifications.id');

        $data = $paginator->getCollection()->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'type' => $n->type,
            'action_type' => $n->action_type,
            'action_id' => $n->action_id !== null ? (int) $n->action_id : null,
            'sent_at' => $n->created_at->toIso8601String(),
            'read_at' => $n->read_at ? \Carbon\Carbon::parse($n->read_at)->toIso8601String() : null,
            'sender_name' => $n->sender_name,
        ])->values()->all();

        return response()->json([
            'message' => __('Notifications retrieved successfully.'),
            'data' => $data,
            'unread_count' => $unreadCount,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, AdminNotification $notification): JsonResponse
    {
        $user = $request->user();
        if (! $this->userCanSeeNotification($user, $notification)) {
            return response()->json(['message' => __('Notification not found.')], 404);
        }

        $exists = $user->notificationReads()->where('admin_notification_id', $notification->id)->exists();
        if ($exists) {
            $user->notificationReads()->updateExistingPivot($notification->id, ['read_at' => now()]);
        } else {
            $user->notificationReads()->attach($notification->id, ['read_at' => now()]);
        }

        return response()->json([
            'message' => __('Notification marked as read.'),
            'data' => ['id' => $notification->id],
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $disabledCategories = $this->preferenceService->disabledCategoriesFor($user);

        $notificationIds = AdminNotification::query()
            ->where(function ($q) use ($disabledCategories, $user) {
                $q->where(function ($inner) use ($disabledCategories) {
                    $inner->where('type', AdminNotification::TYPE_PUBLIC);
                    if ($disabledCategories !== []) {
                        $inner->where(function ($cat) use ($disabledCategories) {
                            $cat->whereNull('category')->orWhereNotIn('category', $disabledCategories);
                        });
                    }
                })->orWhereHas('recipients', fn ($r) => $r->where('users.id', $user->id));
            })
            ->pluck('id');

        $existing = $user->notificationReads()->whereIn('admin_notification_id', $notificationIds)->pluck('admin_notification_id');
        $toInsert = $notificationIds->diff($existing)->mapWithKeys(fn ($id) => [$id => ['read_at' => now()]])->all();
        if ($toInsert !== []) {
            $user->notificationReads()->attach($toInsert);
        }

        return response()->json([
            'message' => __('All notifications marked as read.'),
            'data' => ['marked' => count($toInsert) + $existing->count()],
        ]);
    }

    private function userCanSeeNotification($user, AdminNotification $notification): bool
    {
        if ($notification->type === AdminNotification::TYPE_PUBLIC) {
            return true;
        }

        return $notification->recipients()->where('users.id', $user->id)->exists();
    }
}
