<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone_number' => ['sometimes', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'timezone' => ['sometimes', 'nullable', 'string', Rule::in(timezone_identifiers_list())],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ]);

        $oldAvatar = $user->avatar;
        $newAvatar = null;

        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $newAvatar;
        }

        try {
            DB::transaction(function () use ($user, $validated): void {
                $user->fill($validated);

                if ($user->isDirty()) {
                    $user->save();
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

        return response()->json([
            'message' => __('Profile updated successfully.'),
            'data' => new UserResource($user),
        ]);
    }
}
