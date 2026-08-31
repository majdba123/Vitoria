<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Services\NotificationPreferenceException;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Per-user notification opt-outs (spec §33). In-app only — see the
 * migration's class doc for why no email/SMS/push channel is offered.
 */
class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('api.notification_preferences_retrieved'),
            'data' => $this->preferenceService->listForUser($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(NotificationPreference::ALL_CATEGORIES)],
            'enabled' => ['required', 'boolean'],
        ]);

        try {
            $this->preferenceService->setPreference($request->user(), $validated['category'], $validated['enabled']);
        } catch (NotificationPreferenceException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('notification_preferences.updated_success'),
            'data' => $this->preferenceService->listForUser($request->user()),
        ]);
    }
}
