<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class SelectedProductTypeService
{
    public const COOKIE_NAME = 'preferred_product_type';

    public function resolve(Request $request): ?string
    {
        $type = $this->normalize($request->input('type') ?? $request->input('category_type'));

        if ($type) {
            return $type;
        }

        $user = $request->user();
        $type = $user instanceof User && $user->type === User::TYPE_USER
            ? $user->preferred_product_type
            : null;

        $type ??= $request->hasSession() ? $request->session()->get('preferred_product_type') : null;
        $type ??= $request->cookie(self::COOKIE_NAME);

        return $this->normalize($type);
    }

    public function remember(Request $request, string $type): void
    {
        $normalizedType = $this->normalize($type);

        if ($request->hasSession()) {
            $request->session()->put('preferred_product_type', $normalizedType);
        }

        Cookie::queue(cookie(self::COOKIE_NAME, $normalizedType, 60 * 24 * 30));
    }

    public function normalize(?string $type): ?string
    {
        return in_array($type, [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], true)
            ? $type
            : null;
    }

    public function requiresSelection(?User $user): bool
    {
        return ! $user || $user->type === User::TYPE_USER;
    }

    public function abortIfTypeMismatch(Request $request, ?string $recordType): void
    {
        $selectedType = $this->resolve($request);

        if ($selectedType && $recordType && $selectedType !== $recordType) {
            abort(404, __('The requested page could not be found.'));
        }
    }
}
