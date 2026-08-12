<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;

/**
 * Per-user notification opt-outs (spec §33). Critical categories bypass this
 * service's storage entirely — `isEnabled()` returns true for them without a
 * query, the same defense-in-depth shape as `User::hasVendorPermission()`'s
 * owner bypass (decision D15): a missing or corrupted preference row can
 * never silently suppress a transaction-critical notice.
 */
class NotificationPreferenceService
{
    public function isEnabled(int $userId, string $category): bool
    {
        if (in_array($category, NotificationPreference::CRITICAL_CATEGORIES, true)) {
            return true;
        }

        $preference = NotificationPreference::query()
            ->where('user_id', $userId)
            ->where('category', $category)
            ->first();

        // No row means the default: enabled.
        return $preference === null || $preference->enabled;
    }

    /**
     * Bulk version of isEnabled() for filtering a notification's recipient
     * list without one query per user.
     *
     * @param  list<int>  $userIds
     * @return list<int>
     */
    public function filterEnabled(array $userIds, string $category): array
    {
        if ($userIds === [] || in_array($category, NotificationPreference::CRITICAL_CATEGORIES, true)) {
            return $userIds;
        }

        $disabled = NotificationPreference::query()
            ->whereIn('user_id', $userIds)
            ->where('category', $category)
            ->where('enabled', false)
            ->pluck('user_id')
            ->all();

        return array_values(array_diff($userIds, $disabled));
    }

    /**
     * @throws NotificationPreferenceException
     */
    public function setPreference(User $user, string $category, bool $enabled): NotificationPreference
    {
        if (! in_array($category, NotificationPreference::ALL_CATEGORIES, true)) {
            throw new NotificationPreferenceException(__('notification_preferences.category_invalid'));
        }

        if (! $enabled && in_array($category, NotificationPreference::CRITICAL_CATEGORIES, true)) {
            throw new NotificationPreferenceException(__('notification_preferences.category_not_editable'));
        }

        return NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->id, 'category' => $category],
            ['enabled' => $enabled],
        );
    }

    /**
     * @return list<array{category: string, category_name: string, editable: bool, enabled: bool}>
     */
    public function listForUser(User $user): array
    {
        $stored = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->pluck('enabled', 'category');

        return collect(NotificationPreference::ALL_CATEGORIES)
            ->map(fn (string $category) => [
                'category' => $category,
                'category_name' => __("notification_preferences.category.{$category}"),
                'editable' => in_array($category, NotificationPreference::MUTABLE_CATEGORIES, true),
                'enabled' => in_array($category, NotificationPreference::CRITICAL_CATEGORIES, true)
                    ? true
                    : (bool) ($stored[$category] ?? true),
            ])
            ->values()
            ->all();
    }

    /**
     * The requesting user's disabled *mutable* categories — used to filter
     * public (broadcast-to-everyone) notifications out of their own list,
     * since those have no per-recipient row to skip at creation time.
     *
     * @return list<string>
     */
    public function disabledCategoriesFor(User $user): array
    {
        return NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('enabled', false)
            ->whereIn('category', NotificationPreference::MUTABLE_CATEGORIES)
            ->pluck('category')
            ->all();
    }
}
