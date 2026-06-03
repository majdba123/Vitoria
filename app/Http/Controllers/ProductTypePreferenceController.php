<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductTypePreferenceRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductTypePreferenceController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user && $user->type !== User::TYPE_USER) {
            return redirect()->to($this->dashboardPathFor($user));
        }

        if ($user?->preferred_product_type) {
            return redirect()->route('categories.index', ['type' => $user->preferred_product_type]);
        }

        return view('preferences.product-type', [
            'types' => [
                Category::TYPE_AGRICULTURE => [
                    'label' => 'زراعي',
                    'description' => 'منتجات البذور والأسمدة والري والمستلزمات الزراعية.',
                    'icon' => 'fa-solid fa-seedling',
                ],
                Category::TYPE_VETERINARY => [
                    'label' => 'بيطري',
                    'description' => 'منتجات الأدوية واللقاحات ومستلزمات رعاية الحيوانات.',
                    'icon' => 'fa-solid fa-stethoscope',
                ],
            ],
        ]);
    }

    public function store(StoreProductTypePreferenceRequest $request): RedirectResponse
    {
        $type = $request->validated('preferred_product_type');
        $user = $request->user();

        if ($user && $user->type === User::TYPE_USER) {
            $user->forceFill(['preferred_product_type' => $type])->save();
        }

        $request->session()->put('preferred_product_type', $type);

        return redirect()
            ->route('categories.index', ['type' => $type])
            ->with('success', 'تم حفظ تفضيل نوع المنتجات بنجاح.');
    }

    private function dashboardPathFor(User $user): string
    {
        return match ($user->type) {
            User::TYPE_ADMIN => route('admin.dashboard'),
            User::TYPE_VENDOR => route('vendor.dashboard'),
            User::TYPE_SYNDICATE => route('syndicate.dashboard'),
            default => route('home'),
        };
    }
}
