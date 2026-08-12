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
        if ($request->filled('preferred_product_type')) {
            return $this->storeFromRequest($request);
        }

        $user = $request->user();

        if ($user && $user->type !== User::TYPE_USER) {
            return redirect()->to($this->dashboardPathFor($user));
        }

        return view('preferences.product-type', [
            'selectedType' => $this->selectedProductTypeService->resolve($request),
            'types' => $this->types(),
        ]);
    }

    public function store(StoreProductTypePreferenceRequest $request): RedirectResponse
    {
        $type = $request->validated('preferred_product_type');
        $redirectTo = $request->validated('redirect_to') ?? 'categories';

        return $this->persistSelection($request, $type, $redirectTo);
    }

    private function storeFromRequest(Request $request): RedirectResponse
    {
        $type = $this->selectedProductTypeService->normalize($request->string('preferred_product_type')->toString());
        $redirectTo = $request->string('redirect_to')->toString();
        $redirectTo = in_array($redirectTo, ['home', 'categories'], true) ? $redirectTo : 'categories';

        if (! $type) {
            return redirect()
                ->route('product-type.select')
                ->withInput()
                ->withErrors([
                    'preferred_product_type' => __('preferences.invalid_type'),
                ]);
        }

        return $this->persistSelection($request, $type, $redirectTo);
    }

    private function persistSelection(Request $request, string $type, string $redirectTo): RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->type === User::TYPE_USER) {
            $user->forceFill(['preferred_product_type' => $type])->save();
        }

        $this->selectedProductTypeService->remember($request, $type);

        $route = $redirectTo === 'home'
            ? route('home', ['type' => $type])
            : route('categories.index', ['type' => $type]);

        return redirect()
            ->to($route)
            ->with('success', __('preferences.saved_success'));
    }

    /**
     * @return array<string, array{label: string, description: string, icon: string, button: string}>
     */
    private function types(): array
    {
        return [
            Category::TYPE_AGRICULTURE => [
                'label' => __('preferences.agriculture_label'),
                'description' => __('preferences.agriculture_description'),
                'icon' => 'fa-solid fa-seedling',
                'button' => __('preferences.agriculture_button'),
            ],
            Category::TYPE_VETERINARY => [
                'label' => __('preferences.veterinary_label'),
                'description' => __('preferences.veterinary_description'),
                'icon' => 'fa-solid fa-stethoscope',
                'button' => __('preferences.veterinary_button'),
            ],
        ];
    }

    private function dashboardPathFor(User $user): string
    {
        return match ($user->type) {
            User::TYPE_ADMIN => route('admin.dashboard'),
            User::TYPE_VENDOR => route('vendor.dashboard'),
            User::TYPE_SYNDICATE => route('syndicate.dashboard'),
            User::TYPE_EMPLOYEE => route('employee.dashboard'),
            default => route('home'),
        };
    }
}
