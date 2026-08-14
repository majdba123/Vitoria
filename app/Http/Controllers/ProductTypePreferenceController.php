<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductTypePreferenceRequest;
use App\Models\User;
use App\Services\SelectedProductTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductTypePreferenceController extends Controller
{
    public function __construct(protected SelectedProductTypeService $selectedProductTypeService) {}

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->filled('preferred_product_type')) {
            return $this->storeFromRequest($request);
        }

        $user = $request->user();

        if ($user && $user->type !== User::TYPE_USER) {
            return redirect()->to($this->dashboardPathFor($user));
        }

        return Inertia::render('Preferences/ProductType', [
            'selectedType' => $this->selectedProductTypeService->resolve($request),
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
