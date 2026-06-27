<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(public UserService $userService) {}

    /**
     * List all users.
     */
    public function index(): JsonResponse
    {
        $perPage = min((int) request('per_page', 15), 500);
        $users = User::query()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => __('Users retrieved successfully.'),
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Show a single user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'message' => __('User retrieved successfully.'),
            'data' => new UserResource($user),
        ]);
    }

    /**
     * List a specific user's favourite products (admin view).
     */
    public function favourites(User $user): JsonResponse
    {
        $products = $user->favouriteProducts()
            ->with([
                'photos' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->limit(1),
                'vendor:id,store_name',
                'subcategory:id,name,category_id',
                'subcategory.category:id,name',
            ])
            ->select(['products.id', 'products.name', 'products.price', 'products.vendor_id', 'products.subcategory_id', 'products.quantity'])
            ->latest('favourites.created_at')
            ->get();

        $mapped = $products->map(function ($product) {
            $photo = $product->photos->first();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $product->quantity,
                'first_photo_url' => $photo ? asset('storage/'.$photo->path) : null,
                'vendor' => $product->vendor ? ['id' => $product->vendor->id, 'store_name' => $product->vendor->store_name] : null,
                'subcategory' => $product->subcategory ? [
                    'id' => $product->subcategory->id,
                    'name' => $product->subcategory->name,
                    'category' => $product->subcategory->category ? ['id' => $product->subcategory->category->id, 'name' => $product->subcategory->category->name] : null,
                ] : null,
            ];
        });

        return response()->json([
            'message' => __('User favourites retrieved successfully.'),
            'data' => $mapped,
        ]);
    }

    /**
     * Create a new user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'message' => __('User created successfully.'),
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Update an existing user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        return response()->json([
            'message' => __('User updated successfully.'),
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->json([
            'message' => __('User deleted successfully.'),
        ]);
    }
}
