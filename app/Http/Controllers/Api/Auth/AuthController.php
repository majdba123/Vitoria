<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Commerce\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    public function __construct(public AuthService $authService) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        $result['user']->load(['city', 'syndicate']);

        // Establish web session alongside the API token
        Auth::login($result['user']);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        $this->persistLocale($request, $result['user']->locale);

        // Registration is also an authentication boundary. Preserve the
        // visitor's server cart exactly as login does, so choosing to create
        // an account during checkout never discards shopping intent.
        if ($request->hasSession()) {
            app(CartService::class)->mergeGuestCartIntoUser($request, $result['user']);
        }

        return response()->json([
            'message' => __('User registered successfully.'),
            'data' => [
                'user' => new UserResource($result['user']->load('syndicate')),
                'token' => $result['token'],
                'redirect_url' => $this->redirectUrlFor($result['user']),
            ],
        ], 201);
    }

    /**
     * Login an existing user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        // Establish web session alongside the API token
        Auth::login($result['user']);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        $this->persistLocale($request, $result['user']->locale);

        // Carry anything the visitor added before signing in into their account
        // cart (spec §5). session()->regenerate() rotates the id but keeps the
        // payload, so the guest cart token is still readable here.
        if ($request->hasSession()) {
            app(CartService::class)->mergeGuestCartIntoUser($request, $result['user']);
        }

        return response()->json([
            'message' => __('Logged in successfully.'),
            'data' => [
                'user' => new UserResource($result['user']->load('syndicate')),
                'token' => $result['token'],
                'redirect_url' => $this->redirectUrlFor($result['user']),
            ],
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke Sanctum token if user exists
        if ($user) {
            $this->authService->logout($user);
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => __('Logged out successfully.'),
        ]);
    }

    protected function redirectUrlFor(User $user): string
    {
        return match ($user->type) {
            User::TYPE_ADMIN => route('admin.dashboard'),
            User::TYPE_VENDOR => route('vendor.dashboard'),
            User::TYPE_SYNDICATE => route('syndicate.dashboard'),
            User::TYPE_EMPLOYEE => route('employee.dashboard'),
            default => $user->preferred_product_type
                ? route('home')
                : route('product-type.select'),
        };
    }

    protected function persistLocale(Request $request, ?string $locale): void
    {
        $resolvedLocale = in_array($locale, ['ar', 'en'], true)
            ? $locale
            : app()->getLocale();

        if ($request->hasSession()) {
            $request->session()->put('locale', $resolvedLocale);
        }

        Cookie::queue('locale', $resolvedLocale, 60 * 24 * 365);
        Cookie::queue('sz_locale', $resolvedLocale, 60 * 24 * 365);
    }
}
