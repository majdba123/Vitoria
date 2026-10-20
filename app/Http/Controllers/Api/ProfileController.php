<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        $oldAvatar = $user->avatar;
        $newAvatar = null;

        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $newAvatar;
        }

        try {
            DB::transaction(function () use ($user, $validated): void {
                $user->fill($validated);
                $passwordChanged = $user->isDirty('password');

                if ($user->isDirty()) {
                    $user->save();
                }

                if ($passwordChanged) {
                    app(AuditLogService::class)->record($user, 'user.password_changed', 'User', $user->id, null, ['via' => 'profile']);
                }
            });
        } catch (\Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }

            throw $exception;
        }

        if ($newAvatar && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $user->refresh();

        if (array_key_exists('preferred_product_type', $validated) && $request->hasSession()) {
            $request->session()->put('preferred_product_type', $user->preferred_product_type);
        }

        if (array_key_exists('locale', $validated)) {
            if ($request->hasSession()) {
                $request->session()->put('locale', $user->locale);
            }

            Cookie::queue('locale', $user->locale, 60 * 24 * 365);
            Cookie::queue('sz_locale', $user->locale, 60 * 24 * 365);
        }

        return response()->json([
            'message' => __('Profile updated successfully.'),
            'data' => new UserResource($user),
        ]);
    }
}
