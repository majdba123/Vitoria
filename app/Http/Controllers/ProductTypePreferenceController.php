<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductTypePreferenceRequest;
use App\Models\Category;
use App\Models\User;
use App\Services\SelectedProductTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductTypePreferenceController extends Controller
{
    public function __construct(protected SelectedProductTypeService $selectedProductTypeService) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->type !== User::TYPE_USER) {
            return redirect()->to($this->dashboardPathFor($user));
        }

        return view('preferences.product-type', [
            'selectedType' => $this->selectedProductTypeService->resolve($request),
            'types' => [
                Category::TYPE_AGRICULTURE => [
                    'label' => 'زراعي',
                    'description' => 'تصفح المنتجات والخدمات الزراعية المناسبة لاحتياجاتك اليومية.',
                    'icon' => 'fa-solid fa-seedling',
                    'button' => 'اختيار القسم الزراعي',
                ],
                Category::TYPE_VETERINARY => [
                    'label' => 'بيطري',
                    'description' => 'تصفح المنتجات والخدمات البيطرية مع وصول أسرع للفئات المناسبة.',
                    'icon' => 'fa-solid fa-stethoscope',
                    'button' => 'اختيار القسم البيطري',
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

        $this->selectedProductTypeService->remember($request, $type);

        return redirect()
            ->route('categories.index', ['type' => $type])
            ->with('success', 'تم حفظ نوع التصفح بنجاح.');
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
