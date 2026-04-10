<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(public AuthService $authService) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        $result['user']->load('city');

        // Establish web session alongside the API token
        Auth::login($result['user']);

        return response()->json([
            'message' => __('User registered successfully.'),
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
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

        return response()->json([
            'message' => __('Logged in successfully.'),
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
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

        // Invalidate web session if it exists
        try {
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Safely invalidate session
        try {
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        } catch (\Exception $e) {
            // Session might not exist, ignore
        }

        return response()->json([
            'message' => __('Logged out successfully.'),
        ]);
    }
}
